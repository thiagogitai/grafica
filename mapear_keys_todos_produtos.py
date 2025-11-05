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
            print(f"   ⏳ Aguardando requisições finais...")
            time.sleep(10)  # Mais tempo para garantir todas as requisições
            
            # Obter keys coletadas
            keys_coletadas = driver.execute_script("return window.keys_coletadas || {};")
            keys_capturadas = len(keys_coletadas)
            
            print(f"\n   📊 VERIFICAÇÃO FINAL:")
            print(f"   📊 Opções processadas: {opcoes_processadas}/{total_opcoes_esperadas}")
            print(f"   📊 Keys capturadas: {keys_capturadas}")
            
            # Verificar se todas as Keys foram capturadas - fazer múltiplas passadas se necessário
            percentual_capturado = (keys_capturadas / total_opcoes_esperadas * 100) if total_opcoes_esperadas > 0 else 0
            print(f"   📊 Percentual capturado: {percentual_capturado:.1f}%")
            
            # Se capturou menos de 100%, fazer passadas adicionais até chegar a 100%
            passada = 1
            keys_antes_passada = keys_capturadas
            max_passadas = 10  # Aumentar limite de passadas para garantir 100%
            
            while percentual_capturado < 100.0 and passada <= max_passadas:
                print(f"\n   🔄 PASADA ADICIONAL {passada}/{max_passadas} (capturado: {percentual_capturado:.2f}%, faltam: {total_opcoes_esperadas - keys_capturadas})...")
                time.sleep(5)
                
                # Processar novamente todas as opções (pode ter perdido algumas requisições)
                opcoes_na_passada = 0
                for select_info in selects_info:
                    idx_select = select_info['index']
                    select = select_info['select']
                    total_opcoes = select_info['total_opcoes']
                    
                    for idx_opt in range(1, total_opcoes):
                        try:
                            Select(select).select_by_index(idx_opt)
                            time.sleep(3.0)  # Mais tempo na passada adicional para garantir
                            opcoes_na_passada += 1
                            
                            # Verificar progresso a cada 50 opções
                            if opcoes_na_passada % 50 == 0:
                                keys_temp = driver.execute_script("return window.keys_coletadas || {};")
                                print(f"     Progresso passada {passada}: {opcoes_na_passada} opções processadas, {len(keys_temp)} Keys capturadas")
                        except Exception as e:
                            print(f"     ⚠️ Erro na passada {passada}, select {idx_select}, opção {idx_opt}: {e}")
                            pass
                
                time.sleep(15)  # Mais tempo para garantir todas as requisições
                keys_coletadas = driver.execute_script("return window.keys_coletadas || {};")
                keys_capturadas = len(keys_coletadas)
                percentual_capturado = (keys_capturadas / total_opcoes_esperadas * 100) if total_opcoes_esperadas > 0 else 0
                keys_novas = keys_capturadas - keys_antes_passada
                
                print(f"   ✅ Após passada {passada}: {keys_capturadas} Keys ({percentual_capturado:.2f}%), +{keys_novas} novas")
                
                # Se não aumentou nada, fazer mais uma tentativa com estratégia diferente
                if keys_novas == 0 and percentual_capturado < 100.0:
                    print(f"   ⚠️ Nenhuma Key nova. Tentando estratégia diferente...")
                    # Tentar selecionar opções em ordem reversa
                    for select_info in reversed(selects_info):
                        select = select_info['select']
                        total_opcoes = select_info['total_opcoes']
                        for idx_opt in range(total_opcoes - 1, 0, -1):  # Ordem reversa
                            try:
                                Select(select).select_by_index(idx_opt)
                                time.sleep(3.0)
                            except:
                                pass
                    time.sleep(15)
                    keys_coletadas = driver.execute_script("return window.keys_coletadas || {};")
                    keys_capturadas = len(keys_coletadas)
                    percentual_capturado = (keys_capturadas / total_opcoes_esperadas * 100) if total_opcoes_esperadas > 0 else 0
                    print(f"   ✅ Após estratégia reversa: {keys_capturadas} Keys ({percentual_capturado:.2f}%)")
                
                # Se ainda não chegou a 100%, continuar
                if percentual_capturado >= 100.0:
                    print(f"   🎉 100% ATINGIDO!")
                    break
                
                keys_antes_passada = keys_capturadas
                passada += 1
            
            # Verificação final rigorosa
            if keys_coletadas:
                keys_para_produto = keys_coletadas
                percentual_final = (len(keys_para_produto) / total_opcoes_esperadas * 100) if total_opcoes_esperadas > 0 else 0
                
                print(f"\n   ✅ RESULTADO FINAL:")
                print(f"   ✅ Keys capturadas: {len(keys_para_produto)}")
                print(f"   ✅ Opções esperadas: {total_opcoes_esperadas}")
                print(f"   ✅ Percentual: {percentual_final:.2f}%")
                
                if percentual_final >= 100.0:
                    print(f"   🎉 PERFEITO: 100% DE CAPTURA! TODAS AS KEYS FORAM CAPTURADAS!")
                elif percentual_final >= 99.0:
                    print(f"   ⚠️ QUASE: {percentual_final:.2f}% capturado. Faltam {total_opcoes_esperadas - len(keys_para_produto)} Keys.")
                elif percentual_final >= 95.0:
                    print(f"   ⚠️ ATENÇÃO: {percentual_final:.2f}% capturado. Faltam {total_opcoes_esperadas - len(keys_para_produto)} Keys.")
                else:
                    print(f"   ❌ ERRO: Apenas {percentual_final:.2f}% capturado! Faltam {total_opcoes_esperadas - len(keys_para_produto)} Keys.")
                
                mapeamento_completo[produto] = keys_para_produto
            else:
                print(f"   ❌ ERRO: Nenhuma Key capturada para {produto}")
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

