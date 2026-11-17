<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'wb');
            self::write($out, $headers, $rows);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function write($out, array $headers, iterable $rows): void
    {
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($out, self::safeRow($row), ',', '"', '');
        }
    }

    private static function safeRow(iterable $row): array
    {
        $safe = [];

        foreach ($row as $value) {
            if (is_string($value) && ! is_numeric($value) && isset($value[0]) && in_array($value[0], ['=', '+', '-', '@'], true)) {
                $value = "'".$value;
            }

            $safe[] = $value;
        }

        return $safe;
    }
}
