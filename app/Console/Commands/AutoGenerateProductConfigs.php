<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class AutoGenerateProductConfigs extends Command
{
    protected $signature = 'products:auto-generate-configs 
                            {--url= : URL específica para analisar}
                            {--force : Forçar regeneração mesmo se já existir}';
    
    protected $description = 'Analisa produtos e gera arquivos JSON de configuração automaticamente';

    /**
     * URLs dos produtos para análise automática
     */
    private const PRODUCT_URLS = [
        'impressao-de-panfleto' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-panfleto',
        'impressao-de-apostila' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila',
        'impressao-online-de-livretos-personalizados' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-online-de-livretos-personalizados',
        'impressao-de-revista' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista',
        'impressao-de-tabloide' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide',
        'impressao-de-jornal-de-bairro' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-jornal-de-bairro',
        'impressao-de-guia-de-bairro' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-guia-de-bairro',
    ];

    public function handle()
    {
        $this->info('🔍 Iniciando geração automática de configurações...');
        
        $url = $this->option('url');
        $force = $this->option('force');
        
        if ($url) {
            // Analisar URL específica
            $slug = $this->extractSlugFromUrl($url);
            if (!$slug) {
                $this->error('Não foi possível extrair slug da URL');
                return 1;
            }
            $this->processProduct($slug, $url, $force);
        } else {
            // Processar todos os produtos
            foreach (self::PRODUCT_URLS as $slug => $productUrl) {
                $this->processProduct($slug, $productUrl, $force);
            }
        }
        
        $this->info('✅ Processo concluído!');
        return 0;
    }

    private function processProduct(string $slug, string $url, bool $force)
    {
        $configPath = base_path("resources/data/products/{$slug}.json");
        
        if (file_exists($configPath) && !$force) {
            $this->warn("⏭️  {$slug}: Config já existe (use --force para regenerar)");
            return;
        }
        
        $this->info("📊 Analisando: {$slug}");
        
        // Executar script Python de análise
        $scriptPath = base_path('scrapper/analisar_produto.py');
        $process = Process::timeout(60)->run("python \"{$scriptPath}\" \"{$url}\"");
        
        if (!$process->isSuccessful()) {
            $this->error("❌ Erro ao analisar {$slug}: " . $process->getErrorOutput());
            return;
        }
        
        // Verificar se o mapeamento foi criado
        $mapeamentoPath = base_path("scrapper/{$slug}_mapeamento.json");
        if (!file_exists($mapeamentoPath)) {
            $this->error("❌ Mapeamento não encontrado para {$slug}");
            return;
        }
        
        // Gerar JSON de configuração
        $this->info("📝 Gerando JSON de configuração...");
        $gerarScript = base_path('scrapper/criar_todos_configs.py');
        $process = Process::timeout(30)->run("python \"{$gerarScript}\"");
        
        if ($process->isSuccessful()) {
            if (file_exists($configPath)) {
                $this->info("✅ {$slug}: Config gerado com sucesso!");
            } else {
                $this->error("❌ {$slug}: Config não foi criado");
            }
        } else {
            $this->error("❌ Erro ao gerar config para {$slug}: " . $process->getErrorOutput());
        }
    }

    private function extractSlugFromUrl(string $url): ?string
    {
        $parts = explode('/', $url);
        $lastPart = end($parts);
        
        foreach (self::PRODUCT_URLS as $slug => $productUrl) {
            if (str_contains($productUrl, $lastPart)) {
                return $slug;
            }
        }
        
        return Str::slug($lastPart);
    }
}

