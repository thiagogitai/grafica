<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class DefaultProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'Impressao de Livro',
            'Impressao de Revista',
            'Impressao de Apostila',
            'Impressao de Jornal de Bairro',
            'Impressao de Tabloide',
            'Impressao de Flyer',
            'Impressao Cartao de Visita',
        ];

        foreach ($products as $name) {
            Product::updateOrCreate(
                ['name' => $name],
                [
                    'description' => 'Produto configuravel via JSON',
                    'price' => 0,
                    'template' => Product::TEMPLATE_CONFIG_AUTO,
                    'request_only' => false,
                    'markup_percentage' => 0,
                ]
            );
        }
    }
}
