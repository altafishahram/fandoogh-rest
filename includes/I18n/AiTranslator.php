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
     * @return array Translated key-value array
     */
    public static function translate_batch($texts, $target_lang) {
        $provider = \RMM\Core\Settings::get('ai_translate_provider', 'google_free');
        $api_key  = \RMM\Core\Settings::get('gemini_api_key', '');

        if ($provider === 'gemini' && !empty($api_key)) {
            $result = self::translate_with_gemini($texts, $target_lang, $api_key);
            if (!is_wp_error($result) && !empty($result)) {
                return $result;
            }
        }

        // Fallback: Free Google Translate service
        return self::translate_with_google_free($texts, $target_lang);
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

        $prompt = "You are a professional restaurant menu translator. Translate the following restaurant food information into {$target_name}. Preserve food appetite appeal, tone, and culinary terms accurately.\n";
        $prompt .= "Return ONLY a valid JSON object matching the exact keys provided, with no extra text or markdown formatting.\n";
        $prompt .= "Input JSON:\n" . wp_json_encode($texts, JSON_UNESCAPED_UNICODE);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode($api_key);

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
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('gemini_err', 'Gemini API Error: ' . wp_remote_retrieve_body($response));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $raw_text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $clean_json = json_decode(trim($raw_text), true);
        if (is_array($clean_json)) {
            return $clean_json;
        }

        return new \WP_Error('json_err', 'Failed to parse Gemini response');
    }

    /**
     * Free Google Translate API Fallback
     */
    private static function translate_with_google_free($texts, $target_lang) {
        $translated = [];

        foreach ($texts as $key => $text) {
            if (empty($text) || !is_string($text)) {
                $translated[$key] = '';
                continue;
            }

            $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' . rawurlencode($target_lang) . '&dt=t&q=' . rawurlencode($text);

            $response = wp_remote_get($url, [
                'timeout' => 12,
                'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                    $combined = '';
                    foreach ($data[0] as $segment) {
                        $combined .= $segment[0] ?? '';
                    }
                    $translated[$key] = trim($combined);
                    continue;
                }
            }

            // Fallback: keep original text
            $translated[$key] = $text;
        }

        return $translated;
    }
}
