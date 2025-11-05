#!/bin/bash
# Script para executar mapeamento de Keys no VPS
# Continua rodando mesmo se você desconectar

cd /www/wwwroot/grafica

# Atualizar código
git pull

# Executar em background usando screen (melhor que nohup)
screen -dmS mapeamento_keys bash -c "python3 mapear_keys_todos_produtos.py 2>&1 | tee mapeamento.log"

echo "✅ Script iniciado em background!"
echo ""
echo "Para acompanhar o progresso:"
echo "  screen -r mapeamento_keys"
echo ""
echo "Para sair do screen sem parar o script:"
echo "  Pressione: Ctrl+A depois D"
echo ""
echo "Para ver o log:"
echo "  tail -f mapeamento.log"
echo ""
echo "Para parar o script:"
echo "  screen -X -S mapeamento_keys quit"
echo ""
echo "Verificar se está rodando:"
echo "  ps aux | grep mapear_keys_todos_produtos.py"

