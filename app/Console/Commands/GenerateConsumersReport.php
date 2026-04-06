<?php

namespace App\Console\Commands;

use App\Services\CsvReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature("reports:consumers {filename? : Nome opcional do arquivo CSV}")]
#[Description("Gera um relatório CSV com o total de requisições agrupadas por consumidor.")]
class GenerateConsumersReport extends Command
{
    public function handle(CsvReportService $csvReportService): int
    {
        $filePath = $csvReportService->generateConsumersReport($this->argument("filename"));

        $this->info("Relatório por consumidor gerado com sucesso.");
        $this->line("Arquivo salvo em: " . $filePath);

        return self::SUCCESS;
    }
}
