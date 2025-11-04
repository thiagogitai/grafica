"""
Versão FINAL otimizada - Combina confiabilidade + velocidade máxima
"""
import sys
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import re

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

def scrape_preco_final(opcoes, quantidade):
    """
    Versão FINAL - máxima velocidade sem perder confiabilidade
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    # Configurações MÁXIMAS de performance
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-extensions')
    options.add_argument('--disable-plugins')
    options.add_argument('--disable-background-timer-throttling')
    options.add_argument('--disable-backgrounding-occluded-windows')
    options.add_argument('--disable-renderer-backgrounding')
    options.add_argument('--disable-infobars')
    options.add_argument('--disable-notifications')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--disable-blink-features=AutomationControlled')
    
    # Desabilitar imagens
    prefs = {
        "profile.managed_default_content_settings.images": 2,
        "profile.default_content_setting_values.notifications": 2,
    }
    options.add_experimental_option("prefs", prefs)
    options.add_experimental_option('excludeSwitches', ['enable-logging'])
    options.set_capability('pageLoadStrategy', 'eager')
    
    driver = None
    
    try:
        inicio = time.time()
        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(6)
        
        # Acessar página
        driver.get(url)
        
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
        
        # Encontrar selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        if not selects:
            return None
        
        # Quantidade
        qtd_select = selects[0]
        for opt in qtd_select.find_elements(By.TAG_NAME, 'option'):
            if opt.get_attribute('value') == str(quantidade) or str(quantidade) in opt.text:
                Select(qtd_select).select_by_value(opt.get_attribute('value'))
                break
        
        # Mapeamento
        mapeamento = {
            'formato_miolo_paginas': 1, 'papel_capa': 1, 'cores_capa': 2,
            'orelha_capa': 3, 'acabamento_capa': 4, 'papel_miolo': 5,
            'cores_miolo': 6, 'miolo_sangrado': 7, 'quantidade_paginas_miolo': 8,
            'acabamento_miolo': 9, 'acabamento_livro': 10, 'guardas_livro': 11, 'extras': 12,
        }
        
        # Aplicar opções (Select é mais confiável)
        for campo, valor in opcoes.items():
            if campo == 'quantity':
                continue
            idx = mapeamento.get(campo)
            if idx and idx < len(selects):
                select = selects[idx]
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    if v == str(valor) or t == str(valor) or str(valor) in v or str(valor) in t:
                        Select(select).select_by_value(v)
                        time.sleep(0.02)  # Delay mínimo
                        break
        
        # Polling rápido
        time.sleep(0.2)
        for _ in range(20):  # 2 segundos máximo
            try:
                elem = driver.find_element(By.ID, "calc-total")
                preco = extrair_valor_preco(elem.text)
                if preco and preco > 0:
                    return preco
            except:
                pass
            time.sleep(0.1)
        
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
        print(json.dumps({'success': False, 'error': 'Dados não fornecidos'}))
        sys.exit(1)
    
    try:
        dados = json.loads(sys.argv[1])
        preco = scrape_preco_final(dados.get('opcoes', {}), dados.get('quantidade', 50))
        
        if preco is not None:
            print(json.dumps({'success': True, 'price': preco}))
        else:
            print(json.dumps({'success': False, 'error': 'Preço não encontrado'}))
    except Exception as e:
        print(json.dumps({'success': False, 'error': str(e)}))

if __name__ == "__main__":
    main()

