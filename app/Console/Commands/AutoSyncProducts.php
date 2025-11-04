<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AutoSyncProducts extends Command
{
    protected $signature = 'products:auto-sync 
                            {--template=config:auto : Template a usar}
                            {--update-existing : Atualizar produtos existentes}';
    
    protected $description = 'Sincroniza produtos do banco com arquivos JSON existentes automaticamente';

    /**
     * Produtos conhecidos que devem ter template config
     */
    private const KNOWN_PRODUCTS = [
        'impressao-de-panfleto',
        'impressao-de-apostila',
        'impressao-online-de-livretos-personalizados',
        'impressao-de-revista',
        'impressao-de-tabloide',
        'impressao-de-jornal-de-bairro',
        'impressao-de-guia-de-bairro',
        'impressao-de-livro',
    ];

    public function handle()
    {
        $this->info('🔄 Sincronizando produtos com arquivos JSON...');
        
        $template = $this->option('template');
        $updateExisting = $this->option('update-existing');
        
        $configDir = base_path('resources/data/products');
        $files = glob($configDir . '/*.json');
        
        $this->info("📁 Encontrados " . count($files) . " arquivos JSON");
        
        foreach ($files as $file) {
            $slug = basename($file, '.json');
            $this->info("📦 Processando: {$slug}");
            
            // Verificar se produto existe no banco
            $product = Product::where('name', 'like', '%' . str_replace('-', ' ', $slug) . '%')
                ->orWhere('name', 'like', '%' . ucwords(str_replace('-', ' ', $slug)) . '%')
                ->first();
            
            if (!$product) {
                // Tentar criar produto automaticamente
                $this->warn("⚠️  Produto não encontrado no banco para: {$slug}");
                $this->info("   Criando produto automaticamente...");
                
                $product = Product::create([
                    'name' => $this->slugToName($slug),
                    'description' => "Impressão de " . $this->slugToName($slug),
                    'price' => 0,
                    'template' => $template,
                ]);
                
                $this->info("✅ Produto criado: {$product->name} (ID: {$product->id})");
            } else {
                if ($updateExisting) {
                    // Atualizar template se necessário
                    if ($product->template !== $template && in_array($slug, self::KNOWN_PRODUCTS)) {
                        $product->template = $template;
                        $product->save();
                        $this->info("✅ Template atualizado para: {$product->name}");
                    }
                } else {
                    $this->info("✓ Produto já existe: {$product->name}");
                }
            }
        }
        
        $this->info('✅ Sincronização concluída!');
        return 0;
    }

    private function slugToName(string $slug): string
    {
        $name = str_replace('-', ' ', $slug);
        $name = str_replace(['impressao de ', 'impressao-online-de-'], '', $name);
        return ucwords($name);
    }
}

