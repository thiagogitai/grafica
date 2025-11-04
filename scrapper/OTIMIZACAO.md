# Otimização do Scraper de Livros

## 📊 Análise de Combinações

### Total sem limitações:
- **578 trilhões de combinações**
- **55 milhões de anos** de execução (3s por combinação)

### Campos com mais opções:
1. **quantidade_paginas_miolo**: 352 opções ⚠️ (MAIOR IMPACTO)
2. **papel_miolo**: 26 opções
3. **formato_miolo_paginas**: 23 opções
4. **orelha_capa**: 11 opções
5. **papel_capa**: 10 opções

## 🚀 Estratégias de Otimização

### 1. Limitar Campos Críticos

Edite `scraper_livro_otimizado.py` na seção de configuração:

```python
'campos_limitar': {
    'quantidade_paginas_miolo': 20,  # Apenas 20 opções mais comuns (de 352)
    'formato_miolo_paginas': 5,      # Apenas 5 formatos (de 23)
    'papel_miolo': 8,                # Apenas 8 papéis (de 26)
    'papel_capa': 5,                 # Apenas 5 papéis (de 10)
    'orelha_capa': 3,                # Apenas 3 opções (de 11)
}
```

**Exemplo de redução:**
- Com essas limitações: ~**2.4 milhões de combinações**
- Tempo: ~**200 horas** (8 dias)

### 2. Reduzir Delays

```python
'delay_selecao': 0.1,  # Mínimo seguro: 0.1s
'delay_preco': 0.5,    # Mínimo seguro: 0.5s
```

Isso pode reduzir o tempo em até 50-60%.

### 3. Coletar por Lotes

Execute o scraper várias vezes, cada vez com um subconjunto diferente de opções:

**Lote 1:** Quantidades 50-1000 + algumas opções
**Lote 2:** Quantidades 1250-2500 + outras opções
**Lote 3:** Quantidades 2750-5000 + outras opções

### 4. Focar em Quantidades Específicas

Se não precisa de todas as 31 quantidades, edite:

```python
'quantidades': ["100", "500", "1000", "2000", "5000"]  # Apenas 5 quantidades
```

## 📈 Tabela de Exemplos

| Configuração | Combinações | Tempo (horas) | Tempo (dias) |
|-------------|-------------|---------------|--------------|
| Sem limitações | 578 trilhões | 482 bilhões | 55 milhões |
| Limitar páginas (20) | 32 bilhões | 27 milhões | 308 mil |
| Limitar páginas + formatos | 1.4 bilhão | 1.2 milhão | 13.700 |
| Limitar páginas + formatos + papéis | 43 milhões | 36 mil | 1.500 |
| **Todas as limitações sugeridas** | **2.4 milhões** | **200** | **8** |
| + Apenas 5 quantidades | **387 mil** | **32** | **1.3** |

## 🎯 Recomendação Final

Para uma coleta viável em **1-2 semanas**:

```python
config = {
    'quantidades': [
        "50", "100", "250", "500", "750", "1000", "1500", "2000", 
        "2500", "3000", "4000", "5000"  # 12 quantidades principais
    ],
    'campos_limitar': {
        'quantidade_paginas_miolo': 15,   # 15 mais comuns (8, 16, 24, 32, 48, 64, 96, 128, 160, 200, 240, 280, 320, 400, 500)
        'formato_miolo_paginas': 5,       # 5 mais usados
        'papel_miolo': 6,                 # 6 mais comuns
        'papel_capa': 4,                  # 4 mais comuns
        'orelha_capa': 2,                 # SEM ORELHA + 1 tamanho comum
    },
    'delay_selecao': 0.15,
    'delay_preco': 0.6,
}
```

**Resultado esperado:**
- ~**216 mil combinações**
- ~**36 horas** de execução
- **1.5 dias** contínuos

## ⚙️ Como Usar

1. Edite `scraper_livro_otimizado.py`
2. Ajuste a seção `config` conforme suas necessidades
3. Execute: `python scraper_livro_otimizado.py`
4. O script mostrará quantas combinações serão testadas antes de iniciar

## 💡 Dica

Execute uma versão de teste primeiro com apenas 1-2 quantidades e poucas opções para verificar se está funcionando corretamente antes de fazer a coleta completa.

