"""
Script para fazer scraping em tempo real do preço de REVISTA
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
    Faz scraping do preço de REVISTA no site da Eskenazi em tempo real.
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-extensions')
    options.add_argument('--disable-software-rasterizer')
    options.add_argument('--disable-background-timer-throttling')
    options.add_argument('--disable-backgrounding-occluded-windows')
    options.add_argument('--disable-renderer-backgrounding')
    options.add_argument('--disable-infobars')
    options.add_argument('--disable-notifications')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--disable-setuid-sandbox')
    options.add_argument('--disable-crash-reporter')
    options.add_argument('--disable-logging')
    options.add_argument('--log-level=3')
    
    # Configurar diretório temporário para o Chrome (acessível ao usuário do PHP)
    import tempfile
    import os
    chrome_user_data_dir = os.path.join(tempfile.gettempdir(), 'chrome_user_data_' + str(os.getpid()))
    os.makedirs(chrome_user_data_dir, exist_ok=True)
    options.add_argument(f'--user-data-dir={chrome_user_data_dir}')
    
    prefs = {"profile.managed_default_content_settings.images": 2}
    options.add_experimental_option("prefs", prefs)
    options.add_experimental_option('excludeSwitches', ['enable-logging'])
    options.set_capability('pageLoadStrategy', 'eager')
    
    driver = None
    
    try:
        print("DEBUG: Iniciando ChromeDriver...", file=sys.stderr)
        try:
            driver = webdriver.Chrome(options=options)
            print("DEBUG: ChromeDriver iniciado com sucesso", file=sys.stderr)
        except Exception as e:
            print(f"DEBUG: ERRO ao iniciar ChromeDriver: {type(e).__name__}: {str(e)}", file=sys.stderr)
            import traceback
            print(f"DEBUG: Traceback: {traceback.format_exc()}", file=sys.stderr)
            raise
        driver.set_page_load_timeout(6)
        
        print(f"DEBUG: Acessando URL: {url}", file=sys.stderr)
        driver.get(url)
        time.sleep(random.uniform(1.0, 2.0))
        print("DEBUG: Página carregada", file=sys.stderr)
        
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
        print(f"DEBUG: Encontrados {len(selects)} selects na página", file=sys.stderr)
        if not selects:
            print("DEBUG: Nenhum select encontrado na página", file=sys.stderr)
            return None
        
        # Revista NÃO tem select de quantidade (igual ao livreto)
        mapeamento_revista = {
            'formato_miolo_paginas': 0,
            'formato': 0,  # Aceitar também 'formato' como fallback
            'papel_capa': 1,
            'cores_capa': 2,
            'orelha_capa': 3,
            'acabamento_capa': 4,
            'papel_miolo': 5,
            'cores_miolo': 6,
            'miolo_sangrado': 7,
            'quantidade_paginas_miolo': 8,
            'acabamento_miolo': 9,
            'acabamento_livro': 10,
            'guardas_livro': 11,
            'extras': 12,
            'frete': 13,
            'verificacao_arquivo': 14,
            'prazo_entrega': 15,
        }
        
        campos_processados = 0
        for campo, valor in opcoes.items():
            if campo == 'quantity':
                continue
            
            idx = mapeamento_revista.get(campo)
            if idx is not None and idx < len(selects):
                print(f"DEBUG: Processando campo {campo} = {valor} (select index {idx})", file=sys.stderr)
                select = selects[idx]
                opcoes_encontradas = 0
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    if v == str(valor) or t == str(valor) or str(valor) in v or str(valor) in t:
                        print(f"DEBUG: Opção encontrada para {campo}: {v} / {t}", file=sys.stderr)
                        Select(select).select_by_value(v)
                        opcoes_encontradas += 1
                        campos_processados += 1
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
        
        print(f"DEBUG: Processados {campos_processados} campos. Aguardando cálculo final...", file=sys.stderr)
        time.sleep(0.6)
        for tentativa in range(30):
            time.sleep(0.1)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    print(f"DEBUG: Preço encontrado: {preco_valor} (texto: {preco_texto})", file=sys.stderr)
                    return preco_valor
            except Exception as e:
                if tentativa == 29:  # Última tentativa
                    print(f"DEBUG: Elemento calc-total não encontrado após 3 segundos. Erro: {e}", file=sys.stderr)
                pass
        
        print("DEBUG: Preço não encontrado após todas as tentativas", file=sys.stderr)
        return None
        
    except Exception as e:
        # Capturar erro para debug
        import traceback
        error_msg = str(e)
        traceback_str = traceback.format_exc()
        # Logar erro no stderr para aparecer no log do Laravel
        print(f"ERRO_NO_SCRAPER: {error_msg}", file=sys.stderr)
        print(f"TRACEBACK: {traceback_str}", file=sys.stderr)
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
            resultado = {
                'success': False, 
                'error': 'Preço não encontrado',
                'opcoes_recebidas': opcoes,
                'quantidade': quantidade
            }
        
        print(json.dumps(resultado))
    except Exception as e:
        import traceback
        traceback_str = traceback.format_exc()
        resultado = {
            'success': False, 
            'error': str(e),
            'traceback': traceback_str
        }
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()

