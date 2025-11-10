"""
Atualiza as Keys para cada produto separadamente.
Captura as keys reais diretamente do site da matriz.
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options

def capturar_keys_produto_simples(product_slug):
    """Captura keys reais para um produto usando interceptação de requisições"""
    url = f"https://www.lojagraficaeskenazi.com.br/product/{product_slug}"
    
    print(f"\n🔍 {product_slug}...", file=sys.stderr)
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    
    driver = webdriver.Chrome(options=options)
    
    captured_payloads = []
    
    # Interceptar XMLHttpRequest
    driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
        'source': """
            (function() {
                window.capturedPricingPayloads = [];
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
                            window.capturedPricingPayloads.push({
                                url: this._url,
                                payload: payload,
                                timestamp: Date.now()
                            });
                        } catch(e) {}
                    }
                    return originalSend.apply(this, arguments);
                };
            })();
        """
    })
    
    try:
        driver.get(url)
        time.sleep(5)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btns = document.querySelectorAll('button');
                for (var btn of btns) {
                    if (btn.textContent.includes('Aceitar') || btn.textContent.includes('aceitar')) {
                        btn.click();
                        break;
                    }
                }
            """)
            time.sleep(2)
        except:
            pass
        
        # Encontrar selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"   {len(selects)} selects encontrados", file=sys.stderr)
        
        if len(selects) == 0:
            return {}
        
        # Campo de quantidade
        qtd_input = None
        try:
            qtd_input = driver.find_element(By.ID, 'Q1')
        except:
            pass
        
        # Mapeamento: texto -> Key
        mapeamento = {}
        
        # Fazer várias combinações para capturar diferentes payloads
        for round_num in range(30):  # 30 combinações diferentes
            try:
                # Resetar todos para primeira opção
                for s in selects:
                    try:
                        Select(s).select_by_index(0)
                    except:
                        pass
                time.sleep(0.3)
                
                # Aplicar quantidade
                if qtd_input:
                    qtd_input.clear()
                    qtd_input.send_keys('50')
                    driver.execute_script("arguments[0].blur();", qtd_input)
                    time.sleep(0.5)
                
                # Selecionar alguns selects aleatoriamente
                selects_to_change = []
                for i in range(min(3, len(selects))):  # Mudar até 3 selects por vez
                    idx = (round_num * 3 + i) % len(selects)
                    selects_to_change.append(idx)
                
                # Aplicar mudanças
                for select_idx in selects_to_change:
                    try:
                        select = selects[select_idx]
                        select_obj = Select(select)
                        if len(select_obj.options) > 1:
                            opt_idx = (round_num + select_idx) % (len(select_obj.options) - 1) + 1
                            if opt_idx >= len(select_obj.options):
                                opt_idx = len(select_obj.options) - 1
                            select_obj.select_by_index(opt_idx)
                    except:
                        pass
                
                time.sleep(2)  # Aguardar requisição
                
                # Capturar payloads
                payloads = driver.execute_script("return window.capturedPricingPayloads || [];")
                
                # Processar últimos payloads
                for payload_data in payloads[-5:]:
                    try:
                        options_sent = payload_data.get('payload', {}).get('pricingParameters', {}).get('Options', [])
                        
                        for opt in options_sent:
                            opt_value = opt.get('Value', '').strip()
                            opt_key = opt.get('Key', '')
                            
                            if opt_value and opt_key:
                                # Preservar espaços no final se existirem
                                original_value = opt.get('Value', '')
                                if original_value != opt_value:
                                    # Usar valor original (pode ter espaços)
                                    if original_value not in mapeamento:
                                        mapeamento[original_value] = opt_key
                                        print(f"   ✅ {original_value[:50]}: {opt_key}", file=sys.stderr)
                                else:
                                    if opt_value not in mapeamento:
                                        mapeamento[opt_value] = opt_key
                                        print(f"   ✅ {opt_value[:50]}: {opt_key}", file=sys.stderr)
                    except Exception as e:
                        pass
                
            except Exception as e:
                continue
        
        print(f"   ✅ Total: {len(mapeamento)} keys", file=sys.stderr)
        return mapeamento
        
    except Exception as e:
        import traceback
        print(f"   ❌ Erro: {e}", file=sys.stderr)
        return {}
    finally:
        driver.quit()

def main():
    produtos = ['impressao-de-livro', 'impressao-de-revista']
    
    # Carregar mapeamento existente
    arquivo = 'mapeamento_keys_todos_produtos.json'
    try:
        with open(arquivo, 'r', encoding='utf-8') as f:
            resultado = json.load(f)
    except:
        resultado = {'mapeamento_por_produto': {}}
    
    # Garantir estrutura
    if 'mapeamento_por_produto' not in resultado:
        resultado['mapeamento_por_produto'] = {}
    
    # Atualizar cada produto
    for produto in produtos:
        keys = capturar_keys_produto_simples(produto)
        if keys:
            resultado['mapeamento_por_produto'][produto] = keys
    
    # Salvar
    with open(arquivo, 'w', encoding='utf-8') as f:
        json.dump(resultado, f, indent=4, ensure_ascii=False)
    
    print(f"\n✅ Salvo em: {arquivo}", file=sys.stderr)
    
    # Estatísticas
    stats = {}
    for produto, keys in resultado['mapeamento_por_produto'].items():
        stats[produto] = len(keys)
    
    print(json.dumps({
        'success': True,
        'keys_por_produto': stats
    }, indent=2))

if __name__ == "__main__":
    main()


