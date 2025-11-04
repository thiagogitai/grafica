# 🎯 Como Usar o Sistema Automático

## ✅ Sistema 100% Automático!

Não precisa criar JSONs manualmente. O sistema faz tudo automaticamente!

### 🚀 Opção 1: Usar JSONs Já Gerados (Mais Rápido)

Os JSONs já foram criados automaticamente! Basta:

1. **Criar produto no banco** com template `config:auto`
2. **Nome do produto** deve conter palavras-chave como:
   - "Panfleto" → usa `impressao-de-panfleto.json`
   - "Apostila" → usa `impressao-de-apostila.json`
   - "Revista" → usa `impressao-de-revista.json`
   - etc.

3. **Pronto!** O sistema detecta automaticamente o JSON.

### 🔄 Opção 2: Gerar Novos JSONs Automaticamente

Se precisar regenerar ou criar novos produtos:

```bash
python scrapper\gerar_tudo_automatico.py
```

Este script:
- ✅ Analisa o produto no site
- ✅ Gera JSON automaticamente
- ✅ Salva em `resources/data/products/`
- ✅ Pronto para usar!

### 📋 Lista de Produtos Prontos

Todos estes produtos já têm JSON gerado automaticamente:

- ✅ `impressao-de-panfleto.json`
- ✅ `impressao-de-apostila.json`
- ✅ `impressao-online-de-livretos-personalizados.json`
- ✅ `impressao-de-revista.json`
- ✅ `impressao-de-tabloide.json`
- ✅ `impressao-de-jornal-de-bairro.json`
- ✅ `impressao-de-guia-de-bairro.json`
- ✅ `impressao-de-livro.json`

### 🎨 Como Funciona a Auto-Detecção

1. Você cria produto: **"Impressão de Panfleto"**
2. Sistema gera slug: `impressao-de-panfleto`
3. Sistema procura: `resources/data/products/impressao-de-panfleto.json`
4. ✅ **Encontra e carrega automaticamente!**

### 🔧 Adicionar Novo Produto

1. Execute:
   ```bash
   python scrapper\analisar_produto.py https://www.lojagraficaeskenazi.com.br/product/impressao-de-[novo-produto]
   ```

2. Execute:
   ```bash
   python scrapper\gerar_tudo_automatico.py
   ```

3. **Pronto!** JSON criado automaticamente.

### ⚡ Validação Dupla Automática

O sistema também valida preços automaticamente:
- ✅ Valida no frontend antes de habilitar botão
- ✅ Valida no backend antes de adicionar ao carrinho
- ✅ Quantidade mínima de 50 aplicada automaticamente

**Tudo automático! 🎉**

