<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        // Cria usuários padrão (admin e teste)
        User::updateOrCreate(
            ['email' => 'admin@grafic.local'],
            [
                'name' => 'Administrador',
                'password' => 'admin123',
                'phone' => '+55 00 00000-0000',
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'teste@grafic.local'],
            [
                'name' => 'Usuário de Teste',
                'password' => 'teste123',
                'phone' => '+55 00 00000-0000',
                'is_admin' => false,
            ]
        );
    }
}
