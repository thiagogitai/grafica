# 📥 Como Baixar as Keys do VPS

## Opção 1: Via Navegador (Mais Simples)

1. No VPS, adicione a rota temporária (já está no código):
   ```bash
   cd /www/wwwroot/grafica
   git pull
   ```

2. Acesse no navegador:
   ```
   https://seusite.com.br/download-keys-mapping
   ```
   
   O arquivo será baixado automaticamente como `mapeamento_keys_todos_produtos.json`

3. Salve o arquivo na pasta do projeto local (mesma pasta onde está este README)

## Opção 2: Via PHP (Script)

1. Edite `baixar_keys_http.php` e altere a URL:
   ```php
   $url_vps = 'https://seusite.com.br/download-keys-mapping';
   ```

2. Execute:
   ```bash
   php baixar_keys_http.php
   ```

## Opção 3: Via cURL (Linha de Comando)

```bash
curl -o mapeamento_keys_todos_produtos.json https://seusite.com.br/download-keys-mapping
```

## Opção 4: Via PowerShell (Windows)

```powershell
Invoke-WebRequest -Uri "https://seusite.com.br/download-keys-mapping" -OutFile "mapeamento_keys_todos_produtos.json"
```

## Depois de Baixar

Execute o teste local:

```bash
php testar_precos_livro_local.php
```

Este script vai:
- ✅ Verificar quais opções do template têm Keys
- ❌ Mostrar quais opções estão faltando Keys
- 💡 Sugerir Keys similares
- 🧪 Testar a API com opções válidas

## ⚠️ IMPORTANTE

Após baixar as Keys e corrigir o template, **REMOVA a rota `/download-keys-mapping`** do `routes/web.php` por segurança!

