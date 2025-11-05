# Comando para Executar no VPS

## Execute este comando no VPS:

```bash
cd /www/wwwroot/grafica && git pull && python3 mapear_keys_todos_produtos.py
```

## Ou se quiser executar em background (para não perder se desconectar):

```bash
cd /www/wwwroot/grafica && git pull && nohup python3 mapear_keys_todos_produtos.py > mapeamento.log 2>&1 &
```

## Para acompanhar o progresso em background:

```bash
tail -f mapeamento.log
```

## Para verificar o arquivo salvo:

```bash
cat mapeamento_keys_todos_produtos.json
```

## Para parar o processo (se estiver em background):

```bash
pkill -f mapear_keys_todos_produtos.py
```

## Nota importante:

- O script salva automaticamente após cada produto
- Se parar, os produtos já processados não serão perdidos
- O arquivo `mapeamento_keys_todos_produtos.json` é atualizado continuamente

