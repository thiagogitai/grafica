"""
Script para recriar o JSON completo do livro
com TODAS as opções da página original
"""
import json
import os
import sys
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
import time

def pegar_todas_opcoes_select(url):
    """Pega TODAS as opções de todos os selects da página"""
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    
    driver = None
    try:
        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(30)
        
        print(f"\nCarregando página: {url}")
        driver.get(url)
        time.sleep(3)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btn = Array.from(document.querySelectorAll('button')).find(
                    b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar')
                );
                if (btn) btn.click();
            """)
            time.sleep(1)
        except:
            pass
        
        # Encontrar todos os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"Encontrados {len(selects)} selects\n")
        
        mapeamento = {}
        for idx, select in enumerate(selects):
            # Tentar encontrar label
            label = None
            try:
                parent = select.find_element(By.XPATH, './..')
                labels = parent.find_elements(By.TAG_NAME, 'label')
                if labels:
                    label = labels[0].text.strip()
                else:
                    # Procurar texto antes do select
                    try:
                        label = select.find_element(By.XPATH, './preceding-sibling::*[1]').text.strip()
                    except:
                        pass
            except:
                pass
            
            if not label:
                label = select.get_attribute('name') or select.get_attribute('id') or f'Select {idx}'
            
            # Pegar TODAS as opções
            opcoes = []
            select_obj = Select(select)
            for opt in select_obj.options:
                valor = opt.get_attribute('value')
                texto = opt.text.strip()
                if valor and texto:
                    opcoes.append({'value': valor, 'text': texto})
            
            mapeamento[str(idx)] = {
                'label': label,
                'name': select.get_attribute('name'),
                'id': select.get_attribute('id'),
                'opcoes': opcoes  # TODAS as opções
            }
            
            print(f"Select {idx}: {label}")
            print(f"  Total de opções: {len(opcoes)}")
            if opcoes:
                print(f"  Primeiras 3: {[o['text'] for o in opcoes[:3]]}")
                if len(opcoes) > 3:
                    print(f"  Últimas 3: {[o['text'] for o in opcoes[-3:]]}")
            print()
        
        return mapeamento
        
    except Exception as e:
        print(f"Erro: {e}")
        import traceback
        traceback.print_exc()
        return None
        
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def criar_config_completo(mapeamento):
    """Cria configuração completa a partir do mapeamento"""
    options = []
    
    # Mapear nomes dos campos
    nomes_campos = {
        0: 'formato_miolo_paginas',
        1: 'papel_capa',
        2: 'cores_capa',
        3: 'orelha_capa',
        4: 'acabamento_capa',
        5: 'papel_miolo',
        6: 'cores_miolo',
        7: 'miolo_sangrado',
        8: 'quantidade_paginas_miolo',
        9: 'acabamento_miolo',
        10: 'acabamento_livro',
        11: 'guardas_livro',
        12: 'extras',
        13: 'frete',
        14: 'verificacao_arquivo',
        15: 'prazo_entrega',
    }
    
    # Adicionar quantidade primeiro
    options.append({
        'name': 'quantity',
        'label': '1- Quantidade',
        'type': 'number',
        'default': 50,
        'min': 50,
        'step': 1
    })
    
    # Processar cada select
    for idx in sorted([int(k) for k in mapeamento.keys()]):
        select_data = mapeamento[str(idx)]
        label = select_data.get('label', f'Opção {idx+1}').rstrip(':')
        nome = nomes_campos.get(idx, f'option_{idx}')
        opcoes = select_data.get('opcoes', [])
        
        choices = []
        for opt in opcoes:
            value = opt.get('value', opt.get('text', ''))
            text = opt.get('text', value)
            choices.append({'value': value, 'label': text})
        
        options.append({
            'name': nome,
            'label': label,
            'type': 'select',
            'choices': choices
        })
    
    config = {
        'title_override': 'Impressão de Livro Personalizado',
        'base_price': None,
        'redirect_to_upload': True,
        'options': options
    }
    
    return config

def main():
    url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro'
    
    print("=" * 60)
    print("Recriando JSON completo do livro")
    print("=" * 60)
    
    # Pegar todas as opções
    mapeamento = pegar_todas_opcoes_select(url)
    
    if not mapeamento:
        print("\nERRO: Não foi possível obter o mapeamento")
        sys.exit(1)
    
    # Criar configuração
    config = criar_config_completo(mapeamento)
    
    # Salvar
    output_file = '../resources/data/products/impressao-de-livro.json'
    os.makedirs(os.path.dirname(output_file), exist_ok=True)
    
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(config, f, indent=2, ensure_ascii=False)
    
    print(f"\nOK - JSON completo salvo em: {output_file}")
    print(f"Total de campos: {len(config['options'])}")
    for opt in config['options']:
        if opt['type'] == 'select':
            print(f"  - {opt['label']}: {len(opt['choices'])} opções")
        else:
            print(f"  - {opt['label']}: {opt['type']}")

if __name__ == "__main__":
    main()

