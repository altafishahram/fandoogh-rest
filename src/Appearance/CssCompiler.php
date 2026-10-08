<?php
namespace FandooghRest\Appearance;

defined('ABSPATH') || exit;

/** Bounded CSS subset parser. Tokens retain quoted strings; comments never join identifiers. */
final class CssCompiler
{
    private array $tokens = [];
    private int $position = 0;
    private int $rules = 0;
    private const FUNCTIONS = [
        'rgb', 'rgba', 'hsl', 'hsla', 'hwb', 'lab', 'lch', 'oklab', 'oklch', 'color', 'color-mix',
        'calc', 'min', 'max', 'clamp', 'var', 'env',
        'linear-gradient', 'radial-gradient', 'conic-gradient',
        'repeating-linear-gradient', 'repeating-radial-gradient',
        'translate', 'translatex', 'translatey', 'translate3d',
        'scale', 'scalex', 'scaley', 'scale3d', 'rotate', 'rotatex', 'rotatey', 'rotatez',
        'skew', 'skewx', 'skewy', 'matrix', 'matrix3d', 'perspective',
        'blur', 'brightness', 'contrast', 'grayscale', 'hue-rotate', 'invert',
        'opacity', 'saturate', 'sepia', 'drop-shadow', 'cubic-bezier', 'steps',
        'repeat', 'minmax', 'fit-content',
    ];

    public function compile(string $css, string $scope, bool $declarations = false): string
    {
        $this->tokens = $this->tokenize($css);
        $this->position = 0;
        $this->rules = 0;
        return $declarations ? $this->declarations($this->tokens) : $this->stylesheet($scope, 0, false);
    }

    private function fail(string $message): never
    {
        throw new \InvalidArgumentException($message);
    }

    private function tokenize(string $css): array
    {
        if (strlen($css) > 12000) {
            $this->fail('CSS exceeds the field limit.');
        }
        $tokens = [];
        $length = strlen($css);
        for ($i = 0; $i < $length;) {
            $c = $css[$i];
            if (
                $c === '\\' || $c === '<' || ord($c) === 127
                || (ord($c) < 32 && !in_array($c, ["\n", "\r", "\t"], true))
            ) {
                $this->fail('CSS escapes, HTML and control characters are not allowed.');
            }
            if (ctype_space($c)) {
                while ($i < $length && ctype_space($css[$i])) {
                    $i++;
                }
                $tokens[] = ['space', ' '];
                continue;
            }
            if (substr($css, $i, 2) === '/*') {
                $end = strpos($css, '*/', $i + 2);
                if ($end === false) {
                    $this->fail('Unclosed CSS comment.');
                }
                $i = $end + 2;
                $tokens[] = ['space', ' '];
                continue;
            }
            if (substr($css, $i, 2) === '*/') {
                $this->fail('Unexpected CSS comment terminator.');
            }
            if ($c === '"' || $c === "'") {
                $start = $i++;
                $quote = $c;
                while ($i < $length && $css[$i] !== $quote) {
                    if ($css[$i] === '\\' || $css[$i] === '<' || ord($css[$i]) < 32) {
                        $this->fail('Invalid CSS string.');
                    }
                    $i++;
                }
                if ($i === $length) {
                    $this->fail('Unclosed CSS string.');
                }
                $tokens[] = ['string', substr($css, $start, ++$i - $start)];
                continue;
            }
            if (ctype_alnum($c) || $c === '-' || $c === '_' || ord($c) >= 128) {
                $start = $i++;
                while (
                    $i < $length
                    && (ctype_alnum($css[$i]) || in_array($css[$i], ['-', '_'], true) || ord($css[$i]) >= 128)
                ) {
                    $i++;
                }
                $tokens[] = ['word', substr($css, $start, $i - $start)];
                continue;
            }
            $tokens[] = ['punct', $c];
            $i++;
            if (count($tokens) > 8000) {
                $this->fail('CSS is too complex.');
            }
        }
        return $tokens;
    }

    private function render(array $tokens): string
    {
        return trim(implode('', array_column($tokens, 1)));
    }

    private function skipSpace(): void
    {
        while (($this->tokens[$this->position][0] ?? '') === 'space') {
            $this->position++;
        }
    }

    /** Read a balanced prelude/value, stopping only at top-level punctuation. */
    private function until(array $stops): array
    {
        $result = [];
        $stack = [];
        while (isset($this->tokens[$this->position])) {
            $token = $this->tokens[$this->position];
            $c = $token[1];
            if ($token[0] === 'punct') {
                if (!$stack && in_array($c, $stops, true)) {
                    return $result;
                }
                if (in_array($c, ['(', '['], true)) {
                    $stack[] = $c;
                    if (count($stack) > 16) {
                        $this->fail('CSS is too deeply nested.');
                    }
                } elseif (in_array($c, [')', ']'], true)) {
                    if (array_pop($stack) !== ($c === ')' ? '(' : '[')) {
                        $this->fail('Unbalanced CSS parentheses.');
                    }
                } elseif (in_array($c, ['{', '}'], true)) {
                    $this->fail('Nested CSS declarations are not allowed.');
                }
            }
            $result[] = $token;
            $this->position++;
        }
        if ($stack) {
            $this->fail('Unbalanced CSS parentheses.');
        }
        return $result;
    }

    private function stylesheet(string $scope, int $depth, bool $inside): string
    {
        if ($depth > 4) {
            $this->fail('Too many nested responsive rules.');
        }
        $output = '';
        while (true) {
            $this->skipSpace();
            if (!isset($this->tokens[$this->position])) {
                if ($inside) {
                    $this->fail('Unclosed CSS block.');
                }
                return $output;
            }
            if ($this->tokens[$this->position][1] === '}') {
                if (!$inside) {
                    $this->fail('Unexpected CSS closing brace.');
                }
                $this->position++;
                return $output;
            }
            if (++$this->rules > 200) {
                $this->fail('Too many CSS rules.');
            }
            $prelude = $this->until(['{', '}', ';']);
            if (($this->tokens[$this->position][1] ?? '') !== '{') {
                $this->fail('A CSS rule requires a declaration block.');
            }
            $this->position++;
            if (($prelude[0][1] ?? '') === '@') {
                $name = strtolower($prelude[1][1] ?? '');
                if (
                    !in_array($name, ['media', 'supports'], true)
                    || !trim($this->render(array_slice($prelude, 2)))
                ) {
                    $this->fail('Only @media and @supports rules are allowed.');
                }
                $this->safeValues(array_slice($prelude, 2));
                $output .= $this->render($prelude) . '{' . $this->stylesheet($scope, $depth + 1, true) . '}';
            } else {
                $selectors = $this->selectors($prelude, $scope);
                $body = $this->until(['}']);
                if (($this->tokens[$this->position][1] ?? '') !== '}') {
                    $this->fail('Unclosed CSS declaration block.');
                }
                $this->position++;
                $output .= $selectors . '{' . $this->declarations($body) . '}';
            }
        }
    }

    private function selectors(array $tokens, string $scope): string
    {
        $parts = [];
        $part = [];
        $stack = 0;
        foreach ($tokens as $token) {
            if ($token[0] === 'punct') {
                if (in_array($token[1], ['(', '['], true)) {
                    $stack++;
                }
                if (in_array($token[1], [')', ']'], true)) {
                    $stack--;
                }
                if ($token[1] === ',' && $stack === 0) {
                    $parts[] = $part;
                    $part = [];
                    continue;
                }
                if (in_array($token[1], ['@', '&', ';', '|'], true)) {
                    $this->fail('Unsupported CSS selector.');
                }
            }
            $part[] = $token;
        }
        $parts[] = $part;
        $result = [];
        foreach ($parts as $selector) {
            $text = $this->render($selector);
            if (
                !$text || in_array($text[0], ['+', '~', '>'], true)
                || preg_match('/:(root|scope|host|host-context|global|slotted)\b|\b(html|body)\b/i', $text)
            ) {
                $this->fail('Use selectors relative to menu elements.');
            }
            // Prefix every selector-list member, including those inside responsive rules.
            $rootRelative = preg_match('/^\.ac-menu(?=$|[\s.:\[>])/D', $text) === 1;
            $rootPseudo = preg_match('/^:(hover|focus|active|focus-within|focus-visible)(?=$|[\s.:\[>])/D', $text) === 1;
            if ($rootRelative || $rootPseudo) {
                // Root-self selectors may descend into the menu, but never select its siblings.
                foreach ($selector as $token) {
                    if ($token[0] === 'punct' && in_array($token[1], ['+', '~'], true)) {
                        $this->fail('Root selectors cannot target sibling elements.');
                    }
                }
                $result[] = $scope . ($rootRelative ? substr($text, strlen('.ac-menu')) : $text);
            } else {
                $result[] = $scope . ' ' . $text;
            }
        }
        return implode(',', $result);
    }

    private function declarations(array $tokens): string
    {
        $savedTokens = $this->tokens;
        $savedPosition = $this->position;
        $this->tokens = $tokens;
        $this->position = 0;
        $output = '';
        $count = 0;
        while (true) {
            $this->skipSpace();
            if (!isset($this->tokens[$this->position])) {
                break;
            }
            if ($this->tokens[$this->position][1] === ';') {
                $this->position++;
                continue;
            }
            $property = $this->until([':', ';']);
            $name = $this->render($property);
            if (
                ($this->tokens[$this->position][1] ?? '') !== ':'
                || !preg_match('/^(--[a-zA-Z][a-zA-Z0-9_-]*|[a-zA-Z][a-zA-Z0-9-]*)$/D', $name)
            ) {
                $this->fail('Use CSS property: value declarations.');
            }
            if (in_array(strtolower($name), ['behavior', '-moz-binding', 'src'], true)) {
                $this->fail('This CSS property is not allowed.');
            }
            $this->position++;
            $value = $this->until([';']);
            if (!$this->render($value)) {
                $this->fail('CSS property values cannot be empty.');
            }
            $this->safeValues($value);
            foreach ($value as $token) {
                if ($token[0] === 'punct' && in_array($token[1], [';', ':'], true)) {
                    $this->fail('Invalid punctuation in CSS value.');
                }
            }
            // An inherited variable can contain url(). Allow variable substitution only in
            // known properties whose grammar cannot fetch images, including vendor variants.
            $plainName = preg_replace('/^-(webkit|moz|ms|o)-/', '', strtolower($name));
            $variableSafe = str_starts_with($name, '--') || preg_match(
                '/^(color|background-color|'
                . 'border(-(top|right|bottom|left|block|inline|block-start|block-end|inline-start|inline-end))?(-(color|style|width))?|'
                . 'border(-(top-left|top-right|bottom-left|bottom-right))?-radius|outline(-(color|style|width|offset))?|'
                . 'box-shadow|text-shadow|display|position|z-index|inset(-(block|inline|block-start|block-end|inline-start|inline-end))?|'
                . 'top|right|bottom|left|width|height|min-width|max-width|min-height|max-height|margin(-[a-z-]+)?|padding(-[a-z-]+)?|'
                . 'gap|row-gap|column-gap|grid(-[a-z-]+)?|flex(-[a-z-]+)?|align-[a-z-]+|justify-[a-z-]+|place-[a-z-]+|order|'
                . 'transform(-[a-z-]+)?|translate|rotate|scale|transition(-[a-z-]+)?|animation(-[a-z-]+)?|opacity|font(-[a-z-]+)?|'
                . 'line-height|letter-spacing|word-spacing|text-align|text-transform|text-indent|text-decoration(-[a-z-]+)?|'
                . 'white-space|word-break|overflow(-[a-z-]+)?|visibility|box-sizing|aspect-ratio)$/D',
                $plainName
            );
            if (!$variableSafe && preg_match('/\b(var|env)\s*\(/i', $this->render($value))) {
                $this->fail('Image-bearing or unknown properties cannot use CSS variables.');
            }
            $output .= $name . ':' . $this->render($value) . ';';
            if (++$count > 200) {
                $this->fail('Too many CSS declarations.');
            }
            if (isset($this->tokens[$this->position])) {
                $this->position++;
            }
        }
        $this->tokens = $savedTokens;
        $this->position = $savedPosition;
        return $output;
    }

    private function safeValues(array $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if ($token[0] === 'punct' && in_array($token[1], ['@', '{', '}', '<', '\\'], true)) {
                $this->fail('Unsupported CSS value.');
            }
            if ($token[0] !== 'word') {
                continue;
            }
            $word = strtolower($token[1]);
            if (in_array($word, ['url', 'expression', 'behavior', '-moz-binding', 'image-set', '-webkit-image-set', 'paint', 'element', '-moz-element'], true)) {
                $this->fail('External resources and executable CSS are not allowed.');
            }
            $next = $index + 1;
            if (($tokens[$next][1] ?? '') === '(' && !in_array($word, self::FUNCTIONS, true)) {
                $this->fail('Unsupported CSS function.');
            }
        }
    }
}
