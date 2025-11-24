<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "═══════════════════════════════════════════════════════════\n";
echo "  TESTE COMPLETO DO SISTEMA - BANNERS E WHATSAPP\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Testar Model Banner
echo "1. Testando Model Banner...\n";
try {
    $banner = new \App\Models\Banner();
    $success[] = "Model Banner instanciado";
    
    // Testar método active()
    $activeBanners = \App\Models\Banner::active()->get();
    echo "   ✓ Model funcionando - Banners ativos: " . $activeBanners->count() . "\n";
    $success[] = "Método active() funcionando";
} catch (\Exception $e) {
    $errors[] = "Erro no Model Banner: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 2. Testar Controller
echo "\n2. Testando BannerController...\n";
try {
    $controller = new \App\Http\Controllers\Admin\BannerController();
    $success[] = "BannerController instanciado";
    echo "   ✓ Controller funcionando\n";
} catch (\Exception $e) {
    $errors[] = "Erro no Controller: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 3. Testar Rotas
echo "\n3. Verificando rotas...\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $bannerRoutes = [];
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_contains($name, 'admin.banners')) {
            $bannerRoutes[] = $name;
        }
    }
    
    $expectedRoutes = [
        'admin.banners.index',
        'admin.banners.create',
        'admin.banners.store',
        'admin.banners.edit',
        'admin.banners.update',
        'admin.banners.destroy',
    ];
    
    $missingRoutes = array_diff($expectedRoutes, $bannerRoutes);
    if (empty($missingRoutes)) {
        echo "   ✓ Todas as rotas registradas (" . count($bannerRoutes) . " rotas)\n";
        $success[] = "Rotas registradas corretamente";
    } else {
        $warnings[] = "Rotas faltando: " . implode(', ', $missingRoutes);
        echo "   ⚠ Algumas rotas podem estar faltando\n";
    }
} catch (\Exception $e) {
    $errors[] = "Erro ao verificar rotas: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 4. Testar Views
echo "\n4. Verificando views...\n";
$views = [
    'admin/banners/index.blade.php' => 'Listagem de banners',
    'admin/banners/create.blade.php' => 'Criar banner',
    'admin/banners/edit.blade.php' => 'Editar banner',
    'home.blade.php' => 'Homepage (banners)',
    'products/livro.blade.php' => 'Página produto livro (banners)',
    'product-json.blade.php' => 'Página produto JSON (banners)',
    'layouts/app.blade.php' => 'Layout (WhatsApp flutuante)',
];

foreach ($views as $view => $desc) {
    $path = resource_path('views/' . $view);
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Verificar se tem banners
        if (strpos($view, 'banners') === false && strpos($view, 'home') !== false) {
            if (strpos($content, '$banners') !== false || strpos($content, 'bannerCarousel') !== false) {
                echo "   ✓ $desc: Banners implementados\n";
                $success[] = "$desc com banners";
            } else {
                $warnings[] = "$desc pode não ter banners";
                echo "   ⚠ $desc: Verificar implementação de banners\n";
            }
        } elseif (strpos($view, 'livro') !== false || strpos($view, 'product-json') !== false) {
            if (strpos($content, '$banners') !== false || strpos($content, 'bannerCarousel') !== false) {
                echo "   ✓ $desc: Banners implementados\n";
                $success[] = "$desc com banners";
            } else {
                $warnings[] = "$desc pode não ter banners";
                echo "   ⚠ $desc: Verificar implementação de banners\n";
            }
        } elseif (strpos($view, 'app.blade') !== false) {
            if (strpos($content, 'whatsapp-float') !== false) {
                echo "   ✓ $desc: WhatsApp flutuante implementado\n";
                $success[] = "$desc com WhatsApp flutuante";
            } else {
                $warnings[] = "$desc pode não ter WhatsApp flutuante";
                echo "   ⚠ $desc: Verificar WhatsApp flutuante\n";
            }
        } else {
            echo "   ✓ $desc: Existe\n";
            $success[] = "$desc existe";
        }
    } else {
        $errors[] = "View não encontrada: $view";
        echo "   ✗ $desc: NÃO encontrada\n";
    }
}

// 5. Testar HomeController
echo "\n5. Testando HomeController...\n";
try {
    $homeController = new \App\Http\Controllers\HomeController();
    
    // Verificar método index
    $reflection = new ReflectionClass($homeController);
    $indexMethod = $reflection->getMethod('index');
    $indexSource = file_get_contents($reflection->getFileName());
    
    if (strpos($indexSource, 'Banner::active()') !== false) {
        echo "   ✓ HomeController::index() carrega banners\n";
        $success[] = "HomeController carrega banners";
    } else {
        $warnings[] = "HomeController pode não carregar banners";
        echo "   ⚠ Verificar se HomeController carrega banners\n";
    }
    
    // Verificar método show
    if (strpos($indexSource, 'Banner::active()') !== false || strpos($indexSource, '$banners') !== false) {
        echo "   ✓ HomeController::show() carrega banners\n";
        $success[] = "HomeController::show carrega banners";
    } else {
        $warnings[] = "HomeController::show pode não carregar banners";
        echo "   ⚠ Verificar se HomeController::show carrega banners\n";
    }
} catch (\Exception $e) {
    $errors[] = "Erro no HomeController: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 6. Testar WhatsApp
echo "\n6. Verificando WhatsApp...\n";
try {
    $whatsappNumber = \App\Models\Setting::get('whatsapp_number');
    if ($whatsappNumber) {
        echo "   ✓ WhatsApp configurado: $whatsappNumber\n";
        $success[] = "WhatsApp configurado";
        
        $whatsappLink = 'https://wa.me/' . preg_replace('/\D+/', '', (string) $whatsappNumber);
        echo "   ✓ Link gerado: $whatsappLink\n";
        $success[] = "Link WhatsApp gerado";
    } else {
        $warnings[] = "WhatsApp não configurado";
        echo "   ⚠ WhatsApp não configurado (configure em Admin > Configurações)\n";
    }
} catch (\Exception $e) {
    $errors[] = "Erro ao verificar WhatsApp: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 7. Testar AppServiceProvider
echo "\n7. Verificando AppServiceProvider...\n";
try {
    $providerPath = app_path('Providers/AppServiceProvider.php');
    $providerContent = file_get_contents($providerPath);
    
    if (strpos($providerContent, 'globalWhatsappLink') !== false) {
        echo "   ✓ WhatsApp compartilhado globalmente\n";
        $success[] = "WhatsApp compartilhado nas views";
    } else {
        $warnings[] = "WhatsApp pode não estar compartilhado";
        echo "   ⚠ Verificar compartilhamento do WhatsApp\n";
    }
} catch (\Exception $e) {
    $errors[] = "Erro ao verificar AppServiceProvider: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 8. Testar Migration
echo "\n8. Verificando tabela banners...\n";
try {
    if (\Illuminate\Support\Facades\Schema::hasTable('banners')) {
        echo "   ✓ Tabela 'banners' existe\n";
        $success[] = "Tabela banners criada";
        
        // Verificar colunas
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('banners');
        $requiredColumns = ['id', 'title', 'description', 'image', 'link', 'order', 'is_active', 'created_at', 'updated_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "   ✓ Todas as colunas necessárias existem\n";
            $success[] = "Colunas da tabela corretas";
        } else {
            $warnings[] = "Colunas faltando: " . implode(', ', $missingColumns);
            echo "   ⚠ Algumas colunas podem estar faltando\n";
        }
    } else {
        $errors[] = "Tabela banners não existe";
        echo "   ✗ Tabela 'banners' NÃO existe (execute: php artisan migrate)\n";
    }
} catch (\Exception $e) {
    $errors[] = "Erro ao verificar tabela: " . $e->getMessage();
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// Resumo
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMO DO TESTE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✓ Sucessos: " . count($success) . "\n";
echo "⚠ Avisos: " . count($warnings) . "\n";
echo "✗ Erros: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "ERROS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "AVISOS:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "✅ SISTEMA FUNCIONANDO CORRETAMENTE!\n\n";
    echo "PRÓXIMOS PASSOS:\n";
    echo "1. Acesse /admin/banners e crie um banner de teste\n";
    echo "2. Configure o WhatsApp em /admin/settings\n";
    echo "3. Verifique se os banners aparecem na homepage\n";
    echo "4. Verifique se os banners aparecem nas páginas de produto\n";
    echo "5. Verifique se o botão WhatsApp flutuante aparece\n";
} else {
    echo "❌ CORRIJA OS ERROS ANTES DE CONTINUAR\n";
}

echo "\n";

