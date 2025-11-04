"""
Script para fazer scraping em tempo real do preço do livro
Recebe opções via JSON e retorna o preço
"""
import sys
import json
import time
import re
import random
import os

# Workaround CRÍTICO para Python 3.13 no Windows
# O problema é que o Selenium importa FirefoxProfile que depende de asyncio
# que falha no Python 3.13 Windows devido a um bug no _overlapped
if sys.platform == 'win32' and sys.version_info >= (3, 13):
    # Tentar importar e configurar asyncio ANTES de qualquer importação do selenium
    try:
        # Configurar variáveis de ambiente
        os.environ['PYTHONUNBUFFERED'] = '1'
        os.environ['PYTHONASYNCIODEBUG'] = '0'
        
        # Tentar importar asyncio e configurar política
        import asyncio
        if hasattr(asyncio, 'WindowsSelectorEventLoopPolicy'):
            asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
        # Criar loop se não existir
        try:
            loop = asyncio.get_event_loop()
        except RuntimeError:
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)
    except Exception:
        # Se falhar, tentar continuar - pode funcionar mesmo assim
        pass

# Importar selenium - fazer importação específica para evitar FirefoxProfile
try:
    # Importar apenas o que precisamos do Chrome, evitando Firefox
    from selenium.webdriver.chrome.options import Options
    from selenium.webdriver.chrome.service import Service
    from selenium.webdriver.chrome.webdriver import WebDriver
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support.ui import WebDriverWait, Select
    from selenium.webdriver.support import expected_conditions as EC
    
    # Criar alias para compatibilidade
    class webdriver:
        @staticmethod
        def Chrome(options=None, service=None):
            if service is None:
                service = Service()
            return WebDriver(service=service, options=options)
except Exception as e:
    # Se falhar, tentar importação normal (pode falhar no Python 3.13)
    try:
        from selenium import webdriver
        from selenium.webdriver.common.by import By
        from selenium.webdriver.support.ui import WebDriverWait, Select
        from selenium.webdriver.support import expected_conditions as EC
        from selenium.webdriver.chrome.options import Options
    except Exception as e2:
        # Se tudo falhar, retornar erro JSON
        error_msg = f'Erro ao importar Selenium no Python 3.13: {str(e2)}. Considere usar Python 3.11 ou 3.12.'
        print(json.dumps({'success': False, 'error': error_msg}))
        sys.exit(1)

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
    Faz scraping do preço em tempo real
    
    Args:
        opcoes: Dict com as opções selecionadas
        quantidade: Quantidade desejada
    
    Returns:
        Preço encontrado ou None
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    # Configurar Chrome em modo headless otimizado para velocidade
    options = Options()
    options.add_argument('--headless')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-images')  # Não carregar imagens
    # Não desabilitar JavaScript - o site precisa de JS para calcular preço
    options.add_argument('--disable-extensions')
    options.add_argument('--disable-plugins')
    options.add_argument('--disable-background-timer-throttling')
    options.add_argument('--disable-backgrounding-occluded-windows')
    options.add_argument('--disable-renderer-backgrounding')
    options.add_argument('--window-size=1920,1080')
    
    # Desabilitar imagens e CSS para ser mais rápido
    prefs = {
        "profile.managed_default_content_settings.images": 2,  # Não carregar imagens
        "profile.default_content_setting_values.notifications": 2
    }
    options.add_experimental_option("prefs", prefs)
    
    driver = None
    
    try:
        # Criar driver Chrome usando o método compatível
        try:
            service = Service()
            driver = WebDriver(service=service, options=options)
        except:
            # Fallback para método normal
            driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(6)  # Reduzir timeout
        
        # Acessar página (pessoa esperando carregar)
        driver.get(url)
        time.sleep(random.uniform(1.0, 2.0))  # Pessoa esperando página carregar
        
        # Aceitar cookies se aparecer (timeout menor)
        try:
            cookie_btn = WebDriverWait(driver, 1).until(
                EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Aceitar')]"))
            )
            cookie_btn.click()
            time.sleep(0.2)
        except:
            pass
        
        # Encontrar todos os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        if not selects:
            return None
        
        # Primeiro select é quantidade
        qtd_select = selects[0]
        
        # Definir quantidade
        opcoes_qtd = qtd_select.find_elements(By.TAG_NAME, 'option')
        quantidade_definida = False
        
        for opt in opcoes_qtd:
            valor_opt = opt.get_attribute('value')
            texto_opt = opt.text.strip()
            
            if valor_opt == str(quantidade) or texto_opt == str(quantidade) or str(quantidade) in texto_opt:
                Select(qtd_select).select_by_value(valor_opt)
                quantidade_definida = True
                break
        
        if not quantidade_definida and opcoes_qtd:
            # Tentar por índice (assumir que quantidade está próxima do início)
            Select(qtd_select).select_by_index(0)
        
        # Simular pessoa pensando na quantidade (agora verifica preço também)
        time.sleep(0.3)
        for _ in range(12):  # 1.2 segundos
            time.sleep(0.1)
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    return preco_valor
            except:
                pass
        
        # Mapear opções do request para os selects
        # Ordem esperada dos selects (após quantidade):
        # Options[1] = papel_capa ou formato_miolo
        # Options[2] = cores_capa ou papel_capa
        # etc.
        
        # Para cada opção recebida, encontrar o select correspondente
        # Isso pode precisar de ajuste baseado na estrutura real
        
        mapeamento_campos = {
            'formato_miolo_paginas': 1,
            'papel_capa': 1,
            'cores_capa': 2,
            'orelha_capa': 3,
            'acabamento_capa': 4,
            'papel_miolo': 5,
            'cores_miolo': 6,
            'miolo_sangrado': 7,
            'quantidade_paginas_miolo': 8,
            'acabamento_miolo': 9,
            'acabamento_livro': 10,
            'guardas_livro': 11,
            'extras': 12,
        }
        
        # Aplicar opções selecionadas
        for campo_nome, valor_opcao in opcoes.items():
            if campo_nome == 'quantity':
                continue
            
            indice_select = mapeamento_campos.get(campo_nome)
            if indice_select and indice_select < len(selects):
                select = selects[indice_select]
                
                try:
                    # Procurar opção pelo valor ou texto
                    opcoes_list = select.find_elements(By.TAG_NAME, 'option')
                    for opt in opcoes_list:
                        valor_opt = opt.get_attribute('value')
                        texto_opt = opt.text.strip()
                        valor_proc = str(valor_opcao).strip()
                        
                        if (valor_opt == valor_proc or 
                            texto_opt == valor_proc or
                            valor_proc in valor_opt or
                            valor_proc in texto_opt):
                            Select(select).select_by_value(valor_opt)
                            
                            # Aguardar um pouco para o cálculo ser acionado
                            time.sleep(0.3)
                            
                            # APÓS CADA MUDANÇA: Verificar se preço aparece em até 3 segundos
                            for tentativa in range(27):  # 2.7 segundos (já esperou 0.3s)
                                time.sleep(0.1)  # Verificar a cada 100ms
                                try:
                                    preco_element = driver.find_element(By.ID, "calc-total")
                                    preco_texto = preco_element.text
                                    preco_valor = extrair_valor_preco(preco_texto)
                                    if preco_valor and preco_valor > 0:
                                        # Preço encontrado! Retornar imediatamente
                                        return preco_valor
                                except:
                                    pass
                            
                            # Se não encontrou após 3 segundos, continua para próxima opção
                            break
                except Exception as e:
                    # Se não encontrar, continua
                    pass
        
        # Se chegou aqui, todas opções foram aplicadas mas preço não apareceu
        # Tentar mais uma vez por 3 segundos
        for tentativa in range(30):  # 3 segundos finais
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
        traceback.print_exc(file=sys.stderr)
        return None
        
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def main():
    """Função principal - recebe JSON via argumento"""
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
            resultado = {
                'success': True,
                'price': preco
            }
        else:
            resultado = {
                'success': False,
                'error': 'Preço não encontrado'
            }
        
        print(json.dumps(resultado))
        
    except Exception as e:
        import traceback
        resultado = {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()

