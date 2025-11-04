# Scraper de Preços - Impressão de Livro

## Descrição

Este scraper automatiza a coleta de preços do produto "Impressão de Livro" do site da gráfica Eskenazi.

## ⚠️ AVISO IMPORTANTE

**O número de combinações é EXTREMAMENTE ALTO!**

Com as quantidades especificadas (31 valores) e todas as opções de cada campo, o total de combinações pode chegar a **milhões ou bilhões**.

### Exemplo de cálculo:
- 31 quantidades
- ~22 formatos de miolo
- ~10 papéis de capa
- ~8 cores de capa
- ~12 orelhas
- ~5 acabamentos de capa
- ~30 papéis de miolo
- ~2 cores de miolo
- ~2 sangrados
- ~200+ quantidade de páginas
- e mais...

**Total estimado: 31 × 22 × 10 × 8 × 12 × 5 × 30 × 2 × 2 × 200 × ... = BILHÕES de combinações**

**Tempo estimado: CENTENAS DE DIAS de execução contínua!**

## Recomendações

### Opção 1: Coletar apenas algumas opções principais
Edite o script para limitar as opções mais comuns de cada campo.

### Opção 2: Coletar por lotes
Execute o scraper várias vezes, cada vez com um subconjunto de opções.

### Opção 3: Coletar apenas preços de quantidades específicas
Se precisar apenas de algumas quantidades, edite a lista `quantidades` no script.

## Pré-requisitos

1. Python 3.7+
2. Selenium: `pip install selenium`
3. ChromeDriver instalado e no PATH
   - Baixe em: https://chromedriver.chromium.org/
   - Ou use: `pip install webdriver-manager`

## Como usar

1. Navegue até a pasta `scrapper`:
   ```bash
   cd scrapper
   ```

2. Execute o scraper:
   ```bash
   python scraper_livro.py
   ```

3. O script irá:
   - Abrir o Chrome automaticamente
   - Detectar os campos do formulário
   - Mostrar quantas combinações serão testadas
   - Pedir confirmação antes de iniciar
   - Salvar progresso parcial a cada quantidade
   - Gerar arquivo final `precos_livro.json`

## Estrutura do arquivo de saída

O arquivo `precos_livro.json` terá a seguinte estrutura:

```json
{
  "{\"quantity\":\"50\",\"formato_miolo_paginas\":\"...\",...}": 123.45,
  "{\"quantity\":\"50\",\"formato_miolo_paginas\":\"...\",...}": 234.56,
  ...
}
```

Cada chave é um JSON stringificado com todas as opções selecionadas, e o valor é o preço final.

## Personalização

### Modificar quantidades
Edite a lista `quantidades` no início da função `coletar_precos_livro()`:

```python
quantidades = ["50", "100", "500", "1000"]  # Apenas algumas quantidades
```

### Limitar opções de um campo
Após o script detectar os campos, você pode editar manualmente o dicionário `opcoes` para limitar as opções:

```python
# Exemplo: apenas 3 formatos de miolo
opcoes['formato_miolo_paginas'] = opcoes['formato_miolo_paginas'][:3]
```

## Troubleshooting

### Elemento de preço não encontrado
O script tenta encontrar automaticamente, mas se falhar, você pode:
1. Inspecionar a página manualmente
2. Editar a função `encontrar_elemento_preco()` com o ID correto

### Campos não encontrados
O script tenta encontrar por ID e name. Se ainda assim falhar:
1. Inspecione a página
2. Edite a função `encontrar_campos_formulario()` com os seletores corretos

### Timeout
Se o script demorar muito em uma combinação:
- Aumente o `time.sleep()` após selecionar opções
- Aumente o timeout do WebDriverWait

## Notas

- O script salva progresso parcial a cada quantidade processada
- Se o script for interrompido, você pode retomar manualmente (não implementado automaticamente)
- Os arquivos parciais são salvos como `precos_livro_parcial_{quantidade}.json`

