import json
import os

# Ajustar caminho
base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'resources', 'data', 'products', 'impressao-de-livro.json')

# Carregar arquivo
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("Reduzindo metade das opcoes de cada campo...")

# Processar cada opção
for opt in data['options']:
    nome = opt.get('name', '')
    choices = opt.get('choices', [])
    
    if nome in ['formato_miolo_paginas', 'papel_miolo', 'quantidade_paginas_miolo']:
        choices_originais = len(choices)
        
        # Reduzir para metade (arredondando para cima)
        metade = (choices_originais + 1) // 2
        
        # Manter as primeiras opções (geralmente são as mais comuns)
        opt['choices'] = choices[:metade]
        
        removidos = choices_originais - len(opt['choices'])
        print(f"OK - {nome}: {removidos} opcoes removidas ({choices_originais} -> {len(opt['choices'])})")

# Salvar arquivo
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("\nOK - Arquivo atualizado com sucesso!")

# Mostrar resumo
print("\n" + "="*70)
print("RESUMO DAS ALTERACOES:")
print("="*70)
for opt in data['options']:
    nome = opt.get('name', '')
    num_choices = len(opt.get('choices', []))
    print(f"  {nome}: {num_choices} opcoes")

# Calcular novo total
quantidades = 31
total = quantidades
for opt in data['options']:
    if opt.get('name') != 'quantity':
        total *= len(opt.get('choices', []))

tempo_estimado = total * 3  # 3 segundos por combinação
horas = int(tempo_estimado // 3600)
minutos = int((tempo_estimado % 3600) // 60)
dias = tempo_estimado / 86400

print("\n" + "="*70)
print(f"NOVO TOTAL DE COMBINACOES: {total:,}")
print(f"Tempo estimado: ~{horas:,} horas ({dias:,.1f} dias)")
print("="*70)

