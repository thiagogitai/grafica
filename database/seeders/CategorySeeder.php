<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Livro', 'slug' => 'livro', 'image' => 'https://source.unsplash.com/250x150/?book,graphic-design'],
            ['name' => 'Livreto', 'slug' => 'livreto', 'image' => 'https://source.unsplash.com/250x150/?brochure,graphic-design'],
            ['name' => 'Panfleto', 'slug' => 'panfleto', 'image' => 'https://source.unsplash.com/250x150/?flyer,graphic-design'],
            ['name' => 'Cartão de Visita', 'slug' => 'cartao-de-visita', 'image' => 'https://source.unsplash.com/250x150/?business-card,graphic-design'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
