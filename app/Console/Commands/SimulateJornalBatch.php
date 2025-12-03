<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PricingService;

class SimulateJornalBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pricing:simulate-jornal {--n=30 : Número de simulações}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa simulações de preço para impressao-de-jornal-de-bairro sem depender de CSRF';

    /**
     * Execute the console command.
     */
    public function handle(PricingService $pricingService): int
    {
        $product = 'impressao-de-jornal-de-bairro';
        $pagesOptions = [
            'Miolo 4 páginas', 'Miolo 8 páginas', 'Miolo 12 páginas', 'Miolo 16 páginas',
            'Miolo 20 páginas', 'Miolo 24 páginas', 'Miolo 32 páginas', 'Miolo 40 páginas', 'Miolo 50 páginas',
        ];
        $quantities = [50, 60, 70, 80, 90, 100];

        $ok = 0;
        $failures = [];
        $runs = (int) $this->option('n');

        for ($i = 1; $i <= $runs; $i++) {
            $payload = [
                'quantity' => $quantities[array_rand($quantities)],
                'formato' => '205x275mm',
                'papel_miolo' => 'Offset 75gr',
                'cores_miolo' => '4 cores frente e verso',
                'paginas_miolo' => $pagesOptions[array_rand($pagesOptions)],
                'acabamento_miolo' => 'Dobrado',
                'acabamento_livro' => 'Grampeado - 2 grampos',
                'extras' => 'Nenhum',
                'frete' => 'Incluso',
                'verificacao_arquivo' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
                'prazo_entrega' => 'Padrão: 8 dias úteis de Produção + tempo de FRETE*',
            ];

            try {
                $result = $pricingService->quote($product, $payload);
                $ok++;
                $this->line("[$i/$runs] OK - qty {$payload['quantity']} ({$payload['paginas_miolo']}) => {$result['price']}");
            } catch (\Throwable $e) {
                $failures[] = "[$i/$runs] {$e->getMessage()}";
                $this->warn("[$i/$runs] FAIL - {$e->getMessage()}");
            }
        }

        $this->info("Total OK: {$ok}/{$runs}");
        if ($failures) {
            $this->error("Falhas:\n" . implode("\n", $failures));
        }

        return $failures ? Command::FAILURE : Command::SUCCESS;
    }
}
