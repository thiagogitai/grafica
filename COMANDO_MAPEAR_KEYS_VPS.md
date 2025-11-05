# Comando para Mapear Keys de Todos os Produtos no VPS

## Execute este comando no VPS:

```bash
cd /www/wwwroot/grafica && python3 mapear_keys_todos_produtos.py
```

## Ou se estiver em outro diretório:

```bash
python3 /www/wwwroot/grafica/mapear_keys_todos_produtos.py
```

## O que o script faz:

1. Acessa cada produto do site matriz
2. Intercepta as requisições da API de pricing
3. Extrai as Keys (hashes) de todas as opções
4. Salva em `mapeamento_keys_todos_produtos.json`

## Tempo estimado:

- ~5-10 minutos (dependendo da velocidade da rede)
- Processa 8 produtos sequencialmente

## Após executar:

O arquivo `mapeamento_keys_todos_produtos.json` será criado na raiz do projeto e o sistema começará a usar automaticamente as Keys mapeadas.

