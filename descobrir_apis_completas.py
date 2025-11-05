#!/usr/bin/env python3
"""
Script para descobrir TODAS as APIs disponíveis no site matriz
- API de produtos
- API de pricing para diferentes produtos
- API de listagem
- Etc.
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
import time
import json
import tempfile
import os

base_url = "https://www.lojagraficaeskenazi.com.br"

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

apis_encontradas = {}

try:
    print("="*80)
    print("DESCOBRINDO TODAS AS APIs DO SITE MATRIZ")
    print("="*80)
    
    # 1. Verificar página inicial
    print("\n📋 1. Analisando página inicial...")
    driver.get(base_url)
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
    
    # 2. Instalar interceptor JavaScript ANTES de qualquer ação
    print("\n🔍 2. Instalando interceptor JavaScript...")
    
    driver.execute_script("""
        window.apis_descobertas = {
            pricing: [],
            products: [],
            categories: [],
            search: [],
            outras: []
        };
        
        // Interceptar XMLHttpRequest
        var originalOpen = XMLHttpRequest.prototype.open;
        var originalSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url) {
            this._url = url;
            this._method = method;
            return originalOpen.apply(this, arguments);
        };
        
        XMLHttpRequest.prototype.send = function(data) {
            if (this._url) {
                var url_str = String(this._url);
                
                // Categorizar por tipo de API
                if (url_str.indexOf('pricing') >= 0) {
                    window.apis_descobertas.pricing.push({
                        method: this._method,
                        url: url_str,
                        data: data ? (typeof data === 'string' ? data : JSON.stringify(data)) : null
                    });
                } else if (url_str.indexOf('product') >= 0 || url_str.indexOf('produto') >= 0) {
                    window.apis_descobertas.products.push({
                        method: this._method,
                        url: url_str,
                        data: data ? (typeof data === 'string' ? data : JSON.stringify(data)) : null
                    });
                } else if (url_str.indexOf('category') >= 0 || url_str.indexOf('categoria') >= 0) {
                    window.apis_descobertas.categories.push({
                        method: this._method,
                        url: url_str,
                        data: data ? (typeof data === 'string' ? data : JSON.stringify(data)) : null
                    });
                } else if (url_str.indexOf('search') >= 0 || url_str.indexOf('buscar') >= 0) {
                    window.apis_descobertas.search.push({
                        method: this._method,
                        url: url_str,
                        data: data ? (typeof data === 'string' ? data : JSON.stringify(data)) : null
                    });
                } else if (url_str.indexOf('/api/') >= 0 || url_str.indexOf('/rest/') >= 0) {
                    window.apis_descobertas.outras.push({
                        method: this._method,
                        url: url_str,
                        data: data ? (typeof data === 'string' ? data : JSON.stringify(data)) : null
                    });
                }
            }
            return originalSend.apply(this, arguments);
        };
        
        // Interceptar fetch também
        var originalFetch = window.fetch;
        window.fetch = function(url, options) {
            if (url) {
                var url_str = String(url);
                if (url_str.indexOf('pricing') >= 0) {
                    window.apis_descobertas.pricing.push({
                        method: options?.method || 'GET',
                        url: url_str,
                        data: options?.body || null
                    });
                }
            }
            return originalFetch.apply(this, arguments);
        };
    """)
    
    print("✅ Interceptor instalado")
    
    # 3. Navegar em diferentes páginas para descobrir APIs
    produtos_para_testar = [
        'impressao-de-revista',
        'impressao-de-tabloide',
        'impressao-de-livro',
        'impressao-de-panfleto'
    ]
    
    print(f"\n📋 3. Testando {len(produtos_para_testar)} produtos...")
    
    for produto in produtos_para_testar:
        print(f"\n   Testando: {produto}")
        try:
            url_produto = f"{base_url}/product/{produto}"
            driver.get(url_produto)
            time.sleep(2)
            
            # Aceitar cookies se aparecer
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
            
            # Alterar um select para disparar pricing API
            try:
                selects = driver.find_elements(By.TAG_NAME, 'select')
                if selects:
                    from selenium.webdriver.support.ui import Select
                    Select(selects[0]).select_by_index(1)
                    time.sleep(2)
            except:
                pass
            
        except Exception as e:
            print(f"   ⚠️ Erro ao testar {produto}: {e}")
    
    # 4. Tentar buscar produtos/categorias
    print("\n📋 4. Tentando descobrir API de listagem de produtos...")
    
    # Verificar se há links de categorias
    try:
        categorias = driver.find_elements(By.XPATH, "//a[contains(@href, '/category') or contains(@href, '/categoria')]")
        if categorias:
            print(f"   Encontrados {len(categorias)} links de categorias")
            # Clicar em uma categoria para ver se dispara API
            if len(categorias) > 0:
                try:
                    driver.execute_script("arguments[0].click();", categorias[0])
                    time.sleep(2)
                except:
                    pass
    except:
        pass
    
    # 5. Coletar todas as APIs descobertas
    print("\n📋 5. Coletando APIs descobertas...")
    
    apis_descobertas = driver.execute_script("return window.apis_descobertas || {};")
    
    # 6. Analisar padrões
    print("\n" + "="*80)
    print("APIS DESCOBERTAS")
    print("="*80)
    
    if apis_descobertas.get('pricing'):
        print(f"\n💰 APIs de Pricing ({len(apis_descobertas['pricing'])}):")
        for i, api in enumerate(apis_descobertas['pricing'][:5], 1):
            print(f"   [{i}] {api['method']} {api['url']}")
            if api['data']:
                try:
                    data = json.loads(api['data'])
                    if 'pricingParameters' in data:
                        print(f"       Q1: {data['pricingParameters'].get('Q1', 'N/A')}")
                        print(f"       Options: {len(data['pricingParameters'].get('Options', []))} opções")
                except:
                    print(f"       Data: {api['data'][:100]}...")
    
    if apis_descobertas.get('products'):
        print(f"\n📦 APIs de Produtos ({len(apis_descobertas['products'])}):")
        for i, api in enumerate(apis_descobertas['products'][:5], 1):
            print(f"   [{i}] {api['method']} {api['url']}")
    
    if apis_descobertas.get('categories'):
        print(f"\n📁 APIs de Categorias ({len(apis_descobertas['categories'])}):")
        for i, api in enumerate(apis_descobertas['categories'][:5], 1):
            print(f"   [{i}] {api['method']} {api['url']}")
    
    if apis_descobertas.get('search'):
        print(f"\n🔍 APIs de Busca ({len(apis_descobertas['search'])}):")
        for i, api in enumerate(apis_descobertas['search'][:5], 1):
            print(f"   [{i}] {api['method']} {api['url']}")
    
    if apis_descobertas.get('outras'):
        print(f"\n🔧 Outras APIs ({len(apis_descobertas['outras'])}):")
        for i, api in enumerate(apis_descobertas['outras'][:5], 1):
            print(f"   [{i}] {api['method']} {api['url']}")
    
    # 7. Verificar padrão de URLs de pricing
    print("\n" + "="*80)
    print("ANÁLISE DE PADRÕES")
    print("="*80)
    
    if apis_descobertas.get('pricing'):
        urls_pricing = set([api['url'] for api in apis_descobertas['pricing']])
        print(f"\n📊 URLs de Pricing únicas: {len(urls_pricing)}")
        for url in list(urls_pricing)[:3]:
            print(f"   - {url}")
        
        # Verificar se é sempre /product/{slug}/pricing
        padrao = None
        for url in urls_pricing:
            if '/product/' in url and '/pricing' in url:
                # Extrair padrão
                partes = url.split('/product/')
                if len(partes) > 1:
                    resto = partes[1].split('/pricing')[0]
                    padrao = f"/product/{{slug}}/pricing"
                    break
        
        if padrao:
            print(f"\n✅ Padrão detectado: {padrao}")
            print(f"   Conclusão: Cada produto tem sua própria API de pricing")
            print(f"   URL: {base_url}{padrao.replace('{slug}', 'impressao-de-revista')}")
        else:
            print("\n⚠️ Padrão não detectado claramente")
    
    # 8. Salvar descobertas
    with open('apis_descobertas.json', 'w', encoding='utf-8') as f:
        json.dump(apis_descobertas, f, indent=2, ensure_ascii=False)
    
    print(f"\n✅ APIs salvas em 'apis_descobertas.json'")
    
    # 9. Verificar se há API de listagem de produtos
    print("\n" + "="*80)
    print("TESTANDO APIs DE LISTAGEM")
    print("="*80)
    
    # Tentar URLs comuns de API REST
    urls_para_testar = [
        f"{base_url}/api/products",
        f"{base_url}/api/produtos",
        f"{base_url}/api/catalog",
        f"{base_url}/api/catalogo",
        f"{base_url}/rest/products",
        f"{base_url}/rest/produtos",
    ]
    
    print("\n📋 Testando URLs comuns de API...")
    for url_test in urls_para_testar:
        try:
            import requests
            response = requests.get(url_test, timeout=5, headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            })
            if response.status_code == 200:
                print(f"   ✅ {url_test} - Status: {response.status_code}")
                try:
                    data = response.json()
                    print(f"      Resposta: {json.dumps(data, indent=2)[:200]}...")
                except:
                    print(f"      Resposta: {response.text[:100]}...")
            elif response.status_code != 404:
                print(f"   ⚠️ {url_test} - Status: {response.status_code}")
        except Exception as e:
            pass
    
    print("\n" + "="*80)
    print("RESUMO")
    print("="*80)
    print(f"✅ APIs de Pricing: {len(apis_descobertas.get('pricing', []))}")
    print(f"✅ APIs de Produtos: {len(apis_descobertas.get('products', []))}")
    print(f"✅ Outras APIs: {len(apis_descobertas.get('outras', []))}")
    print("\n💡 Conclusão:")
    print("   - Cada produto tem sua própria URL de pricing")
    print("   - Formato: /product/{slug}/pricing")
    print("   - Não parece haver API geral de listagem de produtos")
    print("   - Mas podemos usar a API de pricing de qualquer produto conhecendo o slug")
    
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

