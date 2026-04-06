<?php

namespace App\Console\Commands;

use App\Models\Log;
use App\Models\ProcessingState;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('logs:processing {file_path : O caminho para o arquivo de log a ser processado.}')]
#[Description('Processa um arquivo de log incrementalmente, atualizando o banco de dados com novas entradas desde a última linha processada.')]
class IncrementalLogProcessing extends Command
{
    private const BATCH_SIZE = 20;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file_path');

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $this->error("O arquivo '{$filePath}' não existe ou não é legível.");

            return self::FAILURE;
        }

        $normalizedPath = realpath($filePath) ?: $filePath;
        $fileName = basename($normalizedPath);

        $this->info("Iniciando o processamento incremental do arquivo de log '{$fileName}'...");

        $state = ProcessingState::firstOrCreate(
            ['file_path' => $normalizedPath],
            ['last_processed_line' => 0]
        );

        $lastProcessedLine = $state->last_processed_line;
        $file = fopen($normalizedPath, 'r');

        if ($file === false) {
            $this->error("Não foi possível abrir o arquivo '{$fileName}' para leitura.");

            return self::FAILURE;
        }

        $currentLine = 0;
        $savedLogs = 0;
        $skippedLogs = 0;
        $batch = [];

        while (($line = fgets($file)) !== false) {
            $currentLine++;

            if ($currentLine <= $lastProcessedLine) {
                continue;
            }

            $logData = $this->parseLogLine($line, $currentLine);

            if ($logData === null) {
                $skippedLogs++;

                continue;
            }

            $batch[] = $logData;

            if (count($batch) >= self::BATCH_SIZE) {
                $savedLogs += $this->flushBatch($batch, $state, $currentLine);
            }
        }

        fclose($file);

        if ($batch !== []) {
            $savedLogs += $this->flushBatch($batch, $state, $currentLine);
        }

        $state->update([
            'last_processed_line' => $currentLine,
            'last_processed_at' => now(),
        ]);

        $this->info("Processamento do arquivo de log '{$fileName}' concluído.");
        $this->line("Registros salvos: {$savedLogs}");
        $this->line("Linhas ignoradas: {$skippedLogs}");

        return self::SUCCESS;
    }

    private function parseLogLine(string $log, int $lineNumber): ?array
    {
        $data = json_decode($log, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn("Linha {$lineNumber} ignorada: JSON inválido - ".json_last_error_msg());

            return null;
        }

        $parsedLog = [
            'consumer_id' => $this->normalizeStringValue(
                data_get($data, 'authenticated_entity.consumer_id.uuid')
                    ?? data_get($data, 'authenticated_entity.consumer_id')
            ),
            'service_name' => $this->normalizeStringValue(data_get($data, 'service.name')),
            'latencies_proxy' => data_get($data, 'latencies.proxy'),
            'latencies_gateway' => data_get($data, 'latencies.gateway'),
            'latencies_request' => data_get($data, 'latencies.request'),
            'created_at' => $this->parseStartedAt(data_get($data, 'started_at')),
            'processed_at' => now(),
        ];

        $missingFields = $this->missingRequiredFields($parsedLog);

        if ($missingFields !== []) {
            $this->warn('Linha '.$lineNumber.' ignorada: campos obrigatórios ausentes ou inválidos ('.implode(', ', $missingFields).').');

            return null;
        }

        return $parsedLog;
    }

    private function missingRequiredFields(array $parsedLog): array
    {
        $missingFields = [];

        foreach (['consumer_id', 'service_name', 'created_at'] as $field) {
            if (
                ($parsedLog[$field] ?? null) === null ||
                $parsedLog[$field] === ''
            ) {
                $missingFields[] = $field;
            }
        }

        foreach (['latencies_proxy', 'latencies_gateway', 'latencies_request'] as $field) {
            if (! is_numeric($parsedLog[$field] ?? null)) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    private function flushBatch(array &$batch, ProcessingState $state, int $currentLine): int
    {
        if ($batch === []) {
            return 0;
        }

        Log::insert($batch);

        $inserted = count($batch);

        $state->update([
            'last_processed_line' => $currentLine,
            'last_processed_at' => now(),
        ]);

        $this->line("Lote processado: {$inserted} registros até a linha {$currentLine}.");

        $batch = [];

        return $inserted;
    }

    private function normalizeStringValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return trim($value) !== '' ? trim($value) : null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }

    private function parseStartedAt(mixed $startedAt): ?Carbon
    {
        if (! is_numeric($startedAt)) {
            return null;
        }

        $timestamp = (int) $startedAt;

        return $timestamp > 9999999999
            ? Carbon::createFromTimestampMs($timestamp)
            : Carbon::createFromTimestamp($timestamp);
    }
}
