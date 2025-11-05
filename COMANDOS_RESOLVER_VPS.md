# Comandos para resolver problemas no VPS

## 1. Resolver conflito do Git (mapeamento_keys_todos_produtos.json)

```bash
cd /www/wwwroot/grafica

# Fazer backup do arquivo existente
cp mapeamento_keys_todos_produtos.json mapeamento_keys_todos_produtos.json.backup

# Remover do working directory (não deletar, só mover temporariamente)
mv mapeamento_keys_todos_produtos.json /tmp/mapeamento_keys_todos_produtos.json.backup

# Agora fazer o pull
git pull

# Se o arquivo foi criado no repositório, restaurar o backup se necessário
# (mas geralmente o arquivo deve estar no .gitignore)
```

## 2. Verificar e corrigir o template impressao-de-livro.json

```bash
cd /www/wwwroot/grafica

# Verificar se o template está correto (sem "105x148mm (A6)")
grep -n "105x148mm (A6)" resources/data/products/impressao-de-livro.json

# Se encontrar, verificar a versão do arquivo
git log --oneline resources/data/products/impressao-de-livro.json | head -5

# Verificar se o campo é "formato_miolo_paginas" e não "formato"
grep -n '"name": "formato"' resources/data/products/impressao-de-livro.json
```

## 3. Se o template estiver errado, atualizar manualmente

```bash
# Verificar se o arquivo está no repositório
git checkout resources/data/products/impressao-de-livro.json

# Ou fazer pull forçado
git pull --force
```

## 4. Limpar cache novamente após correções

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 5. Verificar se o frontend está enviando o campo correto

```bash
# Verificar logs para ver qual campo está sendo enviado
tail -n 50 storage/logs/laravel.log | grep -i "formato\|formato_miolo"
```

## Comandos em sequência (copiar e colar tudo):

```bash
cd /www/wwwroot/grafica && \
cp mapeamento_keys_todos_produtos.json /tmp/mapeamento_keys_backup.json 2>/dev/null || true && \
rm -f mapeamento_keys_todos_produtos.json && \
git pull && \
git checkout resources/data/products/impressao-de-livro.json && \
php artisan cache:clear && \
php artisan config:clear && \
echo "✅ Atualização concluída!"
```

