"""
Script para criar todos os arquivos JSON de configuração
baseado nos mapeamentos analisados
"""
import json
import os

def ler_mapeamento(slug):
    """Lê arquivo de mapeamento"""
    arquivo = f'{slug}_mapeamento.json'
    if not os.path.exists(arquivo):
        return None
    with open(arquivo, 'r', encoding='utf-8') as f:
        return json.load(f)

def criar_config_do_mapeamento(mapeamento_data, slug, tem_quantidade=True):
    """Cria configuração JSON a partir do mapeamento"""
    mapeamento = mapeamento_data.get('mapeamento', {})
    options = []
    
    # Ordenar por índice
    indices = sorted([int(k) for k in mapeamento.keys()])
    
    for idx in indices:
        select_data = mapeamento[str(idx)]
        label = select_data.get('label', f'Opção {idx+1}')
        name = select_data.get('name', f'option_{idx}')
        opcoes = select_data.get('opcoes', [])
        
        # Detectar se é quantidade
        if 'quantidade' in label.lower() or 'quantity' in name.lower():
            field_type = 'number'
            option_config = {
                'name': 'quantity',
                'label': label,
                'type': field_type,
                'default': 50,
                'min': 50,
                'step': 1
            }
        else:
            field_type = 'select'
            # Criar nome limpo do campo
            nome_limpo = name
            if name.startswith('Options['):
                # Extrair número do Options[X].Value
                try:
                    num = int(name.split('[')[1].split(']')[0])
                    # Tentar inferir nome do label
                    if 'formato' in label.lower():
                        nome_limpo = 'formato'
                    elif 'papel' in label.lower() and 'capa' in label.lower():
                        nome_limpo = 'papel_capa'
                    elif 'cores' in label.lower() and 'capa' in label.lower():
                        nome_limpo = 'cores_capa'
                    elif 'acabamento' in label.lower() and 'capa' in label.lower():
                        nome_limpo = 'acabamento_capa'
                    elif 'orelha' in label.lower():
                        nome_limpo = 'orelha_capa'
                    elif 'papel' in label.lower() and 'miolo' in label.lower():
                        if 'miolo 1' in label.lower():
                            nome_limpo = 'papel_miolo_1'
                        elif 'miolo 2' in label.lower():
                            nome_limpo = 'papel_miolo_2'
                        else:
                            nome_limpo = 'papel_miolo'
                    elif 'cores' in label.lower() and 'miolo' in label.lower():
                        if 'miolo 1' in label.lower():
                            nome_limpo = 'cores_miolo_1'
                        elif 'miolo 2' in label.lower():
                            nome_limpo = 'cores_miolo_2'
                        else:
                            nome_limpo = 'cores_miolo'
                    elif 'quantidade' in label.lower() and 'paginas' in label.lower():
                        if 'miolo 1' in label.lower():
                            nome_limpo = 'quantidade_paginas_miolo_1'
                        elif 'miolo 2' in label.lower():
                            nome_limpo = 'quantidade_paginas_miolo_2'
                        else:
                            nome_limpo = 'quantidade_paginas_miolo'
                    elif 'acabamento' in label.lower() and 'miolo' in label.lower():
                        nome_limpo = 'acabamento_miolo'
                    elif 'acabamento' in label.lower() and ('livro' in label.lower() or 'final' in label.lower()):
                        nome_limpo = 'acabamento_livro'
                    elif 'guardas' in label.lower():
                        nome_limpo = 'guardas_livro'
                    elif 'extras' in label.lower():
                        nome_limpo = 'extras'
                    elif 'frete' in label.lower():
                        nome_limpo = 'frete'
                    elif 'verificacao' in label.lower() or 'arquivo' in label.lower():
                        nome_limpo = 'verificacao_arquivo'
                    elif 'prazo' in label.lower():
                        nome_limpo = 'prazo_entrega'
                    elif 'formato' in label.lower() and 'arquivo' in label.lower():
                        nome_limpo = 'formato_arquivo'
                    elif 'contra' in label.lower() and 'capa' in label.lower():
                        nome_limpo = 'contra_capa'
                    elif 'sangrado' in label.lower():
                        nome_limpo = 'miolo_sangrado'
                    else:
                        nome_limpo = f'option_{num}'
                except:
                    nome_limpo = f'option_{idx}'
            
            choices = []
            for opt in opcoes:
                value = opt.get('value', opt.get('text', ''))
                text = opt.get('text', value)
                choices.append({'value': value, 'label': text})
            
            option_config = {
                'name': nome_limpo,
                'label': label,
                'type': field_type,
                'choices': choices
            }
        
        options.append(option_config)
    
    # Se não tem quantidade, adicionar no início
    if not tem_quantidade:
        options.insert(0, {
            'name': 'quantity',
            'label': '1- Quantidade',
            'type': 'number',
            'default': 50,
            'min': 50,
            'step': 1
        })
    
    config = {
        'title_override': None,
        'base_price': None,
        'redirect_to_upload': True,
        'options': options
    }
    
    return config

def main():
    # Lista completa de produtos (panfleto tem quantidade, outros não)
    produtos = [
        ('impressao-de-panfleto', True),
        ('impressao-de-apostila', False),
        ('impressao-online-de-livretos-personalizados', False),
        ('impressao-de-revista', False),
        ('impressao-de-tabloide', False),
        ('impressao-de-jornal-de-bairro', False),
        ('impressao-de-guia-de-bairro', False),
    ]
    
    output_dir = '../resources/data/products'
    os.makedirs(output_dir, exist_ok=True)
    
    for slug, tem_qtd in produtos:
        print(f"\nProcessando: {slug}")
        mapeamento = ler_mapeamento(slug)
        if mapeamento:
            config = criar_config_do_mapeamento(mapeamento, slug, tem_qtd)
            output_file = os.path.join(output_dir, f'{slug}.json')
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(config, f, indent=2, ensure_ascii=False)
            print(f"OK - Config criado: {output_file}")
        else:
            print(f"ERRO - Mapeamento nao encontrado para {slug}")

if __name__ == "__main__":
    main()

