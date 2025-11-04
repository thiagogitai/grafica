import json
import os

# Ajustar caminho
base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'resources', 'data', 'products', 'impressao-de-livro.json')

# Carregar configuração do livro
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

# Quantidades especificadas
quantidades = 31  # 50, 100, 150, ..., 5000

# Contar opções de cada campo
campos = {}
for opt in data['options']:
    if opt['name'] != 'quantity':
        campos[opt['name']] = len(opt.get('choices', []))

print("=" * 70)
print("ANÁLISE DE COMBINAÇÕES - IMPRESSÃO DE LIVRO")
print("=" * 70)
print(f"\nQuantidades: {quantidades}")
print(f"\nCampos e suas opções:")
print("-" * 70)

for nome, qtd in sorted(campos.items()):
    print(f"  {nome:30} : {qtd:4} opções")

# Calcular total
total = quantidades
for nome, qtd in campos.items():
    total *= qtd

print("\n" + "=" * 70)
print(f"TOTAL DE COMBINAÇÕES: {total:,}")
print("=" * 70)

# Calcular tempo
tempo_segundos = total * 3  # 3 segundos por combinação
tempo_horas = tempo_segundos / 3600
tempo_dias = tempo_segundos / 86400
tempo_anos = tempo_dias / 365

print(f"\nTempo estimado (3 segundos por combinação):")
print(f"  {tempo_horas:,.0f} horas")
print(f"  {tempo_dias:,.1f} dias")
print(f"  {tempo_anos:,.2f} anos")

print("\n" + "=" * 70)
print("SUGESTÕES PARA REDUZIR TEMPO:")
print("=" * 70)

# Identificar campos com mais opções
campos_ordenados = sorted(campos.items(), key=lambda x: x[1], reverse=True)
print("\nCampos com mais opções (recomendado limitar):")
for i, (nome, qtd) in enumerate(campos_ordenados[:5], 1):
    print(f"  {i}. {nome}: {qtd} opções")

# Sugerir reduções
reducoes = {
    'quantidade_paginas_miolo': 10,  # Apenas 10 opções mais comuns
    'formato_miolo_paginas': 5,  # Apenas 5 formatos mais usados
}

total_reduzido = quantidades
for nome, qtd_original in campos.items():
    qtd_usar = reducoes.get(nome, qtd_original)
    total_reduzido *= qtd_usar

print(f"\nSe limitarmos alguns campos:")
print(f"  Total reduzido: {total_reduzido:,} combinações")
print(f"  Tempo reduzido: {total_reduzido * 3 / 3600:,.0f} horas ({total_reduzido * 3 / 86400:.1f} dias)")

