import json
import os

# Ajustar caminho
base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'resources', 'data', 'products', 'impressao-de-livro.json')

# Carregar arquivo
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("Reduzindo ainda mais as opcoes...")

# Processar cada opção
for opt in data['options']:
    nome = opt.get('name', '')
    choices = opt.get('choices', [])
    
    if nome == 'quantidade_paginas_miolo':
        # Reduzir para apenas 20 opções mais comuns (8, 16, 24, 32, 48, 64, 96, 128, 160, 200, 240, 280, 320, 400, 500, 600, 700, 800)
        choices_originais = len(choices)
        # Manter apenas múltiplos de 8 e alguns arredondados comuns
        opcoes_comuns = [
            'Miolo 8 páginas', 'Miolo 16 páginas', 'Miolo 24 páginas', 'Miolo 32 páginas',
            'Miolo 48 páginas', 'Miolo 64 páginas', 'Miolo 96 páginas', 'Miolo 128 páginas',
            'Miolo 160 páginas', 'Miolo 200 páginas', 'Miolo 240 páginas', 'Miolo 280 páginas',
            'Miolo 320 páginas', 'Miolo 400 páginas', 'Miolo 500 páginas', 'Miolo 600 páginas',
            'Miolo 700 páginas', 'Miolo 800 páginas'
        ]
        
        novas_choices = [c for c in choices if c.get('value') in opcoes_comuns]
        opt['choices'] = novas_choices
        removidos = choices_originais - len(opt['choices'])
        print(f"OK - {nome}: {removidos} opcoes removidas ({choices_originais} -> {len(opt['choices'])})")
    
    elif nome == 'formato_miolo_paginas':
        # Reduzir para 6 formatos mais comuns
        choices_originais = len(choices)
        formatos_comuns = [
            '148x210mm (A5)', '155x230mm (formato otimizado digital)', '170x230mm',
            '205x275mm', '210x297mm (A4)', '230x230mm'
        ]
        novas_choices = [c for c in choices if c.get('value') in formatos_comuns]
        opt['choices'] = novas_choices
        removidos = choices_originais - len(opt['choices'])
        print(f"OK - {nome}: {removidos} opcoes removidas ({choices_originais} -> {len(opt['choices'])})")
    
    elif nome == 'papel_miolo':
        # Reduzir para 5 papéis mais comuns
        choices_originais = len(choices)
        opt['choices'] = choices[:5]  # Manter apenas as 5 primeiras
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

tempo_estimado = total * 2  # 2 segundos por combinação (otimizado)
horas = int(tempo_estimado // 3600)
minutos = int((tempo_estimado % 3600) // 60)
dias = tempo_estimado / 86400

print("\n" + "="*70)
print(f"NOVO TOTAL DE COMBINACOES: {total:,}")
print(f"Tempo estimado (2s por combinacao): ~{horas:,} horas ({dias:,.1f} dias)")
print("="*70)

