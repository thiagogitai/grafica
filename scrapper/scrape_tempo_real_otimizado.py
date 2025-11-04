"""
Versão OTIMIZADA para máximo desempenho - alvo: 5 segundos
"""
import sys
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
# DesiredCapabilities não é mais necessário na versão moderna do Selenium
import time
import re

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
    Versão OTIMIZADA - alvo: 5 segundos
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    # Configurações MÁXIMAS de performance
    options = Options()
    options.add_argument('--headless=new')  # Novo modo headless mais rápido
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-extensions')
    options.add_argument('--disable-plugins')
    options.add_argument('--disable-background-timer-throttling')
    options.add_argument('--disable-backgrounding-occluded-windows')
    options.add_argument('--disable-renderer-backgrounding')
    options.add_argument('--disable-infobars')
    options.add_argument('--disable-notifications')
    options.add_argument('--disable-web-security')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--disable-blink-features=AutomationControlled')
    
    # Desabilitar imagens, CSS e outros recursos desnecessários
    prefs = {
        "profile.managed_default_content_settings.images": 2,  # Não carregar imagens
        "profile.default_content_setting_values.notifications": 2,
        "profile.default_content_setting_values.media_stream": 2,
    }
    options.add_experimental_option("prefs", prefs)
    
    # Desabilitar logging
    options.add_experimental_option('excludeSwitches', ['enable-logging'])
    
    # Configurar pageLoadStrategy via options (forma moderna)
    options.set_capability('pageLoadStrategy', 'eager')  # Não esperar todos recursos carregarem
    
    driver = None
    
    try:
        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(6)  # Timeout ainda mais reduzido
        
        # Acessar página
        inicio = time.time()
        driver.get(url)
        
        # Aceitar cookies via JavaScript (mais rápido)
        try:
            driver.execute_script("""
                var buttons = Array.from(document.querySelectorAll('button'));
                var btn = buttons.find(b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar'));
                if (btn) btn.click();
            """)
        except:
            pass
        
        # Encontrar selects rapidamente (sem esperar)
        selects = driver.find_elements(By.TAG_NAME, 'select')
        if not selects:
            return None
        
        # Quantidade - Select é mais confiável
        qtd_select = selects[0]
        opcoes_qtd = qtd_select.find_elements(By.TAG_NAME, 'option')
        
        for opt in opcoes_qtd:
            valor_opt = opt.get_attribute('value')
            if valor_opt == str(quantidade) or str(quantidade) in opt.text:
                Select(qtd_select).select_by_value(valor_opt)
                break
        
        # Mapeamento rápido
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
        
        # Aplicar opções rapidamente (sem delays, usando JavaScript para ser mais rápido)
        for campo_nome, valor_opcao in opcoes.items():
            if campo_nome == 'quantity':
                continue
            
            indice_select = mapeamento_campos.get(campo_nome)
            if indice_select and indice_select < len(selects):
                select = selects[indice_select]
                opcoes_list = select.find_elements(By.TAG_NAME, 'option')
                
                # Buscar opção
                opcao_encontrada = None
                for opt in opcoes_list:
                    valor_opt = opt.get_attribute('value')
                    texto_opt = opt.text.strip()
                    valor_proc = str(valor_opcao).strip()
                    
                    if (valor_opt == valor_proc or 
                        texto_opt == valor_proc or
                        valor_proc in valor_opt or
                        valor_proc in texto_opt):
                        opcao_encontrada = valor_opt
                        break
                
                # Select é mais confiável (mesmo sendo um pouco mais lento)
                if opcao_encontrada:
                    Select(select).select_by_value(opcao_encontrada)
                    time.sleep(0.05)  # Delay mínimo
        
        # Aguardar cálculo inicial (mínimo necessário)
        time.sleep(0.3)
        
        # Polling otimizado - verificar rapidamente
        max_tentativas = 20  # Máximo 2 segundos
        tentativa = 0
        preco_valor = None
        
        # Primeira tentativa imediata
        try:
            preco_element = driver.find_element(By.ID, "calc-total")
            preco_texto = preco_element.text
            preco_valor = extrair_valor_preco(preco_texto)
            if preco_valor and preco_valor > 0:
                return preco_valor
        except:
            pass
        
        # Polling agressivo (100ms)
        while tentativa < max_tentativas:
            time.sleep(0.1)  # 100ms
            try:
                preco_element = driver.find_element(By.ID, "calc-total")
                preco_texto = preco_element.text
                preco_valor = extrair_valor_preco(preco_texto)
                if preco_valor and preco_valor > 0:
                    break
            except:
                pass
            tentativa += 1
        
        return preco_valor
        
    except Exception as e:
        # Em produção, retornar None silenciosamente
        # (Para debug, descomente as linhas abaixo)
        # import traceback
        # print(f"ERRO: {e}")
        # traceback.print_exc()
        return None
        
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

def main():
    """Função principal"""
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
        resultado = {
            'success': False,
            'error': str(e)
        }
        print(json.dumps(resultado))
        sys.exit(1)

if __name__ == "__main__":
    main()

