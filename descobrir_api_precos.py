#!/usr/bin/env python3
"""
Script para descobrir se há API de preços no site matriz
Monitora requisições de rede ao alterar opções
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select
import time
import json

url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"

chrome_options = Options()
# Usar headless para funcionar no VPS (mas pode comentar para ver o navegador)
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--disable-extensions')
chrome_options.add_argument('--disable-software-rasterizer')
chrome_options.add_argument('--disable-background-timer-throttling')
chrome_options.add_argument('--disable-backgrounding-occluded-windows')
chrome_options.add_argument('--disable-renderer-backgrounding')
chrome_options.add_argument('--disable-infobars')
chrome_options.add_argument('--disable-notifications')
chrome_options.add_argument('--window-size=1920,1080')
chrome_options.add_argument('--disable-setuid-sandbox')
chrome_options.add_argument('--disable-crash-reporter')
chrome_options.add_argument('--disable-logging')
chrome_options.add_argument('--log-level=3')

# Habilitar logging de performance para capturar requisições
chrome_options.set_capability('goog:loggingPrefs', {'performance': 'ALL'})

# Configurar diretório temporário para user data
import tempfile
import os
chrome_user_data_dir = tempfile.mkdtemp(prefix='chrome_user_data_')
chrome_options.add_argument(f'--user-data-dir={chrome_user_data_dir}')

# Configurar variáveis de ambiente
os.environ['SELENIUM_CACHE_DIR'] = tempfile.gettempdir()

service = Service()
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    print("="*80)
    print("DESCOBRINDO API DE PREÇOS DO SITE MATRIZ")
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
    
    print("\n📡 Monitorando requisições de rede...")
    print("   Alterando opções para detectar chamadas de API...\n")
    
    # Limpar logs anteriores
    driver.get_log('performance')
    
    # Aplicar quantidade
    try:
        qtd_input = driver.find_element(By.ID, "Q1")
        qtd_input.clear()
        qtd_input.send_keys("100")
        driver.execute_script("arguments[0].blur();", qtd_input)
        time.sleep(1)
        print("✅ Quantidade aplicada: 100")
    except:
        pass
    
    # Alterar primeiro select
    selects = driver.find_elements(By.TAG_NAME, 'select')
    if selects:
        try:
            Select(selects[0]).select_by_index(1)
            time.sleep(1)
            print("✅ Select 0 alterado")
        except:
            pass
    
    # Aguardar e capturar logs de performance
    time.sleep(3)
    
    logs = driver.get_log('performance')
    
    print(f"\n📊 Total de requisições capturadas: {len(logs)}\n")
    
    apis_encontradas = []
    respostas_encontradas = []
    
    for log in logs:
        try:
            message = json.loads(log['message'])
            method = message.get('message', {}).get('method', '')
            
            # Verificar requisições de rede
            if method == 'Network.requestWillBeSent':
                request = message.get('message', {}).get('params', {}).get('request', {})
                url_request = request.get('url', '')
                method_http = request.get('method', '')
                request_id = message.get('message', {}).get('params', {}).get('requestId', '')
                
                # Filtrar apenas requisições relevantes
                if any(keyword in url_request.lower() for keyword in ['pricing', 'price', 'calculate', 'calc', 'api', 'preco', 'valor']):
                    post_data = request.get('postData', '')
                    apis_encontradas.append({
                        'url': url_request,
                        'method': method_http,
                        'headers': request.get('headers', {}),
                        'post_data': post_data,
                        'request_id': request_id
                    })
                    print(f"🔍 API encontrada:")
                    print(f"   URL: {url_request}")
                    print(f"   Method: {method_http}")
                    if post_data:
                        try:
                            post_json = json.loads(post_data)
                            print(f"   POST Data (parsed):")
                            print(f"      Q1: {post_json.get('pricingParameters', {}).get('Q1', 'N/A')}")
                            options = post_json.get('pricingParameters', {}).get('Options', [])
                            print(f"      Options: {len(options)} opções")
                            for i, opt in enumerate(options[:3]):  # Mostrar primeiras 3
                                print(f"         [{i}] Key: {opt.get('Key', '')[:20]}..., Value: {opt.get('Value', '')}")
                            if len(options) > 3:
                                print(f"         ... e mais {len(options) - 3} opções")
                        except:
                            print(f"   POST Data (raw, first 300 chars): {post_data[:300]}...")
                    print()
            
            # Verificar respostas
            elif method == 'Network.responseReceived':
                response = message.get('message', {}).get('params', {}).get('response', {})
                url_response = response.get('url', '')
                request_id = message.get('message', {}).get('params', {}).get('requestId', '')
                
                if any(keyword in url_response.lower() for keyword in ['pricing', 'price', 'calculate', 'calc', 'api', 'preco', 'valor']):
                    respostas_encontradas.append({
                        'url': url_response,
                        'status': response.get('status', 'N/A'),
                        'headers': response.get('headers', {}),
                        'request_id': request_id
                    })
                    print(f"📥 Resposta recebida:")
                    print(f"   URL: {url_response}")
                    print(f"   Status: {response.get('status', 'N/A')}")
                    print()
        except:
            continue
    
    # Tentar obter o corpo da resposta usando driver.execute_cdp_cmd
    print("\n🔍 Tentando obter corpo das respostas...")
    try:
        for resp in respostas_encontradas:
            if resp['status'] == 200:
                # Usar CDP para obter resposta
                try:
                    # Obter requestId correspondente
                    request_id = resp.get('request_id', '')
                    if request_id:
                        response_body = driver.execute_cdp_cmd('Network.getResponseBody', {'requestId': request_id})
                        body = response_body.get('body', '')
                        
                        print(f"\n📦 Resposta completa da API:")
                        print(f"   URL: {resp['url']}")
                        try:
                            body_json = json.loads(body)
                            print(f"   Resposta (JSON):")
                            print(json.dumps(body_json, indent=2, ensure_ascii=False))
                            
                            # Extrair preço se existir
                            if 'FormattedCost' in body_json:
                                print(f"\n   💰 Preço formatado: {body_json['FormattedCost']}")
                            if 'Cost' in body_json:
                                print(f"   💰 Preço numérico: {body_json['Cost']}")
                            if 'ErrorMessage' in body_json and body_json['ErrorMessage']:
                                print(f"   ⚠️ Erro: {body_json['ErrorMessage']}")
                        except:
                            print(f"   Resposta (texto): {body[:500]}...")
                    else:
                        print(f"   ⚠️ Não foi possível obter requestId para {resp['url']}")
                except Exception as e:
                    print(f"   ⚠️ Erro ao obter corpo da resposta: {e}")
    except Exception as e:
        print(f"   ⚠️ Erro geral ao obter respostas: {e}")
    
    # Tentar encontrar funções JavaScript de cálculo
    print("\n🔍 Procurando funções JavaScript de cálculo...")
    try:
        funcoes = driver.execute_script("""
            var funcoes = [];
            
            // Procurar funções comuns
            if (typeof calculatePrice === 'function') {
                funcoes.push('calculatePrice');
            }
            if (typeof calcPrice === 'function') {
                funcoes.push('calcPrice');
            }
            if (typeof getPrice === 'function') {
                funcoes.push('getPrice');
            }
            if (typeof calcularPreco === 'function') {
                funcoes.push('calcularPreco');
            }
            
            // Procurar no window
            for (var prop in window) {
                if (typeof window[prop] === 'function' && 
                    (prop.toLowerCase().includes('price') || 
                     prop.toLowerCase().includes('preco') ||
                     prop.toLowerCase().includes('calc'))) {
                    funcoes.push(prop);
                }
            }
            
            return funcoes;
        """)
        
        if funcoes:
            print(f"   Funções encontradas: {funcoes}")
        else:
            print("   Nenhuma função de cálculo encontrada")
    except Exception as e:
        print(f"   Erro: {e}")
    
    # Verificar se há variáveis globais com preços
    print("\n🔍 Procurando variáveis globais com preços...")
    try:
        variaveis = driver.execute_script("""
            var vars = [];
            
            // Procurar priceMatrix, precos, etc
            if (typeof priceMatrix !== 'undefined') {
                vars.push('priceMatrix');
            }
            if (typeof precos !== 'undefined') {
                vars.push('precos');
            }
            if (typeof pricingData !== 'undefined') {
                vars.push('pricingData');
            }
            
            return vars;
        """)
        
        if variaveis:
            print(f"   Variáveis encontradas: {variaveis}")
        else:
            print("   Nenhuma variável de preços encontrada")
    except Exception as e:
        print(f"   Erro: {e}")
    
    # Listar todas as requisições XHR/Fetch
    print("\n📡 Listando todas as requisições XHR/Fetch...")
    try:
        todas_requisicoes = driver.execute_script("""
            // Interceptar XMLHttpRequest
            var requisicoes = [];
            
            // Tentar acessar logs de performance via chrome
            return 'Verifique no DevTools > Network';
        """)
        
        print("   Para ver todas as requisições, abra o DevTools > Network")
        print("   e filtre por XHR/Fetch enquanto altera as opções")
    except:
        pass
    
    print("\n" + "="*80)
    print("RESUMO")
    print("="*80)
    print(f"APIs encontradas: {len(apis_encontradas)}")
    if apis_encontradas:
        for i, api in enumerate(apis_encontradas, 1):
            print(f"\n{i}. {api['method']} {api['url']}")
    
    print("\n💡 DICA: Para ver todas as requisições em tempo real:")
    print("   1. Abra o DevTools (F12)")
    print("   2. Vá na aba Network")
    print("   3. Filtre por XHR ou Fetch")
    print("   4. Altere as opções no formulário")
    print("   5. Veja quais requisições aparecem")
    
finally:
    try:
        driver.quit()
    except:
        pass
    # Limpar diretório temporário
    try:
        import shutil
        shutil.rmtree(chrome_user_data_dir, ignore_errors=True)
    except:
        pass

