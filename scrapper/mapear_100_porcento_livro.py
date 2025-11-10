"""
Mapeia 100% das Keys para impressao-de-livro.
Testa TODAS as opções de TODOS os selects para garantir que nenhuma key fique faltando.
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from collections import defaultdict

def mapear_todas_keys_livro():
    """Mapeia TODAS as keys possíveis para impressao-de-livro"""
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    print(f"\n{'='*70}", file=sys.stderr)
    print(f"📚 MAPEANDO 100% DAS KEYS PARA: impressao-de-livro", file=sys.stderr)
    print(f"{'='*70}", file=sys.stderr)
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option('useAutomationExtension', False)
    
    driver = webdriver.Chrome(options=options)
    
    # Interceptar todas as requisições de pricing
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
                                timestamp: Date.now(),
                                options: payload.pricingParameters ? payload.pricingParameters.Options : []
                            });
                        } catch(e) {}
                    }
                    return originalSend.apply(this, arguments);
                };
            })();
        """
    })
    
    try:
        print(f"\n🌐 Acessando: {url}", file=sys.stderr)
        driver.get(url)
        time.sleep(5)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btns = document.querySelectorAll('button');
                for (var btn of btns) {
                    var text = btn.textContent.toLowerCase();
                    if (text.includes('aceitar') || text.includes('accept')) {
                        btn.click();
                        break;
                    }
                }
            """)
            time.sleep(2)
        except:
            pass
        
        # Encontrar todos os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"\n✅ Encontrados {len(selects)} selects", file=sys.stderr)
        
        if len(selects) == 0:
            print("❌ Nenhum select encontrado!", file=sys.stderr)
            return {}
        
        # Encontrar campo de quantidade
        qtd_input = None
        try:
            qtd_input = driver.find_element(By.ID, 'Q1')
        except:
            try:
                inputs = driver.find_elements(By.CSS_SELECTOR, 'input[type="number"]')
                if inputs:
                    qtd_input = inputs[0]
            except:
                pass
        
        # Listar TODAS as opções de TODOS os selects
        selects_info = []
        for idx, select in enumerate(selects):
            try:
                select_obj = Select(select)
                options_list = []
                for opt in select_obj.options:
                    opt_text = opt.text.strip()
                    opt_value = opt.get_attribute('value') or opt_text
                    if opt_text and opt_text != '-- Selecione --' and opt_text != 'Selecione':
                        options_list.append({
                            'text': opt_text,
                            'value': opt_value,
                            'index': len(options_list)
                        })
                
                selects_info.append({
                    'index': idx,
                    'options': options_list,
                    'total': len(options_list)
                })
                print(f"   Select {idx}: {len(options_list)} opções", file=sys.stderr)
            except Exception as e:
                print(f"   ⚠️  Erro ao processar select {idx}: {e}", file=sys.stderr)
                continue
        
        # Mapeamento completo: texto -> Key
        mapeamento_completo = {}
        
        # Estratégia melhorada: fazer múltiplas rodadas com diferentes combinações
        total_teste = 0
        total_capturadas = 0
        
        print(f"\n🔄 FASE 1: Testando TODAS as opções de cada select individualmente...", file=sys.stderr)
        
        # FASE 1: Para cada select, testar TODAS as suas opções
        for select_idx, select_info in enumerate(selects_info):
            select = selects[select_idx]
            print(f"\n   📋 Select {select_idx} ({select_info['total']} opções):", file=sys.stderr)
            
            for opt_idx_in_list, opt_info in enumerate(select_info['options']):
                try:
                    # Resetar todos os selects
                    for s_idx, s in enumerate(selects):
                        try:
                            Select(s).select_by_index(0)
                        except:
                            pass
                    time.sleep(0.2)
                    
                    # Aplicar quantidade
                    if qtd_input:
                        qtd_input.clear()
                        qtd_input.send_keys('50')
                        driver.execute_script("arguments[0].blur();", qtd_input)
                        time.sleep(0.3)
                    
                    # Limpar payloads anteriores
                    driver.execute_script("window.capturedPricingPayloads = [];")
                    
                    # Selecionar a opção atual
                    select_obj = Select(select)
                    # Encontrar índice real
                    opt_real_idx = None
                    for i, opt in enumerate(select_obj.options):
                        if opt.text.strip() == opt_info['text']:
                            opt_real_idx = i
                            break
                    
                    if opt_real_idx is None:
                        continue
                    
                    select_obj.select_by_index(opt_real_idx)
                    time.sleep(0.5)
                    
                    # Selecionar outras opções válidas nos outros selects
                    for other_idx in range(len(selects)):
                        if other_idx != select_idx:
                            try:
                                other_select = selects[other_idx]
                                other_obj = Select(other_select)
                                if len(other_obj.options) > 1:
                                    # Escolher uma opção válida (não primeira, que é geralmente vazia)
                                    for test_idx in range(1, min(len(other_obj.options), 4)):
                                        try:
                                            opt_text = other_obj.options[test_idx].text.strip()
                                            if opt_text and opt_text != '-- Selecione --':
                                                other_obj.select_by_index(test_idx)
                                                break
                                        except:
                                            pass
                            except:
                                pass
                    
                    time.sleep(2.5)  # Aguardar requisição
                    
                    # Capturar payloads
                    payloads = driver.execute_script("return window.capturedPricingPayloads || [];")
                    
                    # Processar TODAS as opções do payload (não só a do select atual)
                    for payload_data in payloads:
                        try:
                            options_sent = payload_data.get('options', [])
                            if not options_sent:
                                options_sent = payload_data.get('payload', {}).get('pricingParameters', {}).get('Options', [])
                            
                            for opt in options_sent:
                                opt_value = opt.get('Value', '')
                                opt_key = opt.get('Key', '')
                                
                                if opt_value and opt_key:
                                    # Adicionar TODAS as opções encontradas
                                    if opt_value not in mapeamento_completo:
                                        mapeamento_completo[opt_value] = opt_key
                                        total_capturadas += 1
                                        print(f"      ✅ [{select_idx}] {opt_value[:60]}: {opt_key}", file=sys.stderr)
                        except:
                            pass
                    
                    total_teste += 1
                    
                except Exception as e:
                    print(f"      ⚠️  Erro: {e}", file=sys.stderr)
                    continue
        
        print(f"\n🔄 FASE 2: Testando combinações aleatórias para capturar keys faltantes...", file=sys.stderr)
        
        # FASE 2: Fazer combinações aleatórias para pegar keys que podem ter sido perdidas
        keys_antes = len(mapeamento_completo)
        for round_num in range(100):  # 100 combinações aleatórias
            try:
                # Resetar tudo
                for s in selects:
                    try:
                        Select(s).select_by_index(0)
                    except:
                        pass
                time.sleep(0.2)
                
                if qtd_input:
                    qtd_input.clear()
                    qtd_input.send_keys('50')
                    driver.execute_script("arguments[0].blur();", qtd_input)
                    time.sleep(0.3)
                
                driver.execute_script("window.capturedPricingPayloads = [];")
                
                # Selecionar opções aleatórias em múltiplos selects
                for select_idx, select_info in enumerate(selects_info):
                    if len(select_info['options']) > 1:
                        try:
                            select_obj = Select(selects[select_idx])
                            # Escolher uma opção aleatória (não vazia)
                            opt_idx = (round_num * (select_idx + 1)) % (len(select_info['options']) - 1) + 1
                            if opt_idx >= len(select_obj.options):
                                opt_idx = len(select_obj.options) - 1
                            select_obj.select_by_index(opt_idx)
                        except:
                            pass
                
                time.sleep(2.5)
                
                # Capturar payloads
                payloads = driver.execute_script("return window.capturedPricingPayloads || [];")
                
                for payload_data in payloads:
                    try:
                        options_sent = payload_data.get('options', [])
                        if not options_sent:
                            options_sent = payload_data.get('payload', {}).get('pricingParameters', {}).get('Options', [])
                        
                        for opt in options_sent:
                            opt_value = opt.get('Value', '')
                            opt_key = opt.get('Key', '')
                            
                            if opt_value and opt_key and opt_value not in mapeamento_completo:
                                mapeamento_completo[opt_value] = opt_key
                                total_capturadas += 1
                                print(f"      ✅ Nova key: {opt_value[:60]}: {opt_key}", file=sys.stderr)
                    except:
                        pass
                
                total_teste += 1
                
            except:
                continue
        
        print(f"\n   FASE 2: {len(mapeamento_completo) - keys_antes} novas keys capturadas", file=sys.stderr)
        
        print(f"\n{'='*70}", file=sys.stderr)
        print(f"📊 RESULTADO:", file=sys.stderr)
        print(f"   Total de testes: {total_teste}", file=sys.stderr)
        print(f"   Total de keys capturadas: {len(mapeamento_completo)}", file=sys.stderr)
        print(f"{'='*70}", file=sys.stderr)
        
        return mapeamento_completo
        
    except Exception as e:
        import traceback
        print(f"\n❌ ERRO GERAL: {e}", file=sys.stderr)
        print(traceback.format_exc(), file=sys.stderr)
        return {}
    finally:
        driver.quit()

def main():
    # Mapear todas as keys
    mapeamento = mapear_todas_keys_livro()
    
    if not mapeamento:
        print(json.dumps({
            'success': False,
            'error': 'Nenhuma key foi capturada'
        }, indent=2))
        sys.exit(1)
    
    # Carregar arquivo existente
    arquivo = 'mapeamento_keys_todos_produtos.json'
    try:
        with open(arquivo, 'r', encoding='utf-8') as f:
            resultado = json.load(f)
    except:
        resultado = {'mapeamento_por_produto': {}}
    
    # Garantir estrutura
    if 'mapeamento_por_produto' not in resultado:
        resultado['mapeamento_por_produto'] = {}
    
    # Atualizar mapeamento de impressao-de-livro
    resultado['mapeamento_por_produto']['impressao-de-livro'] = mapeamento
    
    # Salvar
    with open(arquivo, 'w', encoding='utf-8') as f:
        json.dump(resultado, f, indent=4, ensure_ascii=False)
    
    print(f"\n✅ Mapeamento salvo em: {arquivo}", file=sys.stderr)
    print(f"   Total de keys para impressao-de-livro: {len(mapeamento)}", file=sys.stderr)
    
    # Listar todas as keys encontradas (primeiras 10)
    print(f"\n📋 Primeiras 10 keys encontradas:", file=sys.stderr)
    for i, (texto, key) in enumerate(list(mapeamento.items())[:10]):
        print(f"   {i+1}. {texto[:50]}: {key}", file=sys.stderr)
    
    # Resultado JSON
    print(json.dumps({
        'success': True,
        'total_keys': len(mapeamento),
        'keys': dict(list(mapeamento.items())[:20])  # Primeiras 20 para preview
    }, indent=2, ensure_ascii=False))

if __name__ == "__main__":
    main()

