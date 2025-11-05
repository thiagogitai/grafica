#!/usr/bin/env python3
"""
Script para mapear valores das opções para suas Keys (hashes) usadas na API
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select
import time
import json
import tempfile
import os

url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"

chrome_options = Options()
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--window-size=1920,1080')
chrome_options.add_argument('--disable-setuid-sandbox')
chrome_options.add_argument('--disable-crash-reporter')
chrome_options.add_argument('--disable-logging')
chrome_options.add_argument('--log-level=3')

chrome_user_data_dir = tempfile.mkdtemp(prefix='chrome_user_data_')
chrome_options.add_argument(f'--user-data-dir={chrome_user_data_dir}')
os.environ['SELENIUM_CACHE_DIR'] = tempfile.gettempdir()

service = Service()
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    print("="*80)
    print("MAPEANDO KEYS DAS OPÇÕES PARA USAR NA API")
    print("="*80)
    
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
    
    # Extrair mapeamento de Keys para valores via JavaScript
    mapeamento = driver.execute_script("""
        var selects = document.querySelectorAll('select');
        var mapeamento = {};
        
        for (var i = 0; i < selects.length; i++) {
            var select = selects[i];
            var selectId = select.id || 'select_' + i;
            var options = select.querySelectorAll('option');
            var opcoes = [];
            
            for (var j = 0; j < options.length; j++) {
                var opt = options[j];
                var value = opt.value || '';
                var text = (opt.text || '').trim();
                
                // Tentar encontrar a Key correspondente
                // A Key pode estar em um atributo data-key, data-option-key, ou ser derivada
                var key = opt.getAttribute('data-key') || 
                         opt.getAttribute('data-option-key') ||
                         opt.getAttribute('data-value-key') ||
                         '';
                
                // Se não encontrou, tentar buscar no JavaScript global
                // ou usar o value como key se parecer um hash
                if (!key && value && value.length > 20 && /^[0-9A-F]+$/i.test(value)) {
                    key = value;
                }
                
                opcoes.push({
                    value: value,
                    text: text,
                    key: key,
                    index: j
                });
            }
            
            mapeamento[selectId] = {
                index: i,
                label: select.previousElementSibling ? select.previousElementSibling.textContent : '',
                options: opcoes
            };
        }
        
        return mapeamento;
    """)
    
    print("\n📋 Mapeamento encontrado:")
    print(json.dumps(mapeamento, indent=2, ensure_ascii=False))
    
    # Tentar outra abordagem: interceptar a chamada da API para ver as Keys
    print("\n🔍 Tentando interceptar chamada da API para obter Keys reais...")
    
    # Limpar logs
    driver.get_log('performance')
    
    # Alterar um select para disparar a chamada
    selects = driver.find_elements(By.TAG_NAME, 'select')
    if selects:
        try:
            Select(selects[0]).select_by_index(1)
            time.sleep(2)
        except:
            pass
    
    # Capturar logs
    logs = driver.get_log('performance')
    keys_reais = {}
    
    for log in logs:
        try:
            message = json.loads(log['message'])
            method = message.get('message', {}).get('method', '')
            
            if method == 'Network.requestWillBeSent':
                request = message.get('message', {}).get('params', {}).get('request', {})
                url_request = request.get('url', '')
                
                if 'pricing' in url_request.lower():
                    post_data = request.get('postData', '')
                    if post_data:
                        try:
                            post_json = json.loads(post_data)
                            options = post_json.get('pricingParameters', {}).get('Options', [])
                            
                            for opt in options:
                                key = opt.get('Key', '')
                                value = opt.get('Value', '')
                                if key and value:
                                    keys_reais[value.strip()] = key
                        except:
                            pass
        except:
            continue
    
    print("\n🔑 Keys reais encontradas na chamada da API:")
    if keys_reais:
        print(json.dumps(keys_reais, indent=2, ensure_ascii=False))
    else:
        print("   Nenhuma Key encontrada. Vamos tentar outra abordagem...")
        
        # Tentar obter via função JavaScript que calcula as Keys
        keys_via_js = driver.execute_script("""
            // Procurar função que gera Keys
            var keys = {};
            
            // Tentar encontrar função que calcula Keys
            if (typeof getOptionKey === 'function') {
                console.log('Função getOptionKey encontrada');
            }
            
            // Tentar obter Keys de todos os selects
            var selects = document.querySelectorAll('select');
            for (var i = 0; i < selects.length; i++) {
                var select = selects[i];
                var options = select.querySelectorAll('option');
                
                for (var j = 0; j < options.length; j++) {
                    var opt = options[j];
                    var text = (opt.text || '').trim();
                    var value = opt.value || '';
                    
                    // Tentar várias formas de obter a Key
                    var key = opt.getAttribute('data-key') ||
                             opt.getAttribute('data-option-key') ||
                             value; // Se value já for a key
                    
                    if (key && text) {
                        keys[text] = key;
                    }
                }
            }
            
            return keys;
        """)
        
        print("\n🔑 Keys via JavaScript:")
        print(json.dumps(keys_via_js, indent=2, ensure_ascii=False))
    
    # Salvar mapeamento em arquivo JSON
    resultado = {
        'mapeamento_selects': mapeamento,
        'keys_reais': keys_reais if keys_reais else keys_via_js
    }
    
    with open('mapeamento_keys_opcoes.json', 'w', encoding='utf-8') as f:
        json.dump(resultado, f, indent=2, ensure_ascii=False)
    
    print("\n✅ Mapeamento salvo em 'mapeamento_keys_opcoes.json'")
    
finally:
    try:
        driver.quit()
    except:
        pass
    try:
        import shutil
        shutil.rmtree(chrome_user_data_dir, ignore_errors=True)
    except:
        pass

