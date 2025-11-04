# Entendendo a Lógica de Cálculo de Preços - Impressão de Livro

## Análise do Sistema Atual

### Sistema Laravel (Seu Site)
O sistema atual usa uma lógica simples baseada em `data-add`:

```javascript
// Lógica atual (product-json.blade.php)
basePrice + soma(data-add de cada opção) * quantity
```

Cada opção no JSON tem um campo `add` que é somado ao preço base.

### Sistema do Site Original (Eskenazi)

O site original provavelmente tem uma lógica mais complexa. Possíveis abordagens:

#### 1. **Tabela de Preços (como Flyer)**
- Matriz de preços pré-calculada
- Busca direta: `precos[quantidade][formato][papel][cores]`
- **Vantagem**: Rápido, preciso
- **Desvantagem**: Precisa fazer scraping de todas as combinações

#### 2. **API de Cálculo**
- Endpoint que recebe opções e retorna preço
- Exemplo: `POST /api/calculate-price` com JSON das opções
- **Vantagem**: Não precisa scraping, cálculo dinâmico
- **Desvantagem**: Precisa descobrir a API

#### 3. **Fórmula/Algoritmo JavaScript**
- Função JavaScript que calcula baseado em regras
- Exemplo: `preco = base + (paginas * fator_papel) * quantidade`
- **Vantagem**: Pode replicar a fórmula
- **Desvantagem**: Precisa entender a fórmula

#### 4. **Cálculo por Componentes**
- Cada componente tem um preço base
- Preço final = soma de componentes * quantidade
- **Vantagem**: Simples de replicar
- **Desvantagem**: Precisa descobrir preços individuais

## Como Descobrir a Lógica

### Método 1: Inspecionar JavaScript
1. Abra DevTools (F12)
2. Vá em Sources > Page
3. Procure por arquivos `.js` relacionados a cálculo
4. Procure por funções: `calculatePrice`, `calcPrice`, `getPrice`, etc.

### Método 2: Monitorar Requisições de Rede
1. Abra DevTools > Network
2. Filtre por XHR/Fetch
3. Altere opções no formulário
4. Veja se aparecem requisições de API
5. Inspecione o payload e resposta

### Método 3: Analisar Mudanças de Preço
1. Anote o preço inicial
2. Altere uma opção por vez
3. Observe a diferença no preço
4. Tente identificar padrões:
   - Preço muda linearmente?
   - Tem descontos por quantidade?
   - Algumas opções multiplicam o preço?

### Método 4: Reverse Engineering
1. Colete alguns exemplos de preços (poucos casos)
2. Tente identificar padrões matemáticos
3. Teste fórmulas até encontrar a correta

## Scripts Criados

1. **`analisar_logica_preco.py`** - Analisa JavaScript e elementos da página
2. **`capturar_logica_calculo.py`** - Monitora requisições e testa mudanças

## Próximos Passos

Execute o script para capturar informações:
```bash
python scrapper/capturar_logica_calculo.py
```

Depois, analise manualmente:
1. Abra o site no navegador
2. F12 > Network > XHR
3. Altere opções e veja requisições
4. Verifique se há uma API endpoint

## Se Encontrar uma API

Se descobrir uma API, podemos:
1. Fazer requisições diretas (muito mais rápido que scraping)
2. Calcular apenas as combinações necessárias
3. Não precisar iterar todas as combinações

## Se For JavaScript

Se for JavaScript, podemos:
1. Extrair a função de cálculo
2. Replicar no nosso código Python
3. Calcular preços sem fazer scraping

## Se For Tabela de Preços

Se for tabela (como flyer), então:
1. Precisamos fazer scraping (mas otimizado)
2. Podemos usar as limitações que já aplicamos
3. Fazer scraping apenas das combinações restantes

