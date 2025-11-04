"""
Script para fazer scraping em tempo real do preço de TABLOIDE
"""
import sys
import json
import time
import re
import random
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options

def extrair_valor_preco(texto):
    """Extrai valor numérico do preço"""
    if not texto:
        return None
    valor = re.sub(r'[R$\s.]', '', texto)
    valor = valor.replace(',', '.')
    try:
        return float(valor)
    except:
        return None

def scrape_preco_tempo_real(opcoes, quantidade):
    """
    Faz scraping do preço de TABLOIDE no site da Eskenazi em tempo real.
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-extensions')
    options.add_argument('--window-size=1920,1080')
    
    prefs = {"profile.managed_default_content_settings.images": 2}
    options.add_experimental_option("prefs", prefs)
    options.set_capability('pageLoadStrategy', 'eager')
    
    driver = None
    
    try:
        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(6)
        
        driver.get(url)
        time.sleep(random.uniform(1.0, 2.0))
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btn = Array.from(document.querySelectorAll('button')).find(
                    b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar')
                );
                if (btn) btn.click();
            """)
        except:
            pass
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        if not selects:
            return None
        
        # Tabloide NÃO tem select de quantidade
        # Mapeamento na ordem dos selects no site (0-9)
        mapeamento_tabloide = {
            'formato': 0,
            'formato_miolo_paginas': 0,  # Alias
            'papel_miolo': 1,
            'cores_miolo': 2,
            'quantidade_paginas_miolo': 3,
            'acabamento_miolo': 4,
            'acabamento_livro': 5,
            'extras': 6,
            'frete': 7,
            'verificacao_arquivo': 8,
            'prazo_entrega': 9,
        }
        
        # Ordenar campos para processar na sequência correta (0, 1, 2, 3, 4, 5, 6, 7, 8, 9)
        campos_ordenados = []
        for idx in range(10):  # 0 a 9
            for campo, valor in opcoes.items():
                if campo == 'quantity':
                    continue
                if mapeamento_tabloide.get(campo) == idx:
                    campos_ordenados.append((campo, valor))
                    break
        
        # Processar campos na ordem correta
        for campo, valor in campos_ordenados:
            idx = mapeamento_tabloide.get(campo)
            if idx is not None and idx < len(selects):
                select = selects[idx]
                opcao_encontrada = False
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    valor_str = str(valor).strip()
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
                            print(f"DEBUG: ERRO ao selecionar {campo}: {e}", file=sys.stderr)
                
                if not opcao_encontrada:
                    print(f"DEBUG: AVISO - Opção não encontrada para {campo} = {valor}", file=sys.stderr)
        
        time.sleep(0.6)
        for _ in range(30):
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
        
    except:
        return None
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def main():
    if len(sys.argv) < 2:
        resultado = {'success': False, 'error': 'Dados não fornecidos'}
        print(json.dumps(resultado))
        sys.exit(1)
    
    try:
        dados = json.loads(sys.argv[1])
        opcoes = dados.get('opcoes', {})
        quantidade = dados.get('quantidade', 50)
        
        preco = scrape_preco_tempo_real(opcoes, quantidade)
        
        if preco is not None:
            resultado = {'success': True, 'price': preco}
        else:
            resultado = {'success': False, 'error': 'Preço não encontrado'}
        
        print(json.dumps(resultado))
    except Exception as e:
        resultado = {'success': False, 'error': str(e)}
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()

