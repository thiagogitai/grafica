"""
Remapear Keys especificamente para impressao-de-revista
Garantir 100% de precisão
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options

def mapear_keys_revista():
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    
    driver = webdriver.Chrome(options=options)
    
    try:
        # Interceptar XMLHttpRequest
        driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
            'source': """
                (function() {
                    var originalSend = XMLHttpRequest.prototype.send;
                    var payloads = [];
                    
                    XMLHttpRequest.prototype.send = function(data) {
                        if (this._url && this._url.includes('/pricing')) {
                            try {
                                var payload = JSON.parse(data);
                                payloads.push({
                                    url: this._url,
                                    payload: payload
                                });
                                window.lastPricingPayloads = payloads;
                            } catch(e) {}
                        }
                        return originalSend.apply(this, arguments);
                    };
                })();
            """
        })
        
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
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"Encontrados {len(selects)} selects", file=sys.stderr)
        
        # Mapear todas as opções de cada select
        mapeamento_keys = {}
        
        # Aplicar quantidade primeiro
        qtd_input = driver.find_element(By.ID, 'Q1')
        qtd_input.clear()
        qtd_input.send_keys('50')
        driver.execute_script("arguments[0].blur();", qtd_input)
        time.sleep(1)
        
        # Para cada select, iterar todas as opções e capturar Keys
        for select_idx in range(len(selects)):
            select = selects[select_idx]
            select_obj = Select(select)
            options_list = select_obj.options
            
            print(f"\nSelect {select_idx}: {len(options_list)} opções", file=sys.stderr)
            
            for opt_idx in range(1, min(len(options_list), 6)):  # Testar primeiras 5 opções
                try:
                    # Resetar todos os selects
                    for s in selects:
                        Select(s).select_by_index(0)
                    time.sleep(0.3)
                    
                    # Aplicar quantidade
                    qtd_input = driver.find_element(By.ID, 'Q1')
                    qtd_input.clear()
                    qtd_input.send_keys('50')
                    driver.execute_script("arguments[0].blur();", qtd_input)
                    time.sleep(0.5)
                    
                    # Aplicar opção do select atual
                    select_obj = Select(selects[select_idx])
                    select_obj.select_by_index(opt_idx)
                    time.sleep(0.5)
                    
                    # Aplicar outras opções padrão (primeira opção de cada outro select)
                    for other_idx, other_select in enumerate(selects):
                        if other_idx != select_idx:
                            try:
                                other_obj = Select(other_select)
                                if len(other_obj.options) > 1:
                                    other_obj.select_by_index(1)
                            except:
                                pass
                    
                    time.sleep(2)  # Aguardar requisição
                    
                    # Capturar payload
                    payloads = driver.execute_script("""
                        return window.lastPricingPayloads || [];
                    """)
                    
                    if payloads:
                        last_payload = payloads[-1]
                        if last_payload and 'payload' in last_payload:
                            options_sent = last_payload['payload'].get('pricingParameters', {}).get('Options', [])
                            
                            # Encontrar a Key para esta opção
                            opt_text = options_list[opt_idx].text.strip()
                            opt_value = options_list[opt_idx].get_attribute('value')
                            
                            for opt_sent in options_sent:
                                if opt_sent['Value'] == opt_text or opt_sent['Value'] == opt_value:
                                    key = opt_sent['Key']
                                    mapeamento_keys[opt_text] = key
                                    print(f"  ✅ {opt_text}: {key}", file=sys.stderr)
                                    break
                    
                except Exception as e:
                    print(f"  ❌ Erro ao processar opção {opt_idx}: {e}", file=sys.stderr)
                    continue
        
        resultado = {
            'success': True,
            'mapeamento': mapeamento_keys,
            'total_keys': len(mapeamento_keys)
        }
        
        print(json.dumps(resultado, indent=2, ensure_ascii=False))
        
    except Exception as e:
        import traceback
        resultado = {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }
        print(json.dumps(resultado, indent=2, ensure_ascii=False))
        sys.exit(1)
    finally:
        driver.quit()

if __name__ == "__main__":
    mapear_keys_revista()



