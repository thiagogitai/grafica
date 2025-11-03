<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::create([
            'name' => 'Impressão de Livro',
            'description' => 'Livros personalizados com capa dura ou brochura. Impressão em offset de alta qualidade, encadernação profissional.',
            'price' => 45.00,
            'image' => null,
            'template' => Product::templateOptionKey('impressao-de-livro'),
        ]);

        Product::create([
            'name' => 'Impressão em Papel A4',
            'description' => 'Impressão colorida de alta qualidade em papel A4. Ideal para documentos, flyers e materiais promocionais.',
            'price' => 0.50,
            'image' => null,
            'template' => Product::TEMPLATE_FLYER,
        ]);

        Product::create([
            'name' => 'Banner Personalizado',
            'description' => 'Banner personalizado em vinil resistente. Perfeito para eventos, lojas e anúncios.',
            'price' => 25.00,
            'image' => null,
            'template' => Product::TEMPLATE_STANDARD,
        ]);

        Product::create([
            'name' => 'Cartão de Visita',
            'description' => 'Cartões de visita profissionais. Papel couchê 300g, impressão frente e verso.',
            'price' => 15.00,
            'image' => null,
            'template' => Product::TEMPLATE_STANDARD,
        ]);

        Product::create([
            'name' => 'Flyer A5',
            'description' => 'Flyers promocionais em papel offset. Ideal para campanhas de marketing.',
            'price' => 0.20,
            'image' => null,
            'template' => Product::TEMPLATE_FLYER,
        ]);

        Product::create([
            'name' => 'Envelope Personalizado',
            'description' => 'Envelopes personalizados com logotipo. Disponíveis em diversos tamanhos.',
            'price' => 0.30,
            'image' => null,
            'template' => Product::TEMPLATE_STANDARD,
        ]);
    }
}
