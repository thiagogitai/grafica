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
    
    # Usar CDP (Chrome DevTools Protocol) para interceptar requisições
    print("\n🔍 Interceptando chamadas da API via CDP para obter Keys reais...")
    
    # Habilitar Network domain no CDP
    driver.execute_cdp_cmd('Network.enable', {})
    
    keys_reais = {}
    selects = driver.find_elements(By.TAG_NAME, 'select')
    
    # Armazenar requisições capturadas
    requisicoes_capturadas = []
    
    def interceptor_request(request):
        """Intercepta requisições de pricing"""
        url_request = request.get('params', {}).get('request', {}).get('url', '')
        if 'pricing' in url_request.lower():
            post_data = request.get('params', {}).get('request', {}).get('postData', '')
            if post_data:
                requisicoes_capturadas.append({
                    'url': url_request,
                    'postData': post_data,
                    'timestamp': time.time()
                })
    
    # Para cada select, alterar opções e capturar Keys
    print(f"\n📋 Total de selects encontrados: {len(selects)}")
    
    # Estratégia: selecionar uma opção de cada select e capturar a requisição completa
    for idx_select, select in enumerate(selects):
        opcoes_select = select.find_elements(By.TAG_NAME, 'option')
        total_opcoes = len(opcoes_select)
        
        if total_opcoes <= 1:  # Pular selects vazios
            continue
        
        print(f"\n📋 Processando select {idx_select} ({total_opcoes} opções)...")
        
        # Limpar requisições anteriores
        requisicoes_capturadas.clear()
        
        # Selecionar uma opção (não a primeira que geralmente é vazia)
        idx_opt_para_testar = min(1, total_opcoes - 1)
        
        try:
            Select(select).select_by_index(idx_opt_para_testar)
            time.sleep(2)  # Aguardar chamada da API
            
            # Capturar requisições via CDP
            # Não podemos usar callback, então vamos usar execute_cdp_cmd para obter eventos
            # Melhor abordagem: usar JavaScript para interceptar XMLHttpRequest
        except Exception as e:
            print(f"   ⚠️ Erro ao selecionar opção {idx_opt_para_testar}: {e}")
            continue
    
    # Usar JavaScript para interceptar requisições
    print("\n🔍 Usando JavaScript para interceptar requisições da API...")
    
    keys_via_js = driver.execute_script("""
        var keys_coletadas = {};
        var selects = document.querySelectorAll('select');
        
        // Interceptar XMLHttpRequest
        var originalOpen = XMLHttpRequest.prototype.open;
        var originalSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._url = url;
            return originalOpen.apply(this, [method, url, ...args]);
        };
        
        XMLHttpRequest.prototype.send = function(data) {
            if (this._url && this._url.indexOf('pricing') >= 0) {
                try {
                    var payload = JSON.parse(data);
                    var options = payload.pricingParameters.Options || [];
                    
                    for (var i = 0; i < options.length; i++) {
                        var opt = options[i];
                        if (opt.Key && opt.Value) {
                            keys_coletadas[opt.Value.trim()] = opt.Key;
                        }
                    }
                } catch(e) {
                    console.log('Erro ao parsear payload:', e);
                }
            }
            return originalSend.apply(this, arguments);
        };
        
        // Alterar selects para disparar requisições
        for (var i = 0; i < selects.length; i++) {
            var select = selects[i];
            var options = select.querySelectorAll('option');
            
            // Selecionar algumas opções representativas
            for (var j = 1; j < Math.min(options.length, 5); j++) {
                select.selectedIndex = j;
                var event = new Event('change', { bubbles: true });
                select.dispatchEvent(event);
            }
        }
        
        // Aguardar um pouco para as requisições
        return new Promise(function(resolve) {
            setTimeout(function() {
                resolve(keys_coletadas);
            }, 3000);
        });
    """)
    
    # Aguardar um pouco mais
    time.sleep(2)
    
    # Tentar obter keys via JavaScript novamente
    keys_via_js_final = driver.execute_script("""
        // Retornar keys coletadas se existir
        if (typeof window.keys_coletadas !== 'undefined') {
            return window.keys_coletadas;
        }
        return {};
    """)
    
    if keys_via_js_final:
        keys_reais.update(keys_via_js_final)
    
    # Se ainda não temos keys, tentar uma abordagem mais direta: fazer algumas requisições manualmente
    if not keys_reais:
        print("\n⚠️ Não foi possível interceptar via JavaScript. Tentando abordagem direta...")
        
        # Pegar todas as opções de todos os selects e seus valores
        todas_opcoes = driver.execute_script("""
            var resultado = {};
            var selects = document.querySelectorAll('select');
            
            for (var i = 0; i < selects.length; i++) {
                var select = selects[i];
                var options = select.querySelectorAll('option');
                var opcoes_select = [];
                
                for (var j = 0; j < options.length; j++) {
                    var opt = options[j];
                    var text = (opt.text || '').trim();
                    var value = opt.value || '';
                    
                    if (text && value && j > 0) { // Pular primeira opção
                        opcoes_select.push({
                            text: text,
                            value: value
                        });
                    }
                }
                
                if (opcoes_select.length > 0) {
                    resultado[i] = opcoes_select;
                }
            }
            
            return resultado;
        """)
        
        print(f"📋 Opções extraídas de {len(todas_opcoes)} selects")
        
        # Agora vamos fazer algumas requisições manuais para obter as Keys
        # Mas isso requer que saibamos como construir a requisição corretamente
        # Por enquanto, vamos usar uma abordagem mais simples: fazer algumas alterações e capturar
    
    # Se ainda não temos keys, usar o valor do option como key (pode ser que já seja a key)
    if not keys_reais and todas_opcoes:
        print("\n🔍 Tentando usar valores dos options como Keys...")
        for select_idx, opcoes_select in todas_opcoes.items():
            for opt in opcoes_select[:10]:  # Limitar a 10 por select para não sobrecarregar
                text = opt['text']
                value = opt['value']
                
                # Se o value parece uma key (hash longo), usar diretamente
                if value and len(value) > 20 and all(c in '0123456789ABCDEFabcdef' for c in value):
                    keys_reais[text] = value
                    print(f"   ✅ Select {select_idx}: '{text}' → {value[:20]}...")
    
    print(f"\n🔑 Total de Keys encontradas: {len(keys_reais)}")
    if keys_reais:
        print("\n📋 Primeiras 10 Keys:")
        for i, (value, key) in enumerate(list(keys_reais.items())[:10]):
            print(f"   [{i+1}] '{value}' → {key[:30]}...")
    else:
        print("\n⚠️ Nenhuma Key encontrada ainda. Tentando última abordagem...")
        
        # Última tentativa: fazer uma requisição manual e ver o que acontece
        # Mas para isso precisaríamos saber como a API funciona internamente
        # Por enquanto, vamos salvar o que temos e pedir para o usuário verificar
    
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

