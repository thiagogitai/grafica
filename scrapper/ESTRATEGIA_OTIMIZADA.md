# Estratégia Otimizada para Coleta de Preços de Livros

## 📊 Situação Atual

Após as reduções, temos **39,4 bilhões de combinações** - ainda impraticável fazer scraping completo.

## 🎯 Estratégias Possíveis

### 1. **Descobrir API/Fórmula (RECOMENDADO - Mais Rápido)**

Se o site original usar uma API ou fórmula JavaScript, podemos:
- ✅ Calcular preços sem scraping
- ✅ Gerar apenas as combinações necessárias
- ✅ Tempo: minutos ao invés de dias

**Como descobrir:**
1. Execute: `python scrapper/capturar_logica_calculo.py`
2. Abra DevTools (F12) > Network > XHR
3. Altere opções no formulário
4. Veja se aparecem requisições de API
5. Se encontrar, podemos replicar as chamadas

### 2. **Scraping Inteligente (Se Não Houver API)**

Se não houver API, fazer scraping mas:
- Coletar apenas amostras estratégicas
- Usar interpolação para preencher gaps
- Fazer scraping de casos representativos

### 3. **Tabela de Preços (Como Flyer)**

Se o site usar tabela pré-calculada:
- Fazer scraping de todas as combinações (mas limitadas)
- Com as reduções atuais: ~39 bilhões ainda é muito
- Precisaria reduzir ainda mais

## 📈 Redução Atual

Com as reduções aplicadas:
- **Quantidade páginas miolo**: 204 → 14 opções ✅
- **Formato miolo**: 12 → 3 opções ✅
- **Papel miolo**: 7 → 5 opções ✅
- **Total**: 39,4 bilhões de combinações

## 💡 Próximos Passos

### Opção A: Tentar Descobrir API/Fórmula (FAZER AGORA)

1. Execute o script de análise:
   ```bash
   python scrapper/capturar_logica_calculo.py
   ```

2. Analise manualmente:
   - Abra o site no navegador
   - F12 > Network > XHR
   - Altere opções e veja requisições
   - Verifique Console para funções JavaScript

3. Se encontrar API:
   - Criar script que chama a API diretamente
   - Muito mais rápido que scraping

### Opção B: Reduzir Mais Campos

Se precisar fazer scraping mesmo assim, podemos reduzir:
- `papel_capa`: 10 → 5 opções
- `cores_capa`: 8 → 4 opções  
- `orelha_capa`: 9 → 3 opções
- `acabamento_livro`: 10 → 5 opções
- `extras`: 7 → 3 opções
- `guardas_livro`: 6 → 3 opções

Isso reduziria para ~**1-2 milhões de combinações** = viável em alguns dias.

### Opção C: Scraping por Amostragem

Coletar apenas:
- Algumas quantidades (ex: 50, 100, 500, 1000, 2000, 5000)
- Algumas combinações representativas
- Usar interpolação para estimar preços intermediários

## 🔍 Como Executar Análise

```bash
# 1. Analisar lógica do site
python scrapper/capturar_logica_calculo.py

# 2. Verificar se há API ou fórmula
# (análise manual no navegador)
```

## 📝 O Que Procurar

1. **Requisições de Rede:**
   - Endpoints como `/api/calculate-price`
   - Endpoints como `/calculate` ou `/price`
   - Payload com opções selecionadas

2. **Funções JavaScript:**
   - `calculatePrice()`, `calcPrice()`, `getPrice()`
   - Fórmulas matemáticas no código
   - Variáveis que armazenam preços base

3. **Padrões de Cálculo:**
   - Preço muda linearmente com quantidade?
   - Algumas opções multiplicam o preço?
   - Há descontos por volume?

