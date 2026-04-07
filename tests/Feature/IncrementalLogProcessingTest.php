<?php

namespace Tests\Feature;

use App\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class IncrementalLogProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_only_valid_lines_and_updates_processing_state(): void
    {
        $filePath = $this->makeLogFile([
            $this->makeValidLogLine('consumer-1', 'service-a', 100, 10, 120, 1712400000000),
            json_encode([
                'service' => ['name' => 'service-a'],
                'latencies' => ['proxy' => 90, 'gateway' => 5, 'request' => 110],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 1')
            ->expectsOutputToContain('Linhas ignoradas: 1')
            ->assertSuccessful();

        $this->assertDatabaseCount('logs', 1);
        $this->assertDatabaseHas('logs', [
            'consumer_id' => 'consumer-1',
            'service_name' => 'service-a',
            'latencies_proxy' => 100,
            'latencies_gateway' => 10,
            'latencies_request' => 120,
        ]);

        $this->assertDatabaseHas('processing_states', [
            'file_path' => realpath($filePath) ?: $filePath,
            'last_processed_line' => 2,
        ]);
    }

    public function test_it_only_processes_new_lines_when_executed_again(): void
    {
        $filePath = $this->makeLogFile([
            $this->makeValidLogLine('consumer-1', 'service-a', 100, 10, 120, 1712400000000),
            $this->makeValidLogLine('consumer-2', 'service-b', 200, 20, 240, 1712400100000),
        ]);

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 2')
            ->assertSuccessful();

        File::append(
            $filePath,
            $this->makeValidLogLine('consumer-3', 'service-c', 300, 30, 360, 1712400200000).PHP_EOL
        );

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 1')
            ->expectsOutputToContain('Linhas ignoradas: 0')
            ->assertSuccessful();

        $this->assertSame(3, Log::count());
        $this->assertDatabaseHas('processing_states', [
            'file_path' => realpath($filePath) ?: $filePath,
            'last_processed_line' => 3,
        ]);
    }

    public function test_it_skips_lines_with_invalid_json(): void
    {
        $filePath = $this->makeLogFile([
            '{invalid json',
            'not even close to json',
            $this->makeValidLogLine('consumer-1', 'service-a', 100, 10, 120, 1712400000000),
            '',
            '{"incomplete": true',
        ]);

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 1')
            ->expectsOutputToContain('Linhas ignoradas: 4')
            ->assertSuccessful();

        $this->assertDatabaseCount('logs', 1);
        $this->assertDatabaseHas('logs', ['consumer_id' => 'consumer-1']);
    }

    public function test_it_handles_empty_file_gracefully(): void
    {
        $filePath = $this->makeLogFile([]);

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 0')
            ->expectsOutputToContain('Linhas ignoradas: 0')
            ->assertSuccessful();

        $this->assertDatabaseCount('logs', 0);
    }

    public function test_it_fails_when_file_does_not_exist(): void
    {
        $this->artisan('logs:processing', ['file_path' => '/tmp/non_existent_file_'.uniqid().'.log'])
            ->expectsOutputToContain('não existe ou não é legível')
            ->assertFailed();

        $this->assertDatabaseCount('logs', 0);
        $this->assertDatabaseCount('processing_states', 0);
    }

    public function test_reprocessing_without_new_lines_does_not_insert_anything(): void
    {
        $filePath = $this->makeLogFile([
            $this->makeValidLogLine('consumer-1', 'service-a', 100, 10, 120, 1712400000000),
        ]);

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 1')
            ->assertSuccessful();

        $this->assertDatabaseCount('logs', 1);

        $this->artisan('logs:processing', ['file_path' => $filePath])
            ->expectsOutputToContain('Registros salvos: 0')
            ->expectsOutputToContain('Linhas ignoradas: 0')
            ->assertSuccessful();

        $this->assertDatabaseCount('logs', 1);
        $this->assertDatabaseHas('processing_states', [
            'file_path' => realpath($filePath) ?: $filePath,
            'last_processed_line' => 1,
        ]);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function makeLogFile(array $lines): string
    {
        $directory = storage_path('framework/testing');

        File::ensureDirectoryExists($directory);

        $filePath = $directory.'/logs_'.uniqid().'.log';
        $content = $lines !== [] ? implode(PHP_EOL, $lines).PHP_EOL : '';
        File::put($filePath, $content);

        return $filePath;
    }

    private function makeValidLogLine(
        string $consumerId,
        string $serviceName,
        int $proxy,
        int $gateway,
        int $request,
        int $startedAt
    ): string {
        return json_encode([
            'authenticated_entity' => [
                'consumer_id' => [
                    'uuid' => $consumerId,
                ],
            ],
            'service' => [
                'name' => $serviceName,
            ],
            'latencies' => [
                'proxy' => $proxy,
                'gateway' => $gateway,
                'request' => $request,
            ],
            'started_at' => $startedAt,
        ], JSON_THROW_ON_ERROR);
    }
}
