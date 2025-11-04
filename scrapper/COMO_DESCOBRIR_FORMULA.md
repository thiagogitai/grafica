# Como Descobrir a Fórmula de Cálculo de Preços

## Método 1: Análise Automática (Script Python)

Execute o script que automatiza a análise:

```bash
python scrapper/descobrir_formula_javascript.py
```

Este script irá:
- Abrir o site no navegador
- Extrair funções JavaScript relacionadas a cálculo
- Analisar mudanças de preço ao alterar opções
- Capturar requisições de rede
- Salvar tudo em `analise_formula_completa.json`

## Método 2: Análise Manual no Navegador (RECOMENDADO)

### Passo 1: Abrir o Site
1. Abra: https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro
2. Pressione F12 para abrir DevTools

### Passo 2: Executar Script no Console
1. Vá na aba **Console**
2. Cole o conteúdo do arquivo `extrair_funcao_calculo.js`
3. Pressione ENTER
4. O script irá listar funções e elementos relacionados

### Passo 3: Inspecionar Código JavaScript
1. Vá na aba **Sources** (ou **Fontes**)
2. Procure por arquivos `.js` que contenham:
   - `calculate`
   - `price`
   - `preco`
   - `total`
   - `calc`
3. Abra os arquivos e procure por funções de cálculo

### Passo 4: Colocar Breakpoint
1. Encontre a função que calcula o preço
2. Coloque um breakpoint na função
3. Altere uma opção no formulário
4. O código vai pausar no breakpoint
5. Inspecione as variáveis para entender a fórmula

### Passo 5: Monitorar Mudanças
1. Vá na aba **Network** (Rede)
2. Filtre por **XHR** ou **Fetch**
3. Altere opções no formulário
4. Veja se aparecem requisições
5. Inspecione o payload e resposta

## Método 3: Análise de Padrões

### Teste Sistemático
1. Anote o preço inicial
2. Altere APENAS a quantidade:
   - 50 → anote preço
   - 100 → anote preço
   - 200 → anote preço
   - 500 → anote preço
3. Calcule a relação: preço / quantidade
4. Veja se é linear ou se tem descontos

### Teste por Campo
1. Mantenha tudo igual, altere apenas UM campo por vez
2. Anote a diferença no preço
3. Tente identificar padrões:
   - Algumas opções somam valor fixo?
   - Algumas multiplicam o preço?
   - Algumas têm descontos?

## O Que Procurar

### 1. Funções JavaScript Comuns
```javascript
calculatePrice()
calcPrice()
getPrice()
updatePrice()
computeTotal()
calculateTotal()
```

### 2. Variáveis Globais
```javascript
window.basePrice
window.totalPrice
window.price
window.calc
```

### 3. Estrutura de Cálculo
```javascript
// Pode ser algo como:
basePrice + (paginas * fator_papel) * quantidade
// ou
precoBase * quantidade * multiplicador_formato * multiplicador_papel
// ou
lookupTable[quantidade][formato][papel][cores]
```

### 4. Requisições de API
- Endpoints como `/api/calculate`
- Payload com todas as opções
- Resposta com o preço calculado

## Depois de Descobrir

Quando encontrar a fórmula, podemos:
1. Replicar no Python
2. Criar função que calcula preços sem scraping
3. Gerar apenas as combinações necessárias
4. Muito mais rápido que scraping!

## Arquivos Úteis

- `descobrir_formula_javascript.py` - Script automatizado
- `extrair_funcao_calculo.js` - Script para console do navegador
- `analise_formula_completa.json` - Resultados da análise (será gerado)

