<?php
namespace FandooghRest\Import;

defined('ABSPATH') || exit;

/** Bounded CSV/OpenXML reader. Never extracts ZIPs, evaluates formulas, or resolves XML entities. */
final class Reader
{
    public const MAX_ROWS = 2000;
    public const MAX_COLUMNS = 40;
    public const MAX_BYTES = 5242880;

    public static function read(string $path, string $extension): array|\WP_Error
    {
        if (!is_file($path) || filesize($path) > self::MAX_BYTES) {
            return self::error('File must be smaller than 5 MB.');
        }
        try {
            $rows = $extension === 'xlsx' ? self::xlsx($path) : self::csv($path);
            if (!$rows) {
                return self::error('The file is empty.');
            }
            $headers = array_shift($rows);
            if (!$headers || count($headers) > self::MAX_COLUMNS) {
                return self::error('The file must contain at most 40 columns.');
            }
            $columns = [];
            foreach ($headers as $index => $label) {
                $label = trim((string) $label) ?: 'ستون ' . ($index + 1);
                $base = mb_substr($label, 0, 100);
                $label = $base;
                $suffix = 2;
                while (in_array($label, $columns, true)) {
                    $label = $base . ' (' . $suffix++ . ')';
                }
                $columns[] = $label;
            }
            $data = [];
            $bytes = 0;
            foreach ($rows as $row) {
                if (!array_filter($row, static fn($cell): bool => trim((string) $cell) !== '')) {
                    continue;
                }
                foreach ($row as $cell) {
                    $bytes += strlen((string) $cell);
                }
                if ($bytes > 2 * 1024 * 1024) {
                    return self::error('Product cell data exceeds the 2 MB safety limit.');
                }
                $data[] = array_pad(array_slice($row, 0, count($columns)), count($columns), '');
            }
            if (count($data) > self::MAX_ROWS) {
                return self::error('A file can contain at most 2000 product rows.');
            }
            return ['columns' => $columns, 'rows' => $data];
        } catch (\Throwable $error) {
            return self::error($error->getMessage());
        }
    }

    private static function csv(string $path): array
    {
        $text = file_get_contents($path);
        if ($text === false || str_contains($text, "\0")) {
            throw new \RuntimeException('Use a UTF-8 CSV file.');
        }
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        if (!mb_check_encoding($text, 'UTF-8')) {
            throw new \RuntimeException('Save the CSV file with UTF-8 encoding.');
        }
        $line = strtok($text, "\r\n") ?: '';
        $delimiter = ',';
        $maximum = 0;
        foreach ([',', ';', "\t"] as $candidate) {
            $count = count(str_getcsv($line, $candidate, '"', ''));
            if ($count > $maximum) {
                $delimiter = $candidate;
                $maximum = $count;
            }
        }
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $text);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            if (count($rows) > self::MAX_ROWS || count($row) > self::MAX_COLUMNS) {
                fclose($stream);
                throw new \RuntimeException('The file exceeds the row or column limit.');
            }
            $rows[] = array_map([self::class, 'cell'], $row);
        }
        fclose($stream);
        return $rows;
    }

    private static function xml(string $text): \SimpleXMLElement
    {
        if (strlen($text) > 20 * 1024 * 1024 || preg_match('/<!\s*(DOCTYPE|ENTITY)/i', $text)) {
            throw new \RuntimeException('Unsafe or oversized Excel XML.');
        }
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($text, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$xml) {
            throw new \RuntimeException('The Excel workbook contains invalid XML.');
        }
        return $xml;
    }

    private static function xlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Enable the PHP ZIP extension to import XLSX files.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Invalid XLSX file.');
        }
        try {
            $expanded = 0;
            if ($zip->numFiles > 256) {
                throw new \RuntimeException('The workbook contains too many files.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $expanded += $entry['size'];
                if ($expanded > 25 * 1024 * 1024 || $entry['size'] > 20 * 1024 * 1024) {
                    throw new \RuntimeException('The expanded workbook is too large.');
                }
            }
            $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $shared = [];
            if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                foreach (self::xml($sharedXml)->children($namespace)->si as $string) {
                    $parts = $string->xpath('.//*[local-name()="t"]');
                    $shared[] = self::cell(implode('', array_map('strval', $parts ?: [])));
                }
            }
            $workbook = $zip->getFromName('xl/workbook.xml');
            $relationships = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbook === false || $relationships === false) {
                throw new \RuntimeException('The workbook is missing its sheet definitions.');
            }
            $sheets = self::xml($workbook)->xpath('//*[local-name()="sheet"]');
            if (!$sheets) {
                throw new \RuntimeException('The workbook has no sheets.');
            }
            $relationId = (string) $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $sheetPath = '';
            foreach (self::xml($relationships)->xpath('//*[local-name()="Relationship"]') ?: [] as $relation) {
                if ((string) $relation['Id'] === $relationId && (string) $relation['TargetMode'] !== 'External') {
                    $target = (string) $relation['Target'];
                    if (str_contains($target, '..') || str_contains($target, '\\') || !preg_match('#^/?(?:xl/)?worksheets/[a-zA-Z0-9_.-]+\.xml$#D', $target)) {
                        throw new \RuntimeException('Invalid worksheet path.');
                    }
                    $sheetPath = str_starts_with(ltrim($target, '/'), 'xl/') ? ltrim($target, '/') : 'xl/' . ltrim($target, '/');
                    break;
                }
            }
            $sheet = $sheetPath ? $zip->getFromName($sheetPath) : false;
            if ($sheet === false) {
                throw new \RuntimeException('The first worksheet could not be read.');
            }
            $rows = [];
            foreach (self::xml($sheet)->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                if (count($rows) > self::MAX_ROWS) {
                    throw new \RuntimeException('The workbook exceeds 2000 product rows.');
                }
                $values = [];
                foreach ($row->children($namespace)->c as $cell) {
                    $attributes = $cell->attributes();
                    $reference = (string) $attributes['r'];
                    if (!preg_match('/^([A-Z]+)[1-9][0-9]*$/D', $reference, $match)) {
                        throw new \RuntimeException('Invalid Excel cell reference.');
                    }
                    $index = 0;
                    foreach (str_split($match[1]) as $letter) {
                        $index = $index * 26 + ord($letter) - 64;
                    }
                    if ($index > self::MAX_COLUMNS) {
                        throw new \RuntimeException('The workbook exceeds 40 columns.');
                    }
                    $child = $cell->children($namespace);
                    $value = (string) $child->v;
                    if (isset($child->f)) {
                        $value = ''; // Ignore formulas, including their cached values.
                    } elseif ((string) $attributes['t'] === 's') {
                        $value = $shared[(int) $value] ?? '';
                    } elseif ((string) $attributes['t'] === 'inlineStr') {
                        $parts = $cell->xpath('.//*[local-name()="t"]');
                        $value = implode('', array_map('strval', $parts ?: []));
                    }
                    $values[$index - 1] = self::cell($value);
                }
                if ($values) {
                    $dense = array_fill(0, max(array_keys($values)) + 1, '');
                    foreach ($values as $index => $value) {
                        $dense[$index] = $value;
                    }
                    $rows[] = $dense;
                }
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    private static function cell(mixed $value): string
    {
        return mb_substr(trim((string) $value), 0, 4000);
    }

    public static function price(string $value): string|\WP_Error|null
    {
        $value = strtr(trim($value), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', ',' => '', '٬' => '', '٫' => '.', 'تومان' => '', 'ریال' => '', ' ' => '', "\u{00A0}" => '']);
        if ($value === '' || mb_stripos($value, 'ناموجود') !== false || in_array(strtolower($value), ['unavailable', 'outofstock', 'out of stock'], true)) {
            return null;
        }
        if (!preg_match('/^[0-9]+(?:\.[0-9]{1,6})?$/D', $value) || !is_finite((float) $value)) {
            return self::error('Price must be a non-negative number, blank, or unavailable.');
        }
        return $value;
    }

    private static function error(string $message): \WP_Error
    {
        return new \WP_Error('admincafe_import_file', __($message, 'fandoogh-rest'), ['status' => 400]);
    }
}
