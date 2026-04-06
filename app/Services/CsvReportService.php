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

    public function generateServicesReport(?string $filename = null): string
    {
        $filename ??= 'services_report_' . now()->format('Ymd_His') . '.csv';

        $rows = Log::query()
            ->select('service_name', DB::raw('COUNT(*) as total_requests'))
            ->groupBy('service_name')
            ->orderBy('service_name')
            ->get()
            ->map(fn (Log $log) => [
                $log->service_name,
                (int) $log->total_requests,
            ])
            ->all();

        return $this->writeCsv(
            'reports/' . $filename,
            [
                'service_name',
                'total_requests'
            ],
            $rows
        );
    }

    public function generateLatenciesReport(?string $filename = null): string
    {
        $filename ??= 'latencies_report_' . now()->format('Ymd_His') . '.csv';

        $rows = Log::query()
            ->select(
                'service_name',
                DB::raw('ROUND(AVG(latencies_proxy), 2) as avg_latency_proxy'),
                DB::raw('ROUND(AVG(latencies_gateway), 2) as avg_latency_gateway'),
                DB::raw('ROUND(AVG(latencies_request), 2) as avg_latency_request')
            )
            ->groupBy('service_name')
            ->orderBy('service_name')
            ->get()
            ->map(fn (Log $log) => [
                $log->service_name,
                (float) $log->avg_latency_proxy,
                (float) $log->avg_latency_gateway,
                (float) $log->avg_latency_request,
            ])
            ->all();

        return $this->writeCsv(
            'reports/' . $filename,
            [
                'service_name',
                'avg_latency_proxy',
                'avg_latency_gateway',
                'avg_latency_request'
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
