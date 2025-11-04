#!/usr/bin/env python3
"""
Script para extrair estrutura COMPLETA do site matriz e criar script de scraping do zero
"""
import json
import sys
import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options

PRODUTOS = {
    'impressao-de-revista': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista',
    'impressao-de-tabloide': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide',
}

def extrair_estrutura_completa(url, slug):
    """Extrai estrutura COMPLETA do site matriz"""
    chrome_options = Options()
    chrome_options.add_argument('--headless=new')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    
    service = Service()
    driver = webdriver.Chrome(service=service, options=chrome_options)
    
    try:
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
        
        estrutura = {
            'url': url,
            'slug': slug,
            'campos': []
        }
        
        # Encontrar TODOS os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        # Encontrar campo de quantidade (input number)
        try:
            qtd_input = driver.find_element(By.XPATH, "//input[@type='number']")
            label_qtd = None
            try:
                parent = qtd_input.find_element(By.XPATH, './..')
                labels = parent.find_elements(By.TAG_NAME, 'label')
                if labels:
                    label_qtd = labels[0].text.strip()
                else:
                    # Procurar texto antes do input
                    try:
                        label_elem = qtd_input.find_element(By.XPATH, './preceding-sibling::*[1]')
                        label_qtd = label_elem.text.strip()
                    except:
                        pass
            except:
                pass
            
            if not label_qtd:
                label_qtd = qtd_input.get_attribute('name') or qtd_input.get_attribute('id') or 'Quantidade'
            
            estrutura['campos'].append({
                'index': -1,  # Campo de quantidade não é select
                'tipo': 'input',
                'name': qtd_input.get_attribute('name') or 'quantity',
                'id': qtd_input.get_attribute('id') or '',
                'label': label_qtd,
                'min': qtd_input.get_attribute('min') or '50',
                'max': qtd_input.get_attribute('max') or '',
                'step': qtd_input.get_attribute('step') or '1',
                'default': qtd_input.get_attribute('value') or '50'
            })
        except:
            pass
        
        # Processar cada select na ordem que aparece na página
        for idx, select in enumerate(selects):
            # Tentar encontrar label
            label = None
            name = select.get_attribute('name') or ''
            select_id = select.get_attribute('id') or ''
            
            try:
                # Tentar encontrar label antes do select
                parent = select.find_element(By.XPATH, './..')
                labels = parent.find_elements(By.TAG_NAME, 'label')
                if labels:
                    label = labels[0].text.strip()
                else:
                    # Procurar texto antes do select
                    try:
                        label_elem = select.find_element(By.XPATH, './preceding-sibling::*[1]')
                        label = label_elem.text.strip()
                    except:
                        pass
            except:
                pass
            
            if not label:
                label = name or select_id or f'Select {idx}'
            
            # Extrair TODAS as opções
            opcoes = []
            for opt in select.find_elements(By.TAG_NAME, 'option'):
                valor = opt.get_attribute('value')
                texto = opt.text.strip()
                if valor and valor.strip() and texto:
                    opcoes.append({
                        'value': valor.strip(),
                        'text': texto
                    })
            
            estrutura['campos'].append({
                'index': idx,
                'tipo': 'select',
                'name': name,
                'id': select_id,
                'label': label,
                'total_opcoes': len(opcoes),
                'opcoes': opcoes
            })
        
        return estrutura
        
    finally:
        driver.quit()

def criar_script_scraping(estrutura):
    """Cria script de scraping do zero baseado na estrutura extraída"""
    slug = estrutura['slug']
    url = estrutura['url']
    campos = estrutura['campos']
    
    # Nome do arquivo
    nome_arquivo = f"scrapper/scrape_{slug.replace('impressao-de-', '').replace('impressao-online-de-', '').replace('impressao-', '')}.py"
    
    # Criar mapeamento
    mapeamento = {}
    for campo in campos:
        if campo['tipo'] == 'select':
            # Tentar inferir nome do campo do label
            label_lower = campo['label'].lower()
            name_inferido = campo['name']
            
            # Se não tem name, tentar inferir do label
            if not name_inferido or name_inferido.startswith('Options'):
                # Inferir do label
                if 'formato' in label_lower:
                    name_inferido = 'formato'
                elif 'papel' in label_lower and 'capa' in label_lower:
                    name_inferido = 'papel_capa'
                elif 'papel' in label_lower and 'miolo' in label_lower:
                    name_inferido = 'papel_miolo'
                elif 'cores' in label_lower and 'capa' in label_lower:
                    name_inferido = 'cores_capa'
                elif 'cores' in label_lower and 'miolo' in label_lower:
                    name_inferido = 'cores_miolo'
                elif 'orelha' in label_lower:
                    name_inferido = 'orelha_capa'
                elif 'acabamento' in label_lower:
                    # Verificar ordem específica: capa, miolo, final/livro
                    if 'capa' in label_lower:
                        name_inferido = 'acabamento_capa'
                    elif 'miolo' in label_lower:
                        name_inferido = 'acabamento_miolo'
                    elif 'final' in label_lower or 'livro' in label_lower:
                        name_inferido = 'acabamento_livro'
                    else:
                        name_inferido = 'acabamento_livro'  # Padrão se não especificado
                elif 'quantidade' in label_lower and 'paginas' in label_lower:
                    name_inferido = 'quantidade_paginas_miolo'
                elif 'sangrado' in label_lower:
                    name_inferido = 'miolo_sangrado'
                elif 'guardas' in label_lower:
                    name_inferido = 'guardas_livro'
                elif 'extras' in label_lower:
                    name_inferido = 'extras'
                elif 'frete' in label_lower:
                    name_inferido = 'frete'
                elif 'verificação' in label_lower or 'verificacao' in label_lower:
                    name_inferido = 'verificacao_arquivo'
                elif 'prazo' in label_lower:
                    name_inferido = 'prazo_entrega'
                elif 'formato' in label_lower and 'arquivo' in label_lower:
                    name_inferido = 'formato_arquivo'
                else:
                    name_inferido = f"campo_{campo['index']}"
            
            mapeamento[name_inferido] = campo['index']
    
    # Gerar código do script
    codigo = f'''"""
Script para fazer scraping em tempo real do preço de {slug.upper()}
Criado automaticamente baseado na estrutura do site matriz
"""
import sys
import json
import time
import re
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

def extrair_valor_preco(texto):
    """Extrai valor numérico do preço"""
    if not texto:
        return None
    valor = re.sub(r'[R$\\s.]', '', texto)
    valor = valor.replace(',', '.')
    try:
        return float(valor)
    except:
        return None

def scrape_preco_tempo_real(opcoes, quantidade):
    """
    Faz scraping do preço de {slug.upper()} no site da Eskenazi em tempo real.
    """
    url = "{url}"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--window-size=1920,1080')
    
    import tempfile
    import os
    
    selenium_cache_dir = os.path.join(tempfile.gettempdir(), 'selenium_cache_' + str(os.getpid()))
    os.makedirs(selenium_cache_dir, exist_ok=True)
    os.environ['SELENIUM_CACHE_DIR'] = selenium_cache_dir
    
    chrome_user_data_dir = os.path.join(tempfile.gettempdir(), 'chrome_user_data_' + str(os.getpid()))
    os.makedirs(chrome_user_data_dir, exist_ok=True)
    options.add_argument(f'--user-data-dir={{chrome_user_data_dir}}')
    
    service = Service()
    driver = None
    
    try:
        driver = webdriver.Chrome(service=service, options=options)
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
        
        # Aplicar quantidade
        try:
            qtd_input = driver.find_element(By.XPATH, "//input[@type='number']")
            qtd_input.clear()
            qtd_input.send_keys(str(quantidade))
            time.sleep(0.5)
        except:
            pass
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        # Mapeamento EXATO baseado no site matriz
        mapeamento = {json.dumps(mapeamento, indent=8)}
        
        # Ordenar campos para processar na sequência correta
        campos_ordenados = []
        max_idx = max(mapeamento.values()) if mapeamento else 0
        for idx in range(max_idx + 1):
            for campo, valor in opcoes.items():
                if campo == 'quantity':
                    continue
                if mapeamento.get(campo) == idx:
                    campos_ordenados.append((campo, valor))
                    break
        
        # Processar campos na ordem correta
        for campo, valor in campos_ordenados:
            idx = mapeamento.get(campo)
            if idx is not None and idx < len(selects):
                select = selects[idx]
                valor_str = str(valor).strip()
                opcao_encontrada = False
                
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    v_str = str(v).strip() if v else ''
                    t_str = str(t).strip() if t else ''
                    
                    if (v_str == valor_str or t_str == valor_str or 
                        valor_str in v_str or valor_str in t_str or
                        v_str in valor_str or t_str in valor_str):
                        try:
                            Select(select).select_by_value(v)
                            opcao_encontrada = True
                            time.sleep(0.4)
                            # Verificar se preço já foi calculado
                            for _ in range(20):
                                time.sleep(0.15)
                                try:
                                    preco_element = driver.find_element(By.ID, "calc-total")
                                    preco_texto = preco_element.text
                                    preco_valor = extrair_valor_preco(preco_texto)
                                    if preco_valor and preco_valor > 0:
                                        return preco_valor
                                except:
                                    pass
                            break
                        except Exception as e:
                            print(f"DEBUG: ERRO ao selecionar {{campo}}: {{e}}", file=sys.stderr)
                
                if not opcao_encontrada:
                    print(f"DEBUG: AVISO - Opção não encontrada para {{campo}} = {{valor}}", file=sys.stderr)
        
        # Aguardar cálculo final
        time.sleep(0.6)
        for tentativa in range(30):
            time.sleep(0.1)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    return preco_valor
            except:
                pass
        
        return None
        
    except Exception as e:
        import traceback
        print(f"ERRO_NO_SCRAPER: {{str(e)}}", file=sys.stderr)
        print(f"TRACEBACK: {{traceback.format_exc()}}", file=sys.stderr)
        return None
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def main():
    if len(sys.argv) < 2:
        resultado = {{'success': False, 'error': 'Dados não fornecidos'}}
        print(json.dumps(resultado))
        sys.exit(1)
    
    try:
        dados = json.loads(sys.argv[1])
        opcoes = dados.get('opcoes', {{}})
        quantidade = dados.get('quantidade', 50)
        
        preco = scrape_preco_tempo_real(opcoes, quantidade)
        
        if preco is not None:
            resultado = {{'success': True, 'price': preco}}
        else:
            resultado = {{'success': False, 'error': 'Preço não encontrado'}}
        
        print(json.dumps(resultado))
    except Exception as e:
        resultado = {{'success': False, 'error': str(e)}}
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()
'''
    
    # Corrigir a linha do mapeamento
    codigo = codigo.replace(f'mapeamento = {json.dumps(mapeamento, indent=8)}', f'mapeamento = {json.dumps(mapeamento)}')
    
    return nome_arquivo, codigo, estrutura

def main():
    print("="*80)
    print("EXTRAÇÃO COMPLETA DA ESTRUTURA DO SITE MATRIZ")
    print("="*80)
    
    for slug, url in PRODUTOS.items():
        print(f"\n{'='*80}")
        print(f"Extraindo: {slug}")
        print(f"{'='*80}")
        
        estrutura = extrair_estrutura_completa(url, slug)
        
        # Salvar estrutura em JSON
        json_path = f"estrutura_{slug}.json"
        with open(json_path, 'w', encoding='utf-8') as f:
            json.dump(estrutura, f, indent=2, ensure_ascii=False)
        print(f"\n✅ Estrutura salva em: {json_path}")
        
        # Mostrar estrutura
        print(f"\n📋 ESTRUTURA EXTRAÍDA:")
        print(f"   Total de campos: {len(estrutura['campos'])}")
        for campo in estrutura['campos']:
            if campo['tipo'] == 'input':
                print(f"   [{campo['index']}] {campo['tipo'].upper()}: {campo['label']} (name: {campo['name']})")
            else:
                print(f"   [{campo['index']}] {campo['tipo'].upper()}: {campo['label']} (name: {campo['name']}, {campo['total_opcoes']} opções)")
        
        # Criar script
        nome_arquivo, codigo, estrutura_full = criar_script_scraping(estrutura)
        
        # Salvar script
        with open(nome_arquivo, 'w', encoding='utf-8') as f:
            f.write(codigo)
        print(f"\n✅ Script criado em: {nome_arquivo}")
        
        print(f"\n📝 Mapeamento criado:")
        mapeamento = {}
        for campo in estrutura['campos']:
            if campo['tipo'] == 'select':
                label_lower = campo['label'].lower()
                name_inferido = campo['name']
                if not name_inferido or name_inferido.startswith('Options'):
                    if 'formato' in label_lower:
                        name_inferido = 'formato'
                    elif 'papel' in label_lower and 'capa' in label_lower:
                        name_inferido = 'papel_capa'
                    elif 'papel' in label_lower and 'miolo' in label_lower:
                        name_inferido = 'papel_miolo'
                    elif 'cores' in label_lower and 'capa' in label_lower:
                        name_inferido = 'cores_capa'
                    elif 'cores' in label_lower and 'miolo' in label_lower:
                        name_inferido = 'cores_miolo'
                    elif 'orelha' in label_lower:
                        name_inferido = 'orelha_capa'
                    elif 'acabamento' in label_lower and 'capa' in label_lower:
                        name_inferido = 'acabamento_capa'
                    elif 'acabamento' in label_lower and 'miolo' in label_lower:
                        name_inferido = 'acabamento_miolo'
                    elif 'acabamento' in label_lower and 'livro' in label_lower:
                        name_inferido = 'acabamento_livro'
                    elif 'quantidade' in label_lower and 'paginas' in label_lower:
                        name_inferido = 'quantidade_paginas_miolo'
                    elif 'sangrado' in label_lower:
                        name_inferido = 'miolo_sangrado'
                    elif 'guardas' in label_lower:
                        name_inferido = 'guardas_livro'
                    elif 'extras' in label_lower:
                        name_inferido = 'extras'
                    elif 'frete' in label_lower:
                        name_inferido = 'frete'
                    elif 'verificação' in label_lower or 'verificacao' in label_lower:
                        name_inferido = 'verificacao_arquivo'
                    elif 'prazo' in label_lower:
                        name_inferido = 'prazo_entrega'
                    elif 'formato' in label_lower and 'arquivo' in label_lower:
                        name_inferido = 'formato_arquivo'
                    else:
                        name_inferido = f"campo_{campo['index']}"
                mapeamento[name_inferido] = campo['index']
                print(f"      '{name_inferido}': {campo['index']}  # {campo['label']}")
    
    print(f"\n{'='*80}")
    print("EXTRAÇÃO CONCLUÍDA")
    print(f"{'='*80}")

if __name__ == '__main__':
    main()

