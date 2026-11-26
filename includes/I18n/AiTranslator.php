<?php
namespace RMM\I18n;

if (!defined('ABSPATH')) {
    exit;
}

class AiTranslator {

    /**
     * Translate texts array into target language
     *
     * @param array $texts Key-value array of texts to translate (e.g. ['name' => '...', 'ingredients' => '...'])
     * @param string $target_lang Target language code (e.g. 'en', 'ar', 'tr', 'fr', 'zh', 'ru')
     * @return array|\WP_Error Translated key-value array or WP_Error on failure
     */
    public static function translate_batch($texts, $target_lang) {
        $provider = \RMM\Core\Settings::get('ai_translate_provider', 'google_free');
        $api_key  = \RMM\Core\Settings::get('gemini_api_key', '');

        $last_error = null;

        if ($provider === 'gemini' && !empty($api_key)) {
            $result = self::translate_with_gemini($texts, $target_lang, $api_key);
            if (!is_wp_error($result) && !empty($result)) {
                return $result;
            }
            if (is_wp_error($result)) {
                $last_error = $result;
            }
        }

        // Primary Free Fallback: Google Translate service (via POST)
        $google_res = self::translate_with_google_free($texts, $target_lang);
        if (!is_wp_error($google_res) && !empty($google_res)) {
            return $google_res;
        }

        // Secondary Free Fallback: MyMemory API
        $mymemory_res = self::translate_with_mymemory($texts, $target_lang);
        if (!is_wp_error($mymemory_res) && !empty($mymemory_res)) {
            return $mymemory_res;
        }

        // Return the most descriptive error instead of silently returning Persian text
        return $last_error ?: (is_wp_error($google_res) ? $google_res : new \WP_Error('rmm_translate_failed', __('خطا در برقراری ارتباط با سرویس‌های ترجمه هوشمند. لطفاً اتصال اینترنت سرور یا کلید هوش مصنوعی را بررسی کنید.', 'fondogh-online-menu')));
    }

    /**
     * Translate using Gemini AI API (v1beta)
     */
    private static function translate_with_gemini($texts, $target_lang, $api_key) {
        $lang_names = [
            'en' => 'English',
            'ar' => 'Arabic',
            'tr' => 'Turkish',
            'fr' => 'French',
            'ru' => 'Russian',
            'zh' => 'Simplified Chinese',
            'fa' => 'Persian'
        ];
        $target_name = $lang_names[$target_lang] ?? $target_lang;

        $prompt  = "You are a professional restaurant menu translator. Translate the following restaurant food information into {$target_name}. Preserve food appetite appeal, tone, and culinary terms accurately.\n";
        $prompt .= "Return ONLY a valid JSON object matching the exact keys provided, with no extra text or markdown formatting.\n";
        $prompt .= "Input JSON:\n" . wp_json_encode($texts, JSON_UNESCAPED_UNICODE);

        // Send key via header only so it is never leaked in server/proxy URL logs
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.2,
                'responseMimeType'=> 'application/json'
            ]
        ];

        $response = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => [
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $api_key,
            ],
            'body'    => wp_json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('gemini_err', __('خطا در برقراری ارتباط با سرور هوش مصنوعی Gemini: ', 'fondogh-online-menu') . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            $err_data = json_decode($raw_body, true);
            $err_msg  = $err_data['error']['message'] ?? ('HTTP ' . $code);
            if (stripos($err_msg, 'location is not supported') !== false) {
                return new \WP_Error('gemini_location', __('سرویس Gemini دسترسی از موقعیت سرور را مسدود کرده است. از مترجم رایگان یا سرور با پروکسی استفاده کنید.', 'fondogh-online-menu'));
            }
            return new \WP_Error('gemini_err', sprintf(__('خطا از سمت سرویس Gemini: %s', 'fondogh-online-menu'), $err_msg));
        }

        $data = json_decode($raw_body, true);
        $raw_text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Strip markdown code fences if model returned ```json ... ```
        $clean_text = preg_replace('/^```(?:json)?\s*/i', '', trim($raw_text));
        $clean_text = preg_replace('/\s*```$/', '', $clean_text);

        $clean_json = json_decode(trim($clean_text), true);
        if (is_array($clean_json)) {
            return $clean_json;
        }

        return new \WP_Error('json_err', __('خطا در پردازش ساختار پاسخ ترجمه هوش مصنوعی.', 'fondogh-online-menu'));
    }

    /**
     * Free Google Translate API Fallback (via POST)
     */
    private static function translate_with_google_free($texts, $target_lang) {
        $translated = [];
        $has_any_success = false;

        foreach ($texts as $key => $text) {
            if (empty($text) || !is_string($text)) {
                $translated[$key] = '';
                continue;
            }

            $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' . rawurlencode($target_lang) . '&dt=t';

            $response = wp_remote_post($url, [
                'timeout' => 12,
                'headers' => [
                    'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                ],
                'body'    => [
                    'q' => $text,
                ],
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                    $combined = '';
                    foreach ($data[0] as $segment) {
                        $combined .= $segment[0] ?? '';
                    }
                    $t_text = trim($combined);
                    if ($t_text !== '') {
                        $translated[$key] = $t_text;
                        $has_any_success = true;
                        continue;
                    }
                }
            }

            $translated[$key] = '';
        }

        if (!$has_any_success && !empty(array_filter($texts))) {
            return new \WP_Error('google_free_err', __('ارتباط با مترجم رایگان گوگل برقرار نشد.', 'fondogh-online-menu'));
        }

        return $translated;
    }

    /**
     * Secondary Free Translate Fallback (MyMemory API)
     */
    private static function translate_with_mymemory($texts, $target_lang) {
        $translated = [];
        $has_any_success = false;

        foreach ($texts as $key => $text) {
            if (empty($text) || !is_string($text)) {
                $translated[$key] = '';
                continue;
            }

            $url = 'https://api.mymemory.translated.net/get?q=' . rawurlencode($text) . '&langpair=fa|' . rawurlencode($target_lang);

            $response = wp_remote_get($url, [
                'timeout' => 12,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ],
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                $res_text = $data['responseData']['translatedText'] ?? '';
                if (!empty($res_text) && $res_text !== $text) {
                    $translated[$key] = trim(html_entity_decode($res_text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $has_any_success = true;
                    continue;
                }
            }

            $translated[$key] = '';
        }

        if (!$has_any_success && !empty(array_filter($texts))) {
            return new \WP_Error('mymemory_err', __('ارتباط با سرویس ترجمه جایگزین برقرار نشد.', 'fondogh-online-menu'));
        }

        return $translated;
    }
}
