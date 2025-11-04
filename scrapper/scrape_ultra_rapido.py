"""
Versão ULTRA RÁPIDA - Alvo: 5 segundos
Usa todas as técnicas possíveis para maximizar velocidade
"""
import sys
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
import time
import re
import threading

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

def scrape_preco_ultra_rapido(opcoes, quantidade):
    """
    Versão ULTRA OTIMIZADA - todas as técnicas de performance
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
    options.add_argument('--disable-web-security')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_argument('--disable-logging')
    options.add_argument('--log-level=3')  # Só erros críticos
    options.add_argument('--silent')
    options.add_argument('--disable-images')
    options.add_argument('--blink-settings=imagesEnabled=false')
    
    # Desabilitar TUDO que não é essencial
    prefs = {
        "profile.managed_default_content_settings.images": 2,
        "profile.default_content_setting_values.notifications": 2,
        "profile.default_content_setting_values.media_stream": 2,
        "profile.default_content_setting_values.geolocation": 2,
        "profile.default_content_setting_values.camera": 2,
        "profile.default_content_setting_values.microphone": 2,
    }
    options.add_experimental_option("prefs", prefs)
    options.add_experimental_option('excludeSwitches', ['enable-logging', 'enable-automation'])
    options.add_experimental_option('useAutomationExtension', False)
    
    # Page load strategy
    options.set_capability('pageLoadStrategy', 'eager')
    
    driver = None
    
    try:
        inicio_total = time.time()
        
        # Criar driver
        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(6)  # Timeout muito reduzido
        
        # Executar JavaScript para desabilitar animações e melhorar performance
        driver.execute_cdp_cmd('Page.setDownloadBehavior', {'behavior': 'deny'})
        driver.execute_cdp_cmd('Network.setBlockedURLs', {'urls': [
            '*://*.google-analytics.com/*',
            '*://*.facebook.com/*',
            '*://*.doubleclick.net/*',
            '*://*.googletagmanager.com/*',
        ]})
        
        # Acessar página
        inicio_load = time.time()
        driver.get(url)
        
        # Aceitar cookies (muito rápido)
        try:
            driver.execute_script("""
                var btn = document.querySelector('button:contains("Aceitar")');
                if (!btn) {
                    var buttons = Array.from(document.querySelectorAll('button'));
                    btn = buttons.find(b => b.textContent.includes('Aceitar'));
                }
                if (btn) btn.click();
            """)
        except:
            pass
        
        # Encontrar selects IMEDIATAMENTE (sem esperar)
        selects = driver.find_elements(By.TAG_NAME, 'select')
        if not selects:
            return None
        
        # Quantidade - método híbrido (mais rápido e confiável)
        qtd_select = selects[0]
        opcoes_qtd = qtd_select.find_elements(By.TAG_NAME, 'option')
        for opt in opcoes_qtd:
            valor_opt = opt.get_attribute('value')
            if valor_opt == str(quantidade) or str(quantidade) in opt.text:
                driver.execute_script(f"""
                    arguments[0].value = '{valor_opt}';
                    arguments[0].dispatchEvent(new Event('change', {{bubbles: true}}));
                """, qtd_select)
                break
        
        # Mapeamento
        mapeamento_campos = {
            'formato_miolo_paginas': 1,
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
        }
        
        # Aplicar opções uma por uma (mais confiável que todas de uma vez)
        for campo_nome, valor_opcao in opcoes.items():
            if campo_nome == 'quantity':
                continue
            
            indice_select = mapeamento_campos.get(campo_nome)
            if indice_select and indice_select < len(selects):
                select = selects[indice_select]
                opcoes_list = select.find_elements(By.TAG_NAME, 'option')
                
                for opt in opcoes_list:
                    valor_opt = opt.get_attribute('value')
                    texto_opt = opt.text.strip()
                    valor_proc = str(valor_opcao).strip()
                    
                    if (valor_opt == valor_proc or 
                        texto_opt == valor_proc or
                        valor_proc in valor_opt or
                        valor_proc in texto_opt):
                        # Usar JavaScript para mudança mais rápida
                        driver.execute_script(f"""
                            arguments[0].value = '{valor_opt}';
                            var event = new Event('change', {{bubbles: true, cancelable: true}});
                            arguments[0].dispatchEvent(event);
                        """, select)
                        time.sleep(0.05)  # Delay mínimo após cada mudança
                        break
        
        # Aguardar um pouco para o cálculo acontecer
        time.sleep(0.5)
        
        # Polling ULTRA rápido para preço
        preco_valor = None
        max_tentativas = 25  # Máximo 2.5 segundos
        tentativa = 0
        
        # Primeira verificação imediata
        try:
            preco_element = driver.find_element(By.ID, "calc-total")
            preco_texto = preco_element.text
            preco_valor = extrair_valor_preco(preco_texto)
            if preco_valor and preco_valor > 0:
                return preco_valor
        except Exception as e:
            pass
        
        # Polling agressivo (100ms)
        while tentativa < max_tentativas:
            time.sleep(0.1)  # 100ms apenas
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    break
            except:
                pass
            tentativa += 1
        
        return preco_valor
        
    except Exception as e:
        return None
        
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def main():
    """Função principal"""
    if len(sys.argv) < 2:
        resultado = {'success': False, 'error': 'Dados não fornecidos'}
        print(json.dumps(resultado))
        sys.exit(1)
    
    try:
        dados = json.loads(sys.argv[1])
        opcoes = dados.get('opcoes', {})
        quantidade = dados.get('quantidade', 50)
        
        preco = scrape_preco_ultra_rapido(opcoes, quantidade)
        
        if preco is not None:
            resultado = {
                'success': True,
                'price': preco
            }
        else:
            resultado = {
                'success': False,
                'error': 'Preço não encontrado'
            }
        
        print(json.dumps(resultado))
        
    except Exception as e:
        resultado = {
            'success': False,
            'error': str(e)
        }
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()

