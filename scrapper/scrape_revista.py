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
        return float(valor)
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
        
        # Aplicar quantidade ANTES de processar selects
        try:
            qtd_input = driver.find_element(By.XPATH, "//input[@type='number']")
            qtd_input.clear()
            qtd_input.send_keys(str(quantidade))
            # Disparar eventos para garantir que o JavaScript detecte a mudança
            driver.execute_script("""
                var input = arguments[0];
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            """, qtd_input)
            time.sleep(1.0)  # Aguardar mais tempo para o cálculo
        except Exception as e:
            print(f"DEBUG: ERRO ao aplicar quantidade: {e}", file=sys.stderr)
            pass
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        # Mapeamento EXATO baseado no site matriz (extraído automaticamente)
        # Ordem dos selects na página: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15
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
        
        # Ordenar campos para processar na sequência correta
        campos_ordenados = []
        max_idx = max(mapeamento.values()) if mapeamento else 0
        for idx in range(max_idx + 1):
            for campo, valor in opcoes.items():
                if campo == 'quantity':
                    continue
                if mapeamento.get(campo) == idx:
                    campos_ordenados.append((campo, valor))
                    break
        
        # Processar campos na ordem correta
        for campo, valor in campos_ordenados:
            idx = mapeamento.get(campo)
            if idx is not None and idx < len(selects):
                select = selects[idx]
                valor_str = str(valor).strip()
                opcao_encontrada = False
                
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    v_str = str(v).strip() if v else ''
                    t_str = str(t).strip() if t else ''
                    
                    if (v_str == valor_str or t_str == valor_str or 
                        valor_str in v_str or valor_str in t_str or
                        v_str in valor_str or t_str in valor_str):
                        try:
                            Select(select).select_by_value(v)
                            opcao_encontrada = True
                            time.sleep(0.4)
                            # Verificar se preço já foi calculado
                            for _ in range(20):
                                time.sleep(0.15)
                                try:
                                    preco_element = driver.find_element(By.ID, "calc-total")
                                    preco_texto = preco_element.text
                                    preco_valor = extrair_valor_preco(preco_texto)
                                    if preco_valor and preco_valor > 0:
                                        return preco_valor
                                except:
                                    pass
                            break
                        except Exception as e:
                            print(f"DEBUG: ERRO ao selecionar {campo}: {e}", file=sys.stderr)
                
                if not opcao_encontrada:
                    print(f"DEBUG: AVISO - Opção não encontrada para {campo} = {valor}", file=sys.stderr)
        
        # Reaplicar quantidade após processar todos os campos (para garantir que o preço está correto)
        try:
            qtd_input = driver.find_element(By.XPATH, "//input[@type='number']")
            valor_atual = qtd_input.get_attribute('value')
            if valor_atual != str(quantidade):
                qtd_input.clear()
                qtd_input.send_keys(str(quantidade))
                driver.execute_script("""
                    var input = arguments[0];
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                """, qtd_input)
                time.sleep(1.0)
        except:
            pass
        
        # Aguardar cálculo final
        time.sleep(1.0)
        for tentativa in range(30):
            time.sleep(0.1)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    return preco_valor
            except:
                pass
        
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
