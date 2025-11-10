"""
Capturar payload EXATO que o site matriz envia para impressao-de-revista
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

def capturar_payload():
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    
    driver = webdriver.Chrome(options=options)
    
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
        
        # Interceptar requisições XHR
        payload_capturado = None
        
        driver.execute_cdp_cmd('Network.enable', {})
        
        def intercept_request(request):
            nonlocal payload_capturado
            if '/pricing' in request.get('request', {}).get('url', ''):
                post_data = request.get('request', {}).get('postData', '')
                if post_data:
                    try:
                        payload_capturado = json.loads(post_data)
                        print(f"PAYLOAD CAPTURADO: {json.dumps(payload_capturado, indent=2, ensure_ascii=False)}", file=sys.stderr)
                    except:
                        pass
        
        # Aplicar opções
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        # Aplicar quantidade
        qtd_input = driver.find_element(By.ID, 'Q1')
        qtd_input.clear()
        qtd_input.send_keys('50')
        driver.execute_script("arguments[0].blur();", qtd_input)
        time.sleep(1)
        
        # Aplicar primeira opção de cada select
        valores_aplicados = []
        for idx, select in enumerate(selects):
            try:
                select_obj = Select(select)
                options = select_obj.options
                if len(options) > 1:  # Pular primeira opção (vazia)
                    select_obj.select_by_index(1)
                    valores_aplicados.append((idx, options[1].text.strip(), options[1].get_attribute('value')))
                    time.sleep(0.5)
            except:
                pass
        
        # Aguardar requisição ser enviada
        time.sleep(5)
        
        # Tentar capturar via JavaScript
        payload_js = driver.execute_script("""
            // Tentar encontrar última requisição
            if (window.lastPricingPayload) {
                return window.lastPricingPayload;
            }
            return null;
        """)
        
        if payload_js:
            print(f"PAYLOAD VIA JS: {json.dumps(payload_js, indent=2, ensure_ascii=False)}", file=sys.stderr)
        
        # Capturar via interceptação de rede (se disponível)
        logs = driver.get_log('performance')
        for log in logs:
            message = json.loads(log['message'])
            if message.get('message', {}).get('method') == 'Network.requestWillBeSent':
                request = message.get('message', {}).get('params', {}).get('request', {})
                if '/pricing' in request.get('url', ''):
                    post_data = request.get('postData', '')
                    if post_data:
                        try:
                            payload = json.loads(post_data)
                            print(f"PAYLOAD VIA LOGS: {json.dumps(payload, indent=2, ensure_ascii=False)}", file=sys.stderr)
                        except:
                            pass
        
        resultado = {
            'success': True,
            'payload': payload_capturado or payload_js,
            'valores_aplicados': valores_aplicados
        }
        
        print(json.dumps(resultado, indent=2, ensure_ascii=False))
        
    except Exception as e:
        resultado = {'success': False, 'error': str(e)}
        print(json.dumps(resultado))
        sys.exit(1)
    finally:
        driver.quit()

if __name__ == "__main__":
    capturar_payload()



