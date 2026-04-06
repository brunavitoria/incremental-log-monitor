<?php

namespace Tests\Feature;

use App\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

        $content = File::get(storage_path('app/reports/consumers_test.csv'));

        $this->assertStringContainsString('consumer_id,total_requests', $content);
        $this->assertStringContainsString('consumer-a,2', $content);
        $this->assertStringContainsString('consumer-b,1', $content);
    }

    public function test_it_generates_the_services_report_csv(): void
    {
        $this->seedLogs();

        $this->artisan('reports:services', ['filename' => 'services_test.csv'])
            ->expectsOutputToContain('Relatório por serviço gerado com sucesso.')
            ->assertSuccessful();

        $content = File::get(storage_path('app/reports/services_test.csv'));

        $this->assertStringContainsString('service_name,total_requests', $content);
        $this->assertStringContainsString('service-a,2', $content);
        $this->assertStringContainsString('service-b,1', $content);
    }

    public function test_it_generates_the_latencies_report_csv(): void
    {
        $this->seedLogs();

        $this->artisan('reports:latencies', ['filename' => 'latencies_test.csv'])
            ->expectsOutputToContain('Relatório de latências gerado com sucesso.')
            ->assertSuccessful();

        $content = File::get(storage_path('app/reports/latencies_test.csv'));

        $this->assertStringContainsString(
            'service_name,avg_latency_proxy,avg_latency_gateway,avg_latency_request',
            $content
        );
        $this->assertStringContainsString('service-a,150,15,180', $content);
        $this->assertStringContainsString('service-b,300,30,360', $content);
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
}
