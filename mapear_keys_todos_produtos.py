#!/usr/bin/env python3
"""
Script para mapear Keys de TODOS os produtos de uma vez
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

# Lista de TODOS os produtos
PRODUTOS = [
    'impressao-de-revista',
    'impressao-de-tabloide',
    'impressao-de-livro',
    'impressao-de-panfleto',
    'impressao-de-apostila',
    'impressao-online-de-livretos-personalizados',
    'impressao-de-jornal-de-bairro',
    'impressao-de-guia-de-bairro',
]

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

mapeamento_completo = {}

try:
    print("="*80)
    print("MAPEANDO KEYS DE TODOS OS PRODUTOS")
    print("="*80)
    print(f"Total de produtos: {len(PRODUTOS)}\n")
    
    for idx, produto in enumerate(PRODUTOS, 1):
        print("="*80)
        print(f"PRODUTO {idx}/{len(PRODUTOS)}: {produto}")
        print("="*80)
        
        url = f"{base_url}/product/{produto}"
        
        try:
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
            
            # Instalar interceptor JavaScript
            driver.execute_script("""
                window.keys_coletadas = {};
                
                var originalOpen = XMLHttpRequest.prototype.open;
                var originalSend = XMLHttpRequest.prototype.send;
                
                XMLHttpRequest.prototype.open = function(method, url) {
                    this._url = url;
                    this._method = method;
                    return originalOpen.apply(this, arguments);
                };
                
                XMLHttpRequest.prototype.send = function(data) {
                    if (this._url && this._url.indexOf('pricing') >= 0 && data) {
                        try {
                            var payload = typeof data === 'string' ? JSON.parse(data) : data;
                            if (payload.pricingParameters && payload.pricingParameters.Options) {
                                var options = payload.pricingParameters.Options;
                                for (var i = 0; i < options.length; i++) {
                                    var opt = options[i];
                                    if (opt.Key && opt.Value) {
                                        window.keys_coletadas[opt.Value.trim()] = opt.Key;
                                    }
                                }
                            }
                        } catch(e) {
                            console.log('Erro ao parsear payload:', e);
                        }
                    }
                    return originalSend.apply(this, arguments);
                };
            """)
            
            # Alterar alguns selects para capturar Keys
            selects = driver.find_elements(By.TAG_NAME, 'select')
            print(f"   Encontrados {len(selects)} selects")
            
            keys_para_produto = {}
            
            # Para cada select, alterar algumas opções
            for idx_select, select in enumerate(selects):
                opcoes_select = select.find_elements(By.TAG_NAME, 'option')
                total_opcoes = len(opcoes_select)
                
                if total_opcoes <= 1:
                    continue
                
                # Selecionar 2-3 opções representativas
                indices_para_testar = [1]  # Segunda opção
                if total_opcoes > 2:
                    indices_para_testar.append(min(3, total_opcoes - 1))
                if total_opcoes > 5:
                    indices_para_testar.append(min(5, total_opcoes - 1))
                
                for idx_opt in indices_para_testar[:2]:  # Máximo 2 por select
                    try:
                        Select(select).select_by_index(idx_opt)
                        time.sleep(1.5)
                    except:
                        pass
            
            # Aguardar todas as requisições
            time.sleep(3)
            
            # Obter keys coletadas
            keys_coletadas = driver.execute_script("return window.keys_coletadas || {};")
            
            if keys_coletadas:
                keys_para_produto = keys_coletadas
                print(f"   ✅ Capturadas {len(keys_para_produto)} Keys")
                mapeamento_completo[produto] = keys_para_produto
            else:
                print(f"   ⚠️ Nenhuma Key capturada para {produto}")
                mapeamento_completo[produto] = {}
            
        except Exception as e:
            print(f"   ❌ Erro ao processar {produto}: {e}")
            mapeamento_completo[produto] = {}
    
    # Salvar mapeamento completo
    resultado = {
        'mapeamento_por_produto': mapeamento_completo,
        'total_produtos': len(PRODUTOS),
        'produtos_com_keys': len([p for p, keys in mapeamento_completo.items() if keys]),
        'data_mapeamento': time.strftime('%Y-%m-%d %H:%M:%S')
    }
    
    # Também criar um mapeamento unificado (todas as keys juntas)
    keys_unificadas = {}
    for produto, keys in mapeamento_completo.items():
        keys_unificadas.update(keys)
    
    resultado['keys_reais'] = keys_unificadas
    resultado['total_keys_unificadas'] = len(keys_unificadas)
    
    with open('mapeamento_keys_todos_produtos.json', 'w', encoding='utf-8') as f:
        json.dump(resultado, f, indent=2, ensure_ascii=False)
    
    print("\n" + "="*80)
    print("RESUMO FINAL")
    print("="*80)
    print(f"✅ Produtos processados: {len(PRODUTOS)}")
    print(f"✅ Produtos com Keys: {resultado['produtos_com_keys']}")
    print(f"✅ Total de Keys únicas: {len(keys_unificadas)}")
    
    for produto, keys in mapeamento_completo.items():
        print(f"   - {produto}: {len(keys)} Keys")
    
    print(f"\n✅ Mapeamento salvo em 'mapeamento_keys_todos_produtos.json'")
    
    # Validar se temos Keys suficientes
    if resultado['produtos_com_keys'] == 0:
        print("\n❌ ERRO: Nenhuma Key foi encontrada!")
        exit(1)
    
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

