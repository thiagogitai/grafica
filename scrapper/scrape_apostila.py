"""
Script para fazer scraping em tempo real do preço de APOSTILA
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
    Faz scraping do preço de APOSTILA no site da Eskenazi em tempo real.
    NOTA: Apostila NÃO tem select de quantidade (começa no select 0 com formato)
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila"
    
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
        
        # NOTA: Apostila NÃO tem select de quantidade visível
        # O primeiro select (índice 0) é "Formato Final Fechado"
        # Mapeamento: Options[0] = select[0], Options[1] = select[1], etc.
        
        # Aplicar opções baseado no mapeamento
        mapeamento_apostila = {
            'formato': 0,  # Options[0] = select[0]
            'papel_capa': 1,
            'cores_capa': 2,
            'acabamento_capa': 3,
            'contra_capa': 4,
            'papel_miolo_1': 5,
            'cores_miolo_1': 6,
            'quantidade_paginas_miolo_1': 7,
            'acabamento_miolo': 8,
            'papel_miolo_2': 9,
            'cores_miolo_2': 10,
            'quantidade_paginas_miolo_2': 11,
            'acabamento_livro': 12,
            'extras': 13,
            'formato_arquivo': 14,
            'verificacao_arquivo': 15,
            'prazo_entrega': 16,
        }
        
        for campo, valor in opcoes.items():
            if campo == 'quantity':
                continue
            
            idx = mapeamento_apostila.get(campo)
            if idx is not None and idx < len(selects):
                select = selects[idx]
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    if v == str(valor) or t == str(valor) or str(valor) in v or str(valor) in t:
                        Select(select).select_by_value(v)
                        time.sleep(0.3)
                        for _ in range(27):
                            time.sleep(0.1)
                            try:
                                preco_element = driver.find_element(By.ID, "calc-total")
                                preco_texto = preco_element.text
                                preco_valor = extrair_valor_preco(preco_texto)
                                if preco_valor and preco_valor > 0:
                                    return preco_valor
                            except:
                                pass
                        break
        
        # Última tentativa
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

