<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use League\Csv\Writer;
use SplTempFileObject;

class CsvReportService
{
    public function generateConsumersReport(?string $filename = null): string
    {
        $filename ??= 'consumers_report_' . now()->format('Ymd_His') . '.csv';

        $rows = Log::query()
            ->select('consumer_id', DB::raw('COUNT(*) as total_requests'))
            ->groupBy('consumer_id')
            ->orderBy('consumer_id')
            ->get()
            ->map(fn (Log $log) => [
                $log->consumer_id,
                (int) $log->total_requests,
            ])
            ->all();

        return $this->writeCsv(
            'reports/' . $filename,
            [
                'consumer_id',
                'total_requests'
            ],
            $rows
        );
    }

    private function writeCsv(string $path, array $header, array $rows): string
    {
        $csv = Writer::from(new SplTempFileObject());
        $csv->insertOne($header);
        $csv->insertAll($rows);

        $fullPath = storage_path('app/' . $path);

        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $csv->toString());

        return $fullPath;
    }
}
