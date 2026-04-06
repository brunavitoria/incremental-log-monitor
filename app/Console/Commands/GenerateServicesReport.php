<?php

namespace App\Console\Commands;

use App\Services\CsvReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reports:services {filename? : Nome opcional do arquivo CSV}')]
#[Description('Gera um relatório CSV com o total de requisições agrupadas por serviço.')]
class GenerateServicesReport extends Command
{
    public function handle(CsvReportService $csvReportService): int
    {
        $filePath = $csvReportService->generateServicesReport($this->argument('filename'));

        $this->info('Relatório por serviço gerado com sucesso.');
        $this->line('Arquivo salvo em: '.$filePath);

        return self::SUCCESS;
    }
}
