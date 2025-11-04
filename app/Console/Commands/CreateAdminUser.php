<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create 
                            {email=admin@todahgrafica.com.br : Email do admin}
                            {password=admin123 : Senha do admin}';
    
    protected $description = 'Cria ou atualiza usuário admin';

    public function handle()
    {
        $email = trim($this->argument('email'));
        $password = trim($this->argument('password'));
        
        // Buscar ou criar usuário
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $user = new User();
            $user->email = $email;
            $user->name = 'Administrador';
            $user->is_admin = true;
        }
        
        // Atualizar senha - usando Hash::make diretamente para garantir
        // (mesmo que o cast faça hash, vamos garantir que está correto)
        $user->password = Hash::make($password);
        $user->save();
        
        // Verificar se a senha está correta
        $teste = Hash::check($password, $user->password);
        
        $this->info("✅ Usuário admin criado/atualizado!");
        $this->info("   Email: {$user->email}");
        $this->info("   Senha: {$password}");
        $this->info("   Admin: " . ($user->is_admin ? 'Sim' : 'Não'));
        $this->info("   Senha verificada: " . ($teste ? '✅ CORRETA' : '❌ ERRO'));
        
        if (!$teste) {
            $this->error("   ⚠️  AVISO: A senha não passou na verificação!");
        }
        
        $this->newLine();
        $this->info("📋 Instruções de login:");
        $this->info("   1. Acesse: http://localhost:8001/login");
        $this->info("   2. Email: {$email}");
        $this->info("   3. Senha: {$password}");
        $this->info("   4. Certifique-se de não ter espaços antes/depois");
        
        return 0;
    }
}

