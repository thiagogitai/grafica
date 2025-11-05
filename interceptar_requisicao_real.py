#!/usr/bin/env python3
"""
Script para interceptar a requisição real do site matriz e ver exatamente o que é enviado
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select
import time
import json

base_url = "https://www.lojagraficaeskenazi.com.br"
url = f"{base_url}/product/impressao-de-livro"

chrome_options = Options()
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--window-size=1920,1080')

service = Service()
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    print("🔍 Acessando site matriz...")
    driver.get(url)
    time.sleep(5)
    
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
    
    # Instalar interceptor para capturar requisições AJAX
    print("\n📡 Instalando interceptor de requisições...")
    
    payload_capturado = driver.execute_script("""
        window.requisicoes_capturadas = [];
        
        // Interceptar XMLHttpRequest
        var originalOpen = XMLHttpRequest.prototype.open;
        var originalSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url) {
            this._method = method;
            this._url = url;
            return originalOpen.apply(this, arguments);
        };
        
        XMLHttpRequest.prototype.send = function(data) {
            if (this._url && this._url.indexOf('pricing') >= 0 && data) {
                try {
                    var payload = typeof data === 'string' ? JSON.parse(data) : data;
                    window.requisicoes_capturadas.push({
                        url: this._url,
                        method: this._method,
                        payload: payload,
                        timestamp: Date.now()
                    });
                    console.log('📡 REQUISIÇÃO CAPTURADA:', payload);
                } catch(e) {
                    console.log('Erro ao parsear payload:', e);
                }
            }
            return originalSend.apply(this, arguments);
        };
        
        // Interceptar fetch também
        var originalFetch = window.fetch;
        window.fetch = function(url, options) {
            if (url && url.indexOf('pricing') >= 0 && options && options.body) {
                try {
                    var payload = typeof options.body === 'string' ? JSON.parse(options.body) : options.body;
                    window.requisicoes_capturadas.push({
                        url: url,
                        method: options.method || 'GET',
                        payload: payload,
                        timestamp: Date.now()
                    });
                    console.log('📡 REQUISIÇÃO FETCH CAPTURADA:', payload);
                } catch(e) {
                    console.log('Erro ao parsear fetch payload:', e);
                }
            }
            return originalFetch.apply(this, arguments);
        };
        
        return 'Interceptor instalado';
    """)
    
    print("✅ Interceptor instalado\n")
    
    # Aguardar carregamento
    time.sleep(3)
    
    # Encontrar todos os selects
    selects = driver.find_elements(By.TAG_NAME, 'select')
    print(f"📊 Total de selects encontrados: {len(selects)}\n")
    
    # Encontrar input de quantidade
    try:
        qtd_input = driver.find_element(By.ID, "Q1")
        print("✅ Input de quantidade (Q1) encontrado\n")
    except:
        print("❌ Input de quantidade não encontrado\n")
        qtd_input = None
    
    # Aplicar uma combinação simples
    print("🧪 Aplicando combinação de teste...\n")
    
    # Quantidade
    if qtd_input:
        qtd_input.clear()
        qtd_input.send_keys("50")
        time.sleep(0.5)
        driver.execute_script("arguments[0].blur();", qtd_input)
        print("✅ Quantidade: 50")
    
    # Aplicar opções nos selects (primeira opção válida de cada)
    for idx, select in enumerate(selects):
        try:
            opcoes = select.find_elements(By.TAG_NAME, 'option')
            if len(opcoes) > 1:  # Pular primeira opção vazia
                # Selecionar segunda opção (primeira válida)
                Select(select).select_by_index(1)
                texto_opcao = opcoes[1].text.strip()
                print(f"✅ Select {idx}: {texto_opcao}")
                time.sleep(0.5)
        except Exception as e:
            print(f"⚠️ Erro ao selecionar select {idx}: {e}")
    
    # Aguardar requisições
    print("\n⏳ Aguardando requisições da API...")
    time.sleep(10)
    
    # Capturar requisições
    requisicoes = driver.execute_script("return window.requisicoes_capturadas || [];")
    
    if requisicoes:
        print(f"\n✅ {len(requisicoes)} requisição(ões) capturada(s)!\n")
        print("=" * 80)
        
        for idx, req in enumerate(requisicoes, 1):
            print(f"\n📡 REQUISIÇÃO {idx}:")
            print(f"   URL: {req.get('url', 'N/A')}")
            print(f"   Method: {req.get('method', 'N/A')}")
            print(f"   Payload:")
            print(json.dumps(req.get('payload', {}), indent=4, ensure_ascii=False))
        
        # Salvar para análise
        with open('requisicao_real_capturada.json', 'w', encoding='utf-8') as f:
            json.dump(requisicoes, f, indent=2, ensure_ascii=False)
        
        print("\n💾 Requisições salvas em 'requisicao_real_capturada.json'")
    else:
        print("\n⚠️ Nenhuma requisição capturada")
        print("   Pode ser que as requisições sejam feitas de outra forma")
    
    # Manter aberto por 5 segundos
    print("\n⏳ Mantendo navegador aberto por 5 segundos...")
    time.sleep(5)
    
except Exception as e:
    print(f"❌ Erro: {e}")
    import traceback
    traceback.print_exc()
    
finally:
    driver.quit()

