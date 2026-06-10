<?php

namespace App\Services\Api;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function stream(Builder $query, array $headings, callable $mapRow, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headings, $mapRow): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            $query->chunk(200, function ($rows) use ($handle, $mapRow): void {
                foreach ($rows as $row) {
                    fputcsv($handle, $mapRow($row));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
