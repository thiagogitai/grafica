"""
Captura as Keys reais da API para cada produto separadamente.
Garante que cada produto use suas próprias keys específicas da API.
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def capturar_keys_produto(product_slug, max_options_per_select=10):
    """Captura keys reais para um produto específico"""
    url = f"https://www.lojagraficaeskenazi.com.br/product/{product_slug}"
    
    print(f"\n🔍 Capturando keys para: {product_slug}", file=sys.stderr)
    print(f"   URL: {url}", file=sys.stderr)
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option('useAutomationExtension', False)
    
    driver = webdriver.Chrome(options=options)
    
    # Interceptar requisições da API
    driver.execute_cdp_cmd('Network.enable', {})
    
    pricing_payloads = []
    
    def intercept_request(request):
        """Captura payloads da API de pricing"""
        if '/pricing' in request.get('request', {}).get('url', ''):
            try:
                post_data = request.get('request', {}).get('postData', '')
                if post_data:
                    payload = json.loads(post_data)
                    pricing_payloads.append({
                        'url': request['request']['url'],
                        'payload': payload,
                        'timestamp': time.time()
                    })
            except:
                pass
    
    # Adicionar listener para requisições
    driver.execute_cdp_cmd('Network.setRequestInterception', {'patterns': [{'urlPattern': '*pricing*'}]})
    
    try:
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
            time.sleep(2)
        except:
            pass
        
        # Encontrar todos os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"   ✅ Encontrados {len(selects)} selects", file=sys.stderr)
        
        if len(selects) == 0:
            return {}
        
        # Encontrar campo de quantidade
        qtd_input = None
        try:
            qtd_input = driver.find_element(By.ID, 'Q1')
        except:
            try:
                qtd_input = driver.find_element(By.CSS_SELECTOR, 'input[type="number"]')
            except:
                pass
        
        # Mapeamento de keys (texto -> key)
        mapeamento_keys = {}
        
        # Estratégia: fazer várias combinações e capturar os payloads
        # Para cada select, testar algumas opções
        total_combinations = 0
        
        # Resetar todos os selects para primeira opção
        for select in selects:
            try:
                Select(select).select_by_index(0)
            except:
                pass
        
        # Aplicar quantidade padrão
        if qtd_input:
            qtd_input.clear()
            qtd_input.send_keys('50')
            driver.execute_script("arguments[0].blur();", qtd_input)
            time.sleep(1)
        
        # Fazer várias mudanças sequenciais para capturar diferentes payloads
        for attempt in range(min(50, max_options_per_select * len(selects))):
            try:
                # Selecionar um select aleatório
                select_idx = attempt % len(selects)
                select = selects[select_idx]
                select_obj = Select(select)
                
                if len(select_obj.options) <= 1:
                    continue
                
                # Selecionar uma opção aleatória (mas não a primeira)
                opt_idx = (attempt // len(selects)) % (len(select_obj.options) - 1) + 1
                if opt_idx >= len(select_obj.options):
                    opt_idx = len(select_obj.options) - 1
                
                select_obj.select_by_index(opt_idx)
                time.sleep(1.5)  # Aguardar requisição
                
                # Capturar payloads via Network logs
                logs = driver.get_log('performance')
                for log in logs[-10:]:  # Últimos 10 logs
                    try:
                        message = json.loads(log['message'])
                        if message.get('message', {}).get('method') == 'Network.requestWillBeSent':
                            request = message.get('message', {}).get('params', {}).get('request', {})
                            if '/pricing' in request.get('url', ''):
                                post_data = request.get('postData', '')
                                if post_data:
                                    payload = json.loads(post_data)
                                    options_sent = payload.get('pricingParameters', {}).get('Options', [])
                                    
                                    # Mapear texto -> Key
                                    opt_text = select_obj.options[opt_idx].text.strip()
                                    opt_value = select_obj.options[opt_idx].get_attribute('value')
                                    
                                    for opt in options_sent:
                                        if opt.get('Value') == opt_text or opt.get('Value') == opt_value:
                                            mapeamento_keys[opt_text] = opt.get('Key')
                                            print(f"   ✅ [{select_idx}] {opt_text}: {opt.get('Key')}", file=sys.stderr)
                                            break
                    except:
                        pass
                
                total_combinations += 1
                
            except Exception as e:
                print(f"   ⚠️  Erro na tentativa {attempt}: {e}", file=sys.stderr)
                continue
        
        # Método alternativo: usar JavaScript para interceptar
        print(f"   🔄 Tentando método alternativo via JavaScript...", file=sys.stderr)
        
        # Re-executar com interceptação JavaScript
        driver.execute_script("""
            window.capturedPayloads = [];
            var originalOpen = XMLHttpRequest.prototype.open;
            var originalSend = XMLHttpRequest.prototype.send;
            
            XMLHttpRequest.prototype.open = function(method, url) {
                this._url = url;
                return originalOpen.apply(this, arguments);
            };
            
            XMLHttpRequest.prototype.send = function(data) {
                if (this._url && this._url.includes('/pricing')) {
                    try {
                        var payload = JSON.parse(data);
                        window.capturedPayloads.push({
                            url: this._url,
                            payload: payload,
                            timestamp: Date.now()
                        });
                    } catch(e) {}
                }
                return originalSend.apply(this, arguments);
            };
        """)
        
        # Fazer mais algumas mudanças
        for i in range(20):
            try:
                select_idx = i % len(selects)
                select = selects[select_idx]
                select_obj = Select(select)
                
                if len(select_obj.options) <= 1:
                    continue
                
                opt_idx = (i + 1) % (len(select_obj.options) - 1) + 1
                if opt_idx >= len(select_obj.options):
                    opt_idx = len(select_obj.options) - 1
                
                select_obj.select_by_index(opt_idx)
                time.sleep(1.5)
                
                # Capturar payloads
                payloads = driver.execute_script("return window.capturedPayloads || [];")
                
                for payload_data in payloads[-5:]:  # Últimos 5
                    options_sent = payload_data.get('payload', {}).get('pricingParameters', {}).get('Options', [])
                    
                    for opt in options_sent:
                        opt_value = opt.get('Value', '').strip()
                        opt_key = opt.get('Key', '')
                        
                        if opt_value and opt_key:
                            # Procurar qual select corresponde a este valor
                            for s_idx, s in enumerate(selects):
                                try:
                                    s_obj = Select(s)
                                    for o in s_obj.options:
                                        if o.text.strip() == opt_value or o.get_attribute('value') == opt_value:
                                            mapeamento_keys[opt_value] = opt_key
                                            print(f"   ✅ [{s_idx}] {opt_value}: {opt_key}", file=sys.stderr)
                                            break
                                except:
                                    pass
            except:
                pass
        
        print(f"   ✅ Total de keys capturadas: {len(mapeamento_keys)}", file=sys.stderr)
        
        return mapeamento_keys
        
    except Exception as e:
        import traceback
        print(f"   ❌ Erro: {e}", file=sys.stderr)
        print(traceback.format_exc(), file=sys.stderr)
        return {}
    finally:
        driver.quit()

def main():
    produtos = [
        'impressao-de-livro',
        'impressao-de-revista'
    ]
    
    resultado_completo = {
        'mapeamento_por_produto': {}
    }
    
    # Carregar mapeamento existente se houver
    try:
        with open('mapeamento_keys_todos_produtos.json', 'r', encoding='utf-8') as f:
            resultado_completo = json.load(f)
    except:
        pass
    
    for produto in produtos:
        print(f"\n{'='*60}", file=sys.stderr)
        print(f"PROCESSANDO: {produto}", file=sys.stderr)
        print(f"{'='*60}", file=sys.stderr)
        
        keys = capturar_keys_produto(produto)
        
        if keys:
            resultado_completo['mapeamento_por_produto'][produto] = keys
            print(f"✅ {produto}: {len(keys)} keys capturadas", file=sys.stderr)
        else:
            print(f"⚠️  {produto}: Nenhuma key capturada", file=sys.stderr)
    
    # Salvar resultado
    output_file = 'mapeamento_keys_todos_produtos.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(resultado_completo, f, indent=4, ensure_ascii=False)
    
    print(f"\n{'='*60}", file=sys.stderr)
    print(f"✅ RESULTADO SALVO EM: {output_file}", file=sys.stderr)
    print(f"{'='*60}", file=sys.stderr)
    
    # Estatísticas
    for produto, keys in resultado_completo['mapeamento_por_produto'].items():
        print(f"   {produto}: {len(keys)} keys", file=sys.stderr)
    
    print(json.dumps({
        'success': True,
        'total_produtos': len(resultado_completo['mapeamento_por_produto']),
        'keys_por_produto': {k: len(v) for k, v in resultado_completo['mapeamento_por_produto'].items()}
    }, indent=2))

if __name__ == "__main__":
    main()


