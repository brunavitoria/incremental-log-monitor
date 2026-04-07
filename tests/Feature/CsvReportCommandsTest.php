<?php

namespace Tests\Feature;

use App\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvReportCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_the_consumers_report_csv(): void
    {
        $this->seedLogs();

        $this->artisan('reports:consumers', ['filename' => 'consumers_test.csv'])
            ->expectsOutputToContain('Relatório por consumidor gerado com sucesso.')
            ->assertSuccessful();

        $rows = $this->parseCsv(storage_path('app/reports/consumers_test.csv'));

        $this->assertSame(['consumer_id', 'total_requests'], $rows[0]);
        $this->assertCount(3, $rows); // header + 2 data rows
        $this->assertSame(['consumer-a', '2'], $rows[1]);
        $this->assertSame(['consumer-b', '1'], $rows[2]);
    }

    public function test_it_generates_the_services_report_csv(): void
    {
        $this->seedLogs();

        $this->artisan('reports:services', ['filename' => 'services_test.csv'])
            ->expectsOutputToContain('Relatório por serviço gerado com sucesso.')
            ->assertSuccessful();

        $rows = $this->parseCsv(storage_path('app/reports/services_test.csv'));

        $this->assertSame(['service_name', 'total_requests'], $rows[0]);
        $this->assertCount(3, $rows);
        $this->assertSame(['service-a', '2'], $rows[1]);
        $this->assertSame(['service-b', '1'], $rows[2]);
    }

    public function test_it_generates_the_latencies_report_csv(): void
    {
        $this->seedLogs();

        $this->artisan('reports:latencies', ['filename' => 'latencies_test.csv'])
            ->expectsOutputToContain('Relatório de latências gerado com sucesso.')
            ->assertSuccessful();

        $rows = $this->parseCsv(storage_path('app/reports/latencies_test.csv'));

        $this->assertSame(
            ['service_name', 'avg_latency_proxy', 'avg_latency_gateway', 'avg_latency_request'],
            $rows[0]
        );
        $this->assertCount(3, $rows);
        $this->assertSame(['service-a', '150', '15', '180'], $rows[1]);
        $this->assertSame(['service-b', '300', '30', '360'], $rows[2]);
    }

    public function test_it_generates_empty_csv_when_no_logs_exist(): void
    {
        $this->artisan('reports:consumers', ['filename' => 'empty_consumers_test.csv'])
            ->assertSuccessful();

        $rows = $this->parseCsv(storage_path('app/reports/empty_consumers_test.csv'));

        $this->assertCount(1, $rows); // only header
        $this->assertSame(['consumer_id', 'total_requests'], $rows[0]);
    }

    private function seedLogs(): void
    {
        Log::insert([
            [
                'consumer_id' => 'consumer-a',
                'service_name' => 'service-a',
                'latencies_proxy' => 100,
                'latencies_gateway' => 10,
                'latencies_request' => 120,
                'created_at' => now()->subMinute(),
                'processed_at' => now(),
            ],
            [
                'consumer_id' => 'consumer-a',
                'service_name' => 'service-a',
                'latencies_proxy' => 200,
                'latencies_gateway' => 20,
                'latencies_request' => 240,
                'created_at' => now()->subSeconds(30),
                'processed_at' => now(),
            ],
            [
                'consumer_id' => 'consumer-b',
                'service_name' => 'service-b',
                'latencies_proxy' => 300,
                'latencies_gateway' => 30,
                'latencies_request' => 360,
                'created_at' => now(),
                'processed_at' => now(),
            ],
        ]);
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
