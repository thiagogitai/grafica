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
            
            # Contar TODAS as opções esperadas primeiro
            total_opcoes_esperadas = 0
            selects_info = []
            for idx_select, select in enumerate(selects):
                opcoes_select = select.find_elements(By.TAG_NAME, 'option')
                total_opcoes = len(opcoes_select)
                if total_opcoes > 1:
                    total_opcoes_esperadas += total_opcoes - 1  # -1 porque primeira geralmente é vazia
                    selects_info.append({
                        'index': idx_select,
                        'select': select,
                        'total_opcoes': total_opcoes,
                        'opcoes': opcoes_select
                    })
            
            print(f"   📊 Total de opções a processar: {total_opcoes_esperadas}")
            print(f"   📊 Total de selects com opções: {len(selects_info)}")
            
            # Processar TODAS as opções de TODOS os selects - SEM EXCEÇÃO
            total_keys_antes = 0
            opcoes_processadas = 0
            
            for select_info in selects_info:
                idx_select = select_info['index']
                select = select_info['select']
                total_opcoes = select_info['total_opcoes']
                opcoes_select = select_info['opcoes']
                
                print(f"   Select {idx_select}: Processando TODAS as {total_opcoes} opções...")
                
                # Processar TODAS as opções (exceto a primeira que geralmente é vazia)
                for idx_opt in range(1, total_opcoes):
                    try:
                        # Tentar até 3 vezes se necessário
                        tentativas = 0
                        sucesso = False
                        
                        while tentativas < 3 and not sucesso:
                            try:
                                # Selecionar opção
                                Select(select).select_by_index(idx_opt)
                                
                                # Aguardar requisição API (mínimo 2s para garantir)
                                time.sleep(2.0)
                                
                                # Verificar se a requisição foi feita
                                keys_atuais = driver.execute_script("return window.keys_coletadas || {};")
                                
                                # Se capturou mais Keys, foi sucesso
                                if len(keys_atuais) >= total_keys_antes:
                                    sucesso = True
                                else:
                                    tentativas += 1
                                    if tentativas < 3:
                                        time.sleep(1.0)
                                        continue
                                
                            except Exception as e:
                                tentativas += 1
                                if tentativas < 3:
                                    time.sleep(0.5)
                                    continue
                                else:
                                    print(f"     ⚠️ Erro ao selecionar opção {idx_opt} após 3 tentativas: {e}")
                        
                        # Atualizar contadores
                        keys_atuais = driver.execute_script("return window.keys_coletadas || {};")
                        keys_capturadas = len(keys_atuais)
                        
                        if keys_capturadas > total_keys_antes:
                            total_keys_antes = keys_capturadas
                        
                        opcoes_processadas += 1
                        
                        # Log a cada 50 opções ou a cada 10% do total
                        if opcoes_processadas % 50 == 0 or opcoes_processadas % max(1, total_opcoes_esperadas // 10) == 0:
                            percentual = (opcoes_processadas / total_opcoes_esperadas) * 100
                            print(f"     📈 Progresso: {opcoes_processadas}/{total_opcoes_esperadas} opções ({percentual:.1f}%), {keys_capturadas} Keys capturadas")
                            
                    except Exception as e:
                        print(f"     ❌ ERRO CRÍTICO ao processar opção {idx_opt} do select {idx_select}: {e}")
                        opcoes_processadas += 1
                        # Continuar mesmo com erro
                        pass
                
                # Aguardar um pouco mais após terminar cada select
                time.sleep(3)
                keys_atuais = driver.execute_script("return window.keys_coletadas || {};")
                print(f"   ✅ Select {idx_select} concluído: {len(keys_atuais)} Keys capturadas até agora ({opcoes_processadas}/{total_opcoes_esperadas} opções processadas)")
            
            # Aguardar todas as requisições finais
            print(f"   Aguardando requisições finais...")
            time.sleep(5)
            
            # Obter keys coletadas
            keys_coletadas = driver.execute_script("return window.keys_coletadas || {};")
            
            if keys_coletadas:
                keys_para_produto = keys_coletadas
                print(f"   ✅ Capturadas {len(keys_para_produto)} Keys para {produto}")
                
                # Verificar se capturamos todas as opções visíveis
                todas_opcoes_visiveis = driver.execute_script("""
                    var total = 0;
                    var selects = document.querySelectorAll('select');
                    for (var i = 0; i < selects.length; i++) {
                        var options = selects[i].querySelectorAll('option');
                        total += Math.max(0, options.length - 1); // -1 para excluir primeira opção vazia
                    }
                    return total;
                """)
                
                print(f"   📊 Total de opções visíveis: {todas_opcoes_visiveis}, Keys capturadas: {len(keys_para_produto)}")
                
                if len(keys_para_produto) < todas_opcoes_visiveis * 0.8:  # Se capturamos menos de 80%
                    print(f"   ⚠️ AVISO: Pode estar faltando Keys! Capturamos {len(keys_para_produto)} de ~{todas_opcoes_visiveis} opções esperadas")
                
                mapeamento_completo[produto] = keys_para_produto
            else:
                print(f"   ❌ Nenhuma Key capturada para {produto}")
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

