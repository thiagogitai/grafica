#!/bin/bash
# Script para baixar mapeamento_keys_todos_produtos.json do VPS

echo "📥 Baixando mapeamento_keys_todos_produtos.json do VPS..."
echo ""
echo "Por favor, execute manualmente no VPS:"
echo "  scp root@srv1097663:/www/wwwroot/grafica/mapeamento_keys_todos_produtos.json ."
echo ""
echo "Ou se você já tem SSH configurado, execute:"
echo ""
read -p "Host do VPS (ex: root@srv1097663): " VPS_HOST
read -p "Caminho remoto (ex: /www/wwwroot/grafica): " REMOTE_PATH

if [ -z "$VPS_HOST" ] || [ -z "$REMOTE_PATH" ]; then
    echo "❌ Host ou caminho não informado!"
    exit 1
fi

echo "📥 Baixando arquivo..."
scp "${VPS_HOST}:${REMOTE_PATH}/mapeamento_keys_todos_produtos.json" .

if [ $? -eq 0 ]; then
    echo "✅ Arquivo baixado com sucesso!"
    ls -lh mapeamento_keys_todos_produtos.json
else
    echo "❌ Erro ao baixar arquivo!"
    exit 1
fi

