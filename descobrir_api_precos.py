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
# NÃO usar headless para ver o que acontece
# chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--window-size=1920,1080')

# Habilitar logging de performance para capturar requisições
chrome_options.set_capability('goog:loggingPrefs', {'performance': 'ALL'})

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
    time.sleep(2)
    
    logs = driver.get_log('performance')
    
    print(f"\n📊 Total de requisições capturadas: {len(logs)}\n")
    
    apis_encontradas = []
    
    for log in logs:
        try:
            message = json.loads(log['message'])
            method = message.get('message', {}).get('method', '')
            
            # Verificar requisições de rede
            if method == 'Network.requestWillBeSent':
                request = message.get('message', {}).get('params', {}).get('request', {})
                url_request = request.get('url', '')
                method_http = request.get('method', '')
                
                # Filtrar apenas requisições relevantes
                if any(keyword in url_request.lower() for keyword in ['pricing', 'price', 'calculate', 'calc', 'api', 'preco', 'valor']):
                    apis_encontradas.append({
                        'url': url_request,
                        'method': method_http,
                        'headers': request.get('headers', {}),
                        'post_data': request.get('postData', '')
                    })
                    print(f"🔍 API encontrada:")
                    print(f"   URL: {url_request}")
                    print(f"   Method: {method_http}")
                    if request.get('postData'):
                        print(f"   POST Data: {request.get('postData')[:200]}...")
                    print()
            
            # Verificar respostas
            elif method == 'Network.responseReceived':
                response = message.get('message', {}).get('params', {}).get('response', {})
                url_response = response.get('url', '')
                
                if any(keyword in url_response.lower() for keyword in ['pricing', 'price', 'calculate', 'calc', 'api', 'preco', 'valor']):
                    print(f"📥 Resposta recebida:")
                    print(f"   URL: {url_response}")
                    print(f"   Status: {response.get('status', 'N/A')}")
                    print()
        except:
            continue
    
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
    input("\nPressione Enter para fechar o navegador...")
    driver.quit()

