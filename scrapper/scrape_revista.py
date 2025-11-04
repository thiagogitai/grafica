"""
Script para fazer scraping em tempo real do preço de IMPRESSAO-DE-REVISTA
Criado automaticamente baseado na estrutura do site matriz
"""
import sys
import json
import time
import re
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

def extrair_valor_preco(texto):
    """Extrai valor numérico do preço"""
    if not texto:
        return None
    valor = re.sub(r'[R$\s.]', '', texto)
    valor = valor.replace(',', '.')
    try:
        preco = float(valor)
        # Validar se o preço é razoável (entre R$ 1 e R$ 1.000.000)
        if preco < 1 or preco > 1000000:
            print(f"DEBUG: ⚠️ Preço fora do range razoável: R$ {preco:.2f} (texto: '{texto}')", file=sys.stderr)
            return None
        return preco
    except:
        return None

def scrape_preco_tempo_real(opcoes, quantidade):
    """
    Faz scraping do preço de IMPRESSAO-DE-REVISTA no site da Eskenazi em tempo real.
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--window-size=1920,1080')
    
    import tempfile
    import os
    
    selenium_cache_dir = os.path.join(tempfile.gettempdir(), 'selenium_cache_' + str(os.getpid()))
    os.makedirs(selenium_cache_dir, exist_ok=True)
    os.environ['SELENIUM_CACHE_DIR'] = selenium_cache_dir
    
    chrome_user_data_dir = os.path.join(tempfile.gettempdir(), 'chrome_user_data_' + str(os.getpid()))
    os.makedirs(chrome_user_data_dir, exist_ok=True)
    options.add_argument(f'--user-data-dir={chrome_user_data_dir}')
    
    service = Service()
    driver = None
    
    try:
        driver = webdriver.Chrome(service=service, options=options)
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
        print(f"DEBUG: Encontrados {len(selects)} selects na página", file=sys.stderr)
        
        # Mapeamento EXATO baseado no site matriz (extraído automaticamente)
        # IMPORTANTE: Processar na sequência EXATA dos selects na página (0, 1, 2, 3...)
        # Ordem dos labels no site: 2- Formato, 3- Papel CAPA, 4- Cores CAPA, etc.
        mapeamento = {
            'formato': 0,  # 2- Formato do Miolo (Páginas):
            'papel_capa': 1,  # 3- Papel CAPA:
            'cores_capa': 2,  # 4- Cores CAPA:
            'orelha_capa': 3,  # 5 - Orelha da CAPA:
            'acabamento_capa': 4,  # 6- Acabamento CAPA:
            'papel_miolo': 5,  # 7- Papel MIOLO:
            'cores_miolo': 6,  # 8- Cores MIOLO:
            'miolo_sangrado': 7,  # 9- MIOLO Sangrado?
            'quantidade_paginas_miolo': 8,  # 10- Quantidade Paginas MIOLO:
            'acabamento_miolo': 9,  # 11- Acabamento MIOLO:
            'acabamento_livro': 10,  # 12- Acabamento LIVRO:
            'guardas_livro': 11,  # 13- Guardas LIVRO:
            'extras': 12,  # 14- Extras:
            'frete': 13,  # 15- Frete:
            'verificacao_arquivo': 14,  # 16- Verificação do Arquivo:
            'prazo_entrega': 15,  # 17- Prazo de Entrega:
        }
        
        # Preparar dados para aplicar tudo de uma vez via JavaScript
        print(f"DEBUG: Preparando para aplicar todas as opções de uma vez via JavaScript", file=sys.stderr)
        
        # Ordenar campos na sequência EXATA (0, 1, 2, 3...)
        campos_ordenados = []
        max_idx = max(mapeamento.values()) if mapeamento else 0
        for idx in range(max_idx + 1):
            for campo, valor in opcoes.items():
                if campo == 'quantity':
                    continue
                if mapeamento.get(campo) == idx:
                    campos_ordenados.append((idx, campo, valor))
                    break
        
        print(f"DEBUG: Total de campos a aplicar: {len(campos_ordenados)}", file=sys.stderr)
        
        # Aplicar TUDO de uma vez via JavaScript (como se a pessoa selecionasse tudo e desse OK)
        resultado_js = driver.execute_script("""
            // Aplicar quantidade primeiro
            var qtdInput = document.getElementById('Q1');
            if (qtdInput) {
                qtdInput.value = arguments[0];
                qtdInput.dispatchEvent(new Event('input', { bubbles: true }));
                qtdInput.dispatchEvent(new Event('change', { bubbles: true }));
                qtdInput.blur();
                if (window.jQuery) {
                    jQuery(qtdInput).val(arguments[0]).trigger('input').trigger('change').trigger('blur');
                }
            }
            
            // Aplicar todos os selects na ordem
            var selects = document.querySelectorAll('select');
            var mapeamento = arguments[1]; // Array de [idx, valor] para cada select
            var valores_aplicados = [];
            
            for (var i = 0; i < mapeamento.length; i++) {
                var idx = mapeamento[i][0];
                var valor_desejado = mapeamento[i][1];
                
                if (idx < selects.length) {
                    var select = selects[idx];
                    var opcoes = select.querySelectorAll('option');
                    var encontrado = false;
                    
                    for (var j = 0; j < opcoes.length; j++) {
                        var opt = opcoes[j];
                        var opt_value = (opt.value || '').trim();
                        var opt_text = (opt.text || '').trim();
                        var valor_str = String(valor_desejado).trim();
                        
                        if (opt_value === valor_str || opt_text === valor_str ||
                            opt_value.indexOf(valor_str) >= 0 || opt_text.indexOf(valor_str) >= 0 ||
                            valor_str.indexOf(opt_value) >= 0 || valor_str.indexOf(opt_text) >= 0) {
                            select.value = opt_value;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            if (window.jQuery) {
                                jQuery(select).val(opt_value).trigger('change');
                            }
                            valores_aplicados.push([idx, opt_value, opt_text]);
                            encontrado = true;
                            break;
                        }
                    }
                    
                    if (!encontrado) {
                        valores_aplicados.push([idx, null, 'NÃO ENCONTRADO', false]);
                    }
                }
            }
            
            // Forçar recálculo do preço (se houver função de cálculo)
            if (typeof calculatePrice === 'function') {
                try {
                    calculatePrice();
                } catch(e) {
                    console.log('Erro ao chamar calculatePrice:', e);
                }
            }
            
            return {
                quantidade_aplicada: qtdInput ? qtdInput.value : null,
                valores_aplicados: valores_aplicados,
                total_selects: selects.length
            };
        """, str(quantidade), [[idx, str(valor)] for idx, campo, valor in campos_ordenados])
        
        print(f"DEBUG: Resultado JavaScript (tudo aplicado de uma vez):", file=sys.stderr)
        print(f"DEBUG:   Quantidade aplicada: {resultado_js.get('quantidade_aplicada', 'N/A')}", file=sys.stderr)
        print(f"DEBUG:   Total de selects na página: {resultado_js.get('total_selects', 'N/A')}", file=sys.stderr)
        print(f"DEBUG:   Valores aplicados nos selects:", file=sys.stderr)
        for item in resultado_js.get('valores_aplicados', []):
            if len(item) >= 4:
                idx, value, text, sucesso = item[0], item[1], item[2], item[3]
                if not sucesso:
                    print(f"DEBUG:     Select {idx}: ❌ NÃO ENCONTRADO", file=sys.stderr)
                else:
                    print(f"DEBUG:     Select {idx}: ✅ value='{value}', text='{text}'", file=sys.stderr)
            else:
                idx, value, text = item[0], item[1], item[2]
                if value is None:
                    print(f"DEBUG:     Select {idx}: ❌ NÃO ENCONTRADO", file=sys.stderr)
                else:
                    print(f"DEBUG:     Select {idx}: ✅ value='{value}', text='{text}'", file=sys.stderr)
        
        # Aguardar cálculo após aplicar tudo (mais tempo para garantir)
        print(f"DEBUG: Aguardando cálculo do preço após aplicar todas as opções...", file=sys.stderr)
        time.sleep(3.0)  # Mais tempo para garantir que todos os cálculos foram feitos
        
        # Verificar valores finais dos selects antes de buscar preço
        print(f"DEBUG: Verificando valores finais selecionados:", file=sys.stderr)
        for idx, select in enumerate(selects):
            try:
                valor_selecionado = Select(select).first_selected_option
                valor_value = valor_selecionado.get_attribute('value')
                valor_text = valor_selecionado.text.strip()
                print(f"DEBUG: Select {idx}: value='{valor_value}', text='{valor_text}'", file=sys.stderr)
            except:
                print(f"DEBUG: Select {idx}: Nenhum valor selecionado", file=sys.stderr)
        
        # Verificar quantidade final
        try:
            qtd_final = driver.find_element(By.ID, "Q1")
            valor_qtd_final = qtd_final.get_attribute('value')
            print(f"DEBUG: Quantidade final Q1: {valor_qtd_final}", file=sys.stderr)
        except:
            print(f"DEBUG: Não foi possível verificar quantidade final", file=sys.stderr)
        
        # Aguardar cálculo final
        print(f"DEBUG: Aguardando cálculo final do preço...", file=sys.stderr)
        time.sleep(2.0)  # Mais tempo para garantir cálculo
        
        # Verificar se há algum elemento de preço antes de começar
        try:
            preco_test = driver.find_element(By.ID, "calc-total")
            print(f"DEBUG: Elemento calc-total existe: '{preco_test.text}'", file=sys.stderr)
            
            # Verificar se há outros elementos com preço na página
            try:
                todos_precos = driver.find_elements(By.XPATH, "//*[contains(text(), 'R$')]")
                print(f"DEBUG: Encontrados {len(todos_precos)} elementos com 'R$' na página", file=sys.stderr)
                for i, elem in enumerate(todos_precos[:5]):  # Mostrar primeiros 5
                    texto = elem.text.strip()
                    if len(texto) < 50:  # Apenas textos curtos (preços)
                        print(f"DEBUG:   Preço {i+1}: '{texto}'", file=sys.stderr)
            except:
                pass
        except:
            print(f"DEBUG: ⚠️ Elemento calc-total não encontrado!", file=sys.stderr)
        
        preco_valido = None
        for tentativa in range(50):  # Mais tentativas
            time.sleep(0.2)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text.strip()
                preco_valor = extrair_valor_preco(preco_texto)
                
                if tentativa % 5 == 0:  # Log a cada 5 tentativas
                    print(f"DEBUG: Tentativa {tentativa+1}: Preço texto = '{preco_texto}', Preço valor = {preco_valor}", file=sys.stderr)
                
                # Validar preço: deve ser entre R$ 1 e R$ 100.000 (preços de revista geralmente não passam disso)
                if preco_valor and 1 <= preco_valor <= 100000:
                    # Se já tinha um preço válido, verificar se é estável
                    if preco_valido and abs(preco_valor - preco_valido) < 0.01:
                        print(f"DEBUG: ✅ Preço estável encontrado na tentativa {tentativa+1}: R$ {preco_valor:.2f} (texto: '{preco_texto}')", file=sys.stderr)
                        return preco_valor
                    elif preco_valido is None:
                        preco_valido = preco_valor
                        print(f"DEBUG: ✅ Primeiro preço válido encontrado na tentativa {tentativa+1}: R$ {preco_valor:.2f} (texto: '{preco_texto}')", file=sys.stderr)
                        # Aguardar mais um pouco para confirmar estabilidade
                        time.sleep(0.5)
                        preco_valor2 = extrair_valor_preco(preco_element.text.strip())
                        if preco_valor2 and abs(preco_valor - preco_valor2) < 0.01 and 1 <= preco_valor2 <= 100000:
                            print(f"DEBUG: ✅ Preço confirmado estável: R$ {preco_valor:.2f}", file=sys.stderr)
                            return preco_valor
                elif preco_valor:
                    print(f"DEBUG: ⚠️ Preço FORA DO RANGE RAZOÁVEL (R$ 1 - R$ 100.000): R$ {preco_valor:.2f} (texto: '{preco_texto}')", file=sys.stderr)
                    print(f"DEBUG: ⚠️ Este preço será IGNORADO - pode ser valor transitório ou elemento errado", file=sys.stderr)
            except Exception as e:
                if tentativa == 0:
                    print(f"DEBUG: Erro ao buscar preço (tentativa {tentativa+1}): {e}", file=sys.stderr)
                pass
        
        # Se encontrou um preço válido mas não confirmou estabilidade, retornar mesmo assim
        if preco_valido:
            print(f"DEBUG: ⚠️ Retornando preço sem confirmação de estabilidade: R$ {preco_valido:.2f}", file=sys.stderr)
            return preco_valido
        
        print(f"DEBUG: ❌ Preço não encontrado após 30 tentativas", file=sys.stderr)
        return None
        
    except Exception as e:
        import traceback
        print(f"ERRO_NO_SCRAPER: {str(e)}", file=sys.stderr)
        print(f"TRACEBACK: {traceback.format_exc()}", file=sys.stderr)
        return None
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def main():
    if len(sys.argv) < 2:
        resultado = {'success': False, 'error': 'Dados não fornecidos'}
        print(json.dumps(resultado))
        sys.exit(1)
    
    try:
        dados = json.loads(sys.argv[1])
        opcoes = dados.get('opcoes', {})
        quantidade = dados.get('quantidade', 50)
        
        preco = scrape_preco_tempo_real(opcoes, quantidade)
        
        if preco is not None:
            resultado = {'success': True, 'price': preco}
        else:
            resultado = {'success': False, 'error': 'Preço não encontrado'}
        
        print(json.dumps(resultado))
    except Exception as e:
        resultado = {'success': False, 'error': str(e)}
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()
