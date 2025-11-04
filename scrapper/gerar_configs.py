"""
Script para analisar produtos e gerar arquivos JSON de configuração
"""
import sys
import json
import os
from analisar_produto import analisar_produto

# URLs dos produtos
PRODUTOS = {
    'impressao-de-panfleto': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-panfleto',
    'impressao-de-apostila': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila',
    'impressao-online-de-livretos-personalizados': 'https://www.lojagraficaeskenazi.com.br/product/impressao-online-de-livretos-personalizados',
    'impressao-de-revista': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista',
    'impressao-de-tabloide': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide',
    'impressao-de-jornal-de-bairro': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-jornal-de-bairro',
    'impressao-de-guia-de-bairro': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-guia-de-bairro',
}

def gerar_json_config(mapeamento, slug):
    """Gera JSON de configuração baseado no mapeamento"""
    options = []
    
    for idx, select_data in sorted(mapeamento.items()):
        label = select_data.get('label', f'Opção {idx+1}')
        name = select_data.get('name', f'option_{idx}')
        opcoes = select_data.get('opcoes', [])
        
        # Detectar tipo de campo
        field_type = 'select'
        if 'quantidade' in label.lower() or 'quantity' in name.lower():
            field_type = 'number'
            default = 50
            min_val = 50
        else:
            default = None
            min_val = None
        
        # Criar choices para select
        choices = []
        for opt in opcoes:
            value = opt.get('value', opt.get('text', ''))
            text = opt.get('text', value)
            choices.append({'value': value, 'label': text})
        
        option_config = {
            'name': name,
            'label': label,
            'type': field_type,
        }
        
        if field_type == 'number':
            option_config['default'] = default
            option_config['min'] = min_val
            option_config['step'] = 1
        else:
            option_config['choices'] = choices
        
        options.append(option_config)
    
    config = {
        'title_override': None,
        'base_price': None,
        'redirect_to_upload': True,
        'options': options
    }
    
    return config

def main():
    output_dir = '../resources/data/products'
    os.makedirs(output_dir, exist_ok=True)
    
    for slug, url in PRODUTOS.items():
        print(f"\n{'='*60}")
        print(f"Processando: {slug}")
        print(f"{'='*60}")
        
        resultado = analisar_produto(url)
        if resultado:
            config = gerar_json_config(resultado['mapeamento'], slug)
            
            output_file = os.path.join(output_dir, f'{slug}.json')
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(config, f, indent=2, ensure_ascii=False)
            
            print(f"\nOK - Config gerado: {output_file}")
        else:
            print(f"\nERRO - Erro ao processar {slug}")

if __name__ == "__main__":
    main()

