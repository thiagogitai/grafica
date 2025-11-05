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
    
    # Interceptar TODAS as chamadas da API para obter Keys reais
    print("\n🔍 Interceptando chamadas da API para obter Keys reais de TODAS as opções...")
    
    keys_reais = {}
    selects = driver.find_elements(By.TAG_NAME, 'select')
    
    # Para cada select, alterar todas as opções e capturar as Keys
    for idx_select, select in enumerate(selects):
        print(f"\n📋 Processando select {idx_select} ({len(select.find_elements(By.TAG_NAME, 'option'))} opções)...")
        
        opcoes_select = select.find_elements(By.TAG_NAME, 'option')
        
        for idx_opt, opt in enumerate(opcoes_select):
            if idx_opt == 0:  # Pular primeira opção (geralmente vazia)
                continue
            
            try:
                # Limpar logs
                driver.get_log('performance')
                
                # Selecionar esta opção
                Select(select).select_by_index(idx_opt)
                time.sleep(1.5)  # Aguardar chamada da API
                
                # Capturar logs
                logs = driver.get_log('performance')
                
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
                                        
                                        # Encontrar a opção correspondente a este select
                                        if idx_select < len(options):
                                            opt_data = options[idx_select]
                                            key = opt_data.get('Key', '')
                                            value = opt_data.get('Value', '').strip()
                                            
                                            if key and value:
                                                keys_reais[value] = key
                                                print(f"   ✅ [{idx_select}] '{value}' → {key[:20]}...")
                                    except Exception as e:
                                        pass
                    except:
                        continue
            except Exception as e:
                print(f"   ⚠️ Erro ao processar opção {idx_opt}: {e}")
                continue
    
    print(f"\n🔑 Total de Keys reais encontradas: {len(keys_reais)}")
    if keys_reais:
        print("\n📋 Primeiras 10 Keys:")
        for i, (value, key) in enumerate(list(keys_reais.items())[:10]):
            print(f"   [{i+1}] '{value}' → {key[:30]}...")
    
    # Validar se temos Keys suficientes
    if not keys_reais:
        print("\n❌ ERRO: Nenhuma Key foi encontrada!")
        print("   O mapeamento NÃO será salvo.")
        print("   Verifique se o site está funcionando corretamente.")
        exit(1)
    
    # Salvar mapeamento em arquivo JSON
    resultado = {
        'mapeamento_selects': mapeamento,
        'keys_reais': keys_reais,
        'total_keys': len(keys_reais),
        'data_mapeamento': time.strftime('%Y-%m-%d %H:%M:%S')
    }
    
    with open('mapeamento_keys_opcoes.json', 'w', encoding='utf-8') as f:
        json.dump(resultado, f, indent=2, ensure_ascii=False)
    
    print(f"\n✅ Mapeamento salvo em 'mapeamento_keys_opcoes.json'")
    print(f"   Total de Keys mapeadas: {len(keys_reais)}")
    
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

