"""
Script genérico para scraping de qualquer produto
Usa mapeamento baseado nos índices dos selects
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

def scrape_preco_tempo_real(opcoes, quantidade, url_produto):
    """
    Faz scraping genérico do preço em tempo real.
    """
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
        
        driver.get(url_produto)
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
        
        # Quantidade (primeiro select - índice 0)
        qtd_select = selects[0]
        for opt in qtd_select.find_elements(By.TAG_NAME, 'option'):
            if opt.get_attribute('value') == str(quantidade) or str(quantidade) in opt.text:
                Select(qtd_select).select_by_value(opt.get_attribute('value'))
                break
        
        time.sleep(0.3)
        for _ in range(12):
            time.sleep(0.1)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    return preco_valor
            except:
                pass
        
        # Aplicar outras opções (começar do índice 1, pois 0 é quantidade)
        # Os selects são mapeados sequencialmente: Options[0] = select[1], Options[1] = select[2], etc.
        for campo, valor in opcoes.items():
            if campo == 'quantity':
                continue
            
            # Tentar encontrar o select correspondente procurando em todos
            for idx, select in enumerate(selects[1:], start=1):
                try:
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
                except:
                    pass
        
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

# URLs dos produtos
PRODUTOS_URLS = {
    'impressao-de-apostila': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila',
    'impressao-online-de-livretos-personalizados': 'https://www.lojagraficaeskenazi.com.br/product/impressao-online-de-livretos-personalizados',
    'impressao-de-revista': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista',
    'impressao-de-tabloide': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide',
    'impressao-de-jornal-de-bairro': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-jornal-de-bairro',
    'impressao-de-guia-de-bairro': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-guia-de-bairro',
}

def main():
    if len(sys.argv) < 2:
        resultado = {'success': False, 'error': 'Dados não fornecidos'}
        print(json.dumps(resultado))
        sys.exit(1)
    
    try:
        dados = json.loads(sys.argv[1])
        opcoes = dados.get('opcoes', {})
        quantidade = dados.get('quantidade', 50)
        product_slug = dados.get('product_slug', '')
        
        # Determinar URL do produto
        url = PRODUTOS_URLS.get(product_slug)
        if not url:
            resultado = {'success': False, 'error': f'Produto não encontrado: {product_slug}'}
            print(json.dumps(resultado))
            sys.exit(1)
        
        preco = scrape_preco_tempo_real(opcoes, quantidade, url)
        
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

