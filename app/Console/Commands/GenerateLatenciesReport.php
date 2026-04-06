<?php

namespace App\Console\Commands;

use App\Services\CsvReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature("reports:latencies {filename? : Nome opcional do arquivo CSV}")]
#[Description("Gera um relatório CSV com as latências médias agrupadas por serviço.")]
class GenerateLatenciesReport extends Command
{
    public function handle(CsvReportService $csvReportService): int
    {
        $filePath = $csvReportService->generateLatenciesReport($this->argument("filename"));

        $this->info("Relatório de latências gerado com sucesso.");
        $this->line("Arquivo salvo em: " . $filePath);

        return self::SUCCESS;
    }
}
