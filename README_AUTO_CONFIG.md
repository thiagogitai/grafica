# 🚀 Sistema Automático de Configuração de Produtos

## Como Funciona

O sistema agora é **100% AUTOMÁTICO**! Não precisa criar JSONs manualmente.

### Opção 1: Geração Automática Completa (Recomendado)

Execute o script que faz tudo automaticamente:

```bash
python scrapper\gerar_tudo_automatico.py
```

Este script:
1. ✅ Analisa cada produto no site
2. ✅ Gera o arquivo JSON automaticamente
3. ✅ Pronto para usar!

### Opção 2: Usar Comando Artisan

```bash
# Gerar todos os configs automaticamente
php artisan products:auto-generate-configs

# Sincronizar produtos do banco com JSONs existentes
php artisan products:auto-sync

# Forçar regeneração
php artisan products:auto-generate-configs --force
```

### Opção 3: Auto-Detecção no Sistema

Quando você cria um produto no banco com:
- **Template**: `config:auto`
- O sistema **automaticamente** procura o JSON baseado no nome do produto

**Exemplo:**
- Produto: "Impressão de Panfleto"
- Slug gerado: `impressao-de-panfleto`
- Sistema procura: `resources/data/products/impressao-de-panfleto.json`
- ✅ Se existir, carrega automaticamente!

## Produtos Configurados

Todos estes produtos já têm JSON gerado:

- ✅ impressao-de-panfleto
- ✅ impressao-de-apostila
- ✅ impressao-online-de-livretos-personalizados
- ✅ impressao-de-revista
- ✅ impressao-de-tabloide
- ✅ impressao-de-jornal-de-bairro
- ✅ impressao-de-guia-de-bairro
- ✅ impressao-de-livro

## Como Adicionar Novo Produto

1. **Criar produto no banco:**
   - Nome: "Impressão de [Nome]"
   - Template: `config:auto`

2. **Executar geração:**
   ```bash
   python scrapper\gerar_tudo_automatico.py --force
   ```

3. **Pronto!** O sistema detecta automaticamente.

## Estrutura Automática

```
resources/data/products/
├── impressao-de-panfleto.json          ✅ Auto-gerado
├── impressao-de-apostila.json           ✅ Auto-gerado
├── impressao-de-revista.json            ✅ Auto-gerado
└── ... (todos os outros)
```

O sistema procura automaticamente baseado no **slug do nome do produto**!

