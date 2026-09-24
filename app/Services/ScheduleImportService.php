<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;
use Throwable;

class ScheduleImportService
{
    /**
     * @return array<int, array<string, string|null>>
     */
    public function parseUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Invalid uploaded file path.');
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->parseCsv($path);
        }

        if ($extension === 'xlsx') {
            return $this->parseXlsx($path);
        }

        if ($extension === 'xls') {
            return $this->parseXls($path);
        }

        throw new \RuntimeException('Unsupported file format.');
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $rows = [];
        $header = null;

        try {
            while (($data = fgetcsv($handle)) !== false) {
                if ($data === [null] || $data === []) {
                    continue;
                }

                if ($header === null) {
                    $header = $this->normalizeHeaderRow($data);
                    continue;
                }

                $rows[] = $this->combineRow($header, $data);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseXlsx(string $path): array
    {
        $xlsx = SimpleXLSX::parse($path);
        if (! $xlsx) {
            throw new \RuntimeException('Unable to parse XLSX file.');
        }

        return $this->parseGridRows($xlsx->rows());
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseXls(string $path): array
    {
        $xls = SimpleXLS::parseFile($path);
        if (! $xls) {
            throw new \RuntimeException('Unable to parse XLS file.');
        }

        return $this->parseGridRows($xls->rows());
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     * @return array<int, array<string, string|null>>
     */
    private function parseGridRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $header = null;
        $output = [];

        foreach ($rows as $row) {
            if ($header === null) {
                $header = $this->normalizeHeaderRow($row);
                continue;
            }

            $output[] = $this->combineRow($header, $row);
        }

        return $output;
    }

    /**
     * @param array<int, mixed> $headerRow
     * @return array<int, string>
     */
    private function normalizeHeaderRow(array $headerRow): array
    {
        $normalized = [];

        foreach ($headerRow as $index => $value) {
            $text = trim((string) $value);
            if ((int) $index === 0) {
                $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
            }

            $key = strtolower($text);
            $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';
            $key = trim($key, '_');
            $normalized[] = $key !== '' ? $key : 'column_'.($index + 1);
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $header
     * @param array<int, mixed> $data
     * @return array<string, string|null>
     */
    private function combineRow(array $header, array $data): array
    {
        $result = [];

        foreach ($header as $index => $key) {
            $value = $data[$index] ?? null;
            $stringValue = $value === null ? null : trim((string) $value);
            $result[$key] = $stringValue === '' ? null : $stringValue;
        }

        return $result;
    }
}
