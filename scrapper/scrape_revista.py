"""
Script para fazer scraping em tempo real do preço de REVISTA
"""
import sys
import json
import time
import re
import random
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
import logging

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
    Faz scraping do preço de REVISTA no site da Eskenazi em tempo real.
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista"
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-extensions')
    options.add_argument('--disable-software-rasterizer')
    options.add_argument('--disable-background-timer-throttling')
    options.add_argument('--disable-backgrounding-occluded-windows')
    options.add_argument('--disable-renderer-backgrounding')
    options.add_argument('--disable-infobars')
    options.add_argument('--disable-notifications')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--disable-setuid-sandbox')
    options.add_argument('--disable-crash-reporter')
    options.add_argument('--disable-logging')
    options.add_argument('--log-level=3')
    
    # Configurar diretórios temporários para o Chrome e Selenium (acessíveis ao usuário do PHP)
    import tempfile
    import os
    
    # Configurar diretório de cache do Selenium para evitar problemas de permissão
    selenium_cache_dir = os.path.join(tempfile.gettempdir(), 'selenium_cache_' + str(os.getpid()))
    os.makedirs(selenium_cache_dir, exist_ok=True)
    os.environ['SELENIUM_CACHE_DIR'] = selenium_cache_dir
    
    # Configurar diretório de dados do usuário do Chrome
    chrome_user_data_dir = os.path.join(tempfile.gettempdir(), 'chrome_user_data_' + str(os.getpid()))
    os.makedirs(chrome_user_data_dir, exist_ok=True)
    options.add_argument(f'--user-data-dir={chrome_user_data_dir}')
    
    # Configurar HOME temporário se necessário
    if not os.access(os.path.expanduser('~/.cache'), os.W_OK):
        os.environ['HOME'] = tempfile.gettempdir()
        print(f"DEBUG: HOME temporário configurado: {tempfile.gettempdir()}", file=sys.stderr)
    
    prefs = {"profile.managed_default_content_settings.images": 2}
    options.add_experimental_option("prefs", prefs)
    options.add_experimental_option('excludeSwitches', ['enable-logging'])
    options.set_capability('pageLoadStrategy', 'eager')
    
    driver = None
    
    try:
        print("DEBUG: Iniciando ChromeDriver...", file=sys.stderr)
        try:
            # Tentar criar Service com logs (opcional)
            try:
                service = Service()
                # Tentar configurar log se possível
                try:
                    service.log_path = '/tmp/chromedriver.log'
                except:
                    pass
                driver = webdriver.Chrome(service=service, options=options)
            except Exception as service_error:
                # Se Service falhar, tentar sem Service
                print(f"DEBUG: Service falhou, tentando sem Service: {service_error}", file=sys.stderr)
                driver = webdriver.Chrome(options=options)
            print("DEBUG: ChromeDriver iniciado com sucesso", file=sys.stderr)
        except Exception as e:
            print(f"DEBUG: ERRO ao iniciar ChromeDriver: {type(e).__name__}: {str(e)}", file=sys.stderr)
            import traceback
            print(f"DEBUG: Traceback: {traceback.format_exc()}", file=sys.stderr)
            # Tentar ler log do ChromeDriver se existir
            try:
                with open('/tmp/chromedriver.log', 'r') as f:
                    log_content = f.read()
                    if log_content:
                        print(f"DEBUG: ChromeDriver log: {log_content[-2000:]}", file=sys.stderr)
            except:
                pass
            raise
        driver.set_page_load_timeout(6)
        
        print(f"DEBUG: Acessando URL: {url}", file=sys.stderr)
        driver.get(url)
        time.sleep(random.uniform(1.0, 2.0))
        print("DEBUG: Página carregada", file=sys.stderr)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btn = Array.from(document.querySelectorAll('button')).find(
                    b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar')
                );
                if (btn) btn.click();
            """)
        except:
            pass
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"DEBUG: Encontrados {len(selects)} selects na página", file=sys.stderr)
        if not selects:
            print("DEBUG: Nenhum select encontrado na página", file=sys.stderr)
            return None
        
        # Mapeamento completo baseado no site matriz (https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista)
        # Ordem dos selects na página: 0-15 (16 selects no total)
        mapeamento_revista = {
            # Select 0: Formato do Miolo (Páginas)
            'formato': 0,
            'formato_miolo_paginas': 0,  # Alias alternativo
            # Select 1: Papel CAPA
            'papel_capa': 1,
            # Select 2: Cores CAPA
            'cores_capa': 2,
            # Select 3: Orelha da CAPA
            'orelha_capa': 3,
            # Select 4: Acabamento CAPA
            'acabamento_capa': 4,
            # Select 5: Papel MIOLO
            'papel_miolo': 5,
            # Select 6: Cores MIOLO
            'cores_miolo': 6,
            # Select 7: MIOLO Sangrado?
            'miolo_sangrado': 7,
            # Select 8: Quantidade Paginas MIOLO
            'quantidade_paginas_miolo': 8,
            # Select 9: Acabamento MIOLO
            'acabamento_miolo': 9,
            # Select 10: Acabamento LIVRO
            'acabamento_livro': 10,
            # Select 11: Guardas LIVRO
            'guardas_livro': 11,
            # Select 12: Extras
            'extras': 12,
            # Select 13: Frete
            'frete': 13,
            # Select 14: Verificação do Arquivo
            'verificacao_arquivo': 14,
            # Select 15: Prazo de Entrega
            'prazo_entrega': 15,
        }
        
        campos_processados = 0
        campos_nao_encontrados = []
        campos_encontrados = []
        
        print(f"DEBUG: Total de campos recebidos: {len(opcoes)}", file=sys.stderr)
        print(f"DEBUG: Campos recebidos: {list(opcoes.keys())}", file=sys.stderr)
        print(f"DEBUG: Total de selects na página: {len(selects)}", file=sys.stderr)
        
        # Ordenar campos para processar na ordem correta (formato primeiro, depois os outros)
        campos_ordenados = []
        outros_campos = []
        
        for campo, valor in opcoes.items():
            if campo == 'quantity':
                continue
            if campo in ['formato', 'formato_miolo_paginas']:
                campos_ordenados.insert(0, (campo, valor))  # Formato primeiro
            else:
                outros_campos.append((campo, valor))
        
        # Processar todos os campos na ordem correta
        todos_campos = campos_ordenados + outros_campos
        
        for campo, valor in todos_campos:
            idx = mapeamento_revista.get(campo)
            if idx is not None and idx < len(selects):
                print(f"DEBUG: Processando campo {campo} = {valor} (select index {idx})", file=sys.stderr)
                select = selects[idx]
                opcoes_encontradas = 0
                for opt in select.find_elements(By.TAG_NAME, 'option'):
                    v = opt.get_attribute('value')
                    t = opt.text.strip()
                    # Comparação mais flexível para encontrar a opção correta
                    valor_str = str(valor).strip()
                    v_str = str(v).strip() if v else ''
                    t_str = str(t).strip() if t else ''
                    
                    if (v_str == valor_str or t_str == valor_str or 
                        valor_str in v_str or valor_str in t_str or
                        v_str in valor_str or t_str in valor_str):
                        print(f"DEBUG: Opção encontrada para {campo}: value='{v_str}' / text='{t_str}' (buscando: '{valor_str}')", file=sys.stderr)
                        try:
                            Select(select).select_by_value(v)
                            opcoes_encontradas += 1
                            campos_processados += 1
                            campos_encontrados.append(f"{campo}={valor}")
                            time.sleep(0.4)  # Aguardar mais tempo para página atualizar
                            # Verificar se preço já foi calculado
                            for _ in range(20):
                                time.sleep(0.15)
                                try:
                                    preco_element = driver.find_element(By.ID, "calc-total")
                                    preco_texto = preco_element.text
                                    preco_valor = extrair_valor_preco(preco_texto)
                                    if preco_valor and preco_valor > 0:
                                        print(f"DEBUG: Preço encontrado durante processamento: {preco_valor}", file=sys.stderr)
                                        return preco_valor
                                except:
                                    pass
                        except Exception as e:
                            print(f"DEBUG: ERRO ao selecionar opção para {campo}: {e}", file=sys.stderr)
                        break
                if opcoes_encontradas == 0:
                    print(f"DEBUG: AVISO - Opção não encontrada para campo {campo} = {valor}", file=sys.stderr)
                    print(f"DEBUG: Opções disponíveis no select {idx}:", file=sys.stderr)
                    for opt in select.find_elements(By.TAG_NAME, 'option')[:5]:  # Mostrar primeiras 5
                        print(f"DEBUG:   - value='{opt.get_attribute('value')}' text='{opt.text.strip()}'", file=sys.stderr)
                    campos_nao_encontrados.append(f"{campo}={valor}")
            else:
                if idx is None:
                    print(f"DEBUG: AVISO - Campo {campo} não está no mapeamento", file=sys.stderr)
                    campos_nao_encontrados.append(f"{campo} (não mapeado)")
                else:
                    print(f"DEBUG: AVISO - Campo {campo} tem índice {idx} mas página tem apenas {len(selects)} selects", file=sys.stderr)
                    campos_nao_encontrados.append(f"{campo} (índice {idx} inválido)")
        
        print(f"DEBUG: Campos processados com sucesso ({campos_processados}): {campos_encontrados}", file=sys.stderr)
        if campos_nao_encontrados:
            print(f"DEBUG: AVISO - Campos não processados ({len(campos_nao_encontrados)}): {campos_nao_encontrados}", file=sys.stderr)
        
        print(f"DEBUG: Processados {campos_processados} campos. Aguardando cálculo final...", file=sys.stderr)
        time.sleep(0.6)
        for tentativa in range(30):
            time.sleep(0.1)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    print(f"DEBUG: Preço encontrado: {preco_valor} (texto: {preco_texto})", file=sys.stderr)
                    return preco_valor
            except Exception as e:
                if tentativa == 29:  # Última tentativa
                    print(f"DEBUG: Elemento calc-total não encontrado após 3 segundos. Erro: {e}", file=sys.stderr)
                pass
        
        print("DEBUG: Preço não encontrado após todas as tentativas", file=sys.stderr)
        return None
        
    except Exception as e:
        # Capturar erro para debug
        import traceback
        error_msg = str(e)
        traceback_str = traceback.format_exc()
        # Logar erro no stderr para aparecer no log do Laravel
        print(f"ERRO_NO_SCRAPER: {error_msg}", file=sys.stderr)
        print(f"TRACEBACK: {traceback_str}", file=sys.stderr)
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
            resultado = {
                'success': False, 
                'error': 'Preço não encontrado',
                'opcoes_recebidas': opcoes,
                'quantidade': quantidade
            }
        
        print(json.dumps(resultado))
    except Exception as e:
        import traceback
        traceback_str = traceback.format_exc()
        resultado = {
            'success': False, 
            'error': str(e),
            'traceback': traceback_str
        }
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()

