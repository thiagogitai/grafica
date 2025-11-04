import json
import re

import os

# Ajustar caminho
base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'resources', 'data', 'products', 'impressao-de-livro.json')

# Carregar arquivo
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("Removendo opcoes conforme solicitado...")

# Remover secoes 15, 16, 17 (frete, verificacao_arquivo, prazo_entrega)
campos_para_remover = ['frete', 'verificacao_arquivo', 'prazo_entrega']
data['options'] = [opt for opt in data['options'] if opt.get('name') not in campos_para_remover]
print(f"OK - Removidas secoes 15, 16, 17: {', '.join(campos_para_remover)}")

# Processar cada opção
for opt in data['options']:
    nome = opt.get('name', '')
    
    # Processar papel_miolo
    if nome == 'papel_miolo':
        choices_originais = len(opt['choices'])
        # Remover itens com "abaixo de 300" ou "acima de 300"
        opt['choices'] = [
            c for c in opt['choices']
            if 'abaixo de 300' not in c.get('value', '').lower()
            and 'acima de 300' not in c.get('value', '').lower()
            and 'acima de 300pçs' not in c.get('value', '').lower()
            and 'acima de 300pcs' not in c.get('value', '').lower()
            and c.get('value') != 'Impressão Offset - >500unidades'
            and c.get('value') != 'Offset 56gr'
            and c.get('value') != 'Offset 63gr'
        ]
        removidos = choices_originais - len(opt['choices'])
        print(f"OK - Papel miolo: {removidos} opcoes removidas ({choices_originais} -> {len(opt['choices'])})")
    
    # Processar orelha_capa
    elif nome == 'orelha_capa':
        choices_originais = len(opt['choices'])
        # Remover "COM Orelha de 3cm", "13cm", "14cm"
        opt['choices'] = [
            c for c in opt['choices']
            if '3cm' not in c.get('value', '')
            and '13cm' not in c.get('value', '')
            and '14cm' not in c.get('value', '')
        ]
        removidos = choices_originais - len(opt['choices'])
        print(f"OK - Orelha capa: {removidos} opcoes removidas ({choices_originais} -> {len(opt['choices'])})")
    
    # Processar quantidade_paginas_miolo
    elif nome == 'quantidade_paginas_miolo':
        choices_originais = len(opt['choices'])
        # Remover miolos depois de 820 páginas
        novas_choices = []
        for c in opt['choices']:
            valor = c.get('value', '')
            # Extrair número de páginas
            match = re.search(r'Miolo (\d+)', valor)
            if match:
                num_paginas = int(match.group(1))
                if num_paginas <= 820:
                    novas_choices.append(c)
            else:
                # Se não conseguir extrair número, manter (pode ser formato diferente)
                novas_choices.append(c)
        
        opt['choices'] = novas_choices
        removidos = choices_originais - len(opt['choices'])
        print(f"OK - Quantidade paginas miolo: {removidos} opcoes removidas (mantidas ate 820 paginas)")

# Salvar arquivo
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("\nOK - Arquivo atualizado com sucesso!")

# Mostrar resumo
print("\n" + "="*70)
print("RESUMO DAS ALTERACOES:")
print("="*70)
total_campos = len(data['options'])
for opt in data['options']:
    nome = opt.get('name', '')
    num_choices = len(opt.get('choices', []))
    print(f"  {nome}: {num_choices} opcoes")

