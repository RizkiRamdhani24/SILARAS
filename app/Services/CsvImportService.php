<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvImportService
{
    public function template(string $filename, array $headers, bool $withBom = true): StreamedResponse
    {
        return response()->streamDownload(static function () use ($headers, $withBom): void {
            $stream = fopen('php://output', 'wb');
            if ($withBom) {
                fwrite($stream, "\xEF\xBB\xBF");
            }
            fputcsv($stream, $headers, ',', '"', '');
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function read(UploadedFile $file, array $expectedHeaders): array
    {
        $path = $file->getRealPath();
        $handle = $path === false ? false : fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak dapat dibaca.',
            ]);
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            if (! is_array($header)) {
                throw ValidationException::withMessages([
                    'file' => 'File CSV kosong atau tidak memiliki header.',
                ]);
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $header = array_map(static fn ($value): string => trim((string) $value), $header);
            if ($header !== $expectedHeaders) {
                throw ValidationException::withMessages([
                    'file' => 'Header CSV harus tepat: '.implode(',', $expectedHeaders).'.',
                ]);
            }

            $rows = [];
            $line = 1;
            while (($fields = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $line++;
                if ($fields === [null] || $fields === []) {
                    continue;
                }

                if (count($fields) !== count($expectedHeaders)) {
                    throw ValidationException::withMessages([
                        'file' => "Baris {$line} harus memiliki tepat ".count($expectedHeaders).' kolom.',
                    ]);
                }

                $values = array_map(static fn ($value): string => trim((string) $value), $fields);
                if (count(array_filter($values, static fn (string $value): bool => $value !== '')) === 0) {
                    continue;
                }

                $rows[] = [
                    'line' => $line,
                    'values' => array_combine($expectedHeaders, $values),
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak memiliki baris data.',
            ]);
        }

        return $rows;
    }
}
