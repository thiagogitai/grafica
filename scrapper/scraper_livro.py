import time
import json
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import StaleElementReferenceException, NoSuchElementException, TimeoutException

def limpar_preco(texto_preco):
    """Remove o 'R$', espaços e converte a vírgula para ponto."""
    if not texto_preco or texto_preco.strip() == '':
        return 0.0
    try:
        # Remove tudo exceto dígitos e vírgula
        limpo = ''.join(c for c in texto_preco if c.isdigit() or c == ',')
        return float(limpo.replace(',', '.'))
    except (ValueError, TypeError):
        print(f"Aviso: Não foi possível converter o valor '{texto_preco}' para número.")
        return 0.0

def encontrar_elemento_preco(driver):
    """Tenta encontrar o elemento que contém o preço de várias formas."""
    possiveis_ids = ['calc-total', 'total-price', 'preco-total', 'price-total', 'total']
    possiveis_xpath = [
        "//*[contains(@id, 'total')]",
        "//*[contains(@id, 'preco')]",
        "//*[contains(@id, 'price')]",
        "//*[contains(@class, 'total')]",
        "//*[contains(@class, 'preco')]",
        "//*[contains(text(), 'Valor desse pedido')]/following-sibling::*",
    ]
    
    # Tenta por ID
    for id_tentativa in possiveis_ids:
        try:
            elemento = driver.find_element(By.ID, id_tentativa)
            if 'R$' in elemento.text or any(c.isdigit() for c in elemento.text):
                return elemento, id_tentativa
        except:
            continue
    
    # Tenta por XPath
    for xpath in possiveis_xpath:
        try:
            elementos = driver.find_elements(By.XPATH, xpath)
            for elemento in elementos:
                if 'R$' in elemento.text or any(c.isdigit() for c in elemento.text):
                    return elemento, elemento.get_attribute('id') or 'preco_encontrado'
        except:
            continue
    
    return None, None

def encontrar_campos_formulario(driver):
    """Encontra todos os campos select do formulário e seus IDs."""
    campos_encontrados = {}
    
    # Busca todos os selects
    selects = driver.find_elements(By.TAG_NAME, 'select')
    
    for select in selects:
        select_id = select.get_attribute('id')
        select_name = select.get_attribute('name')
        select_label = None
        
        # Tenta encontrar o label associado
        if select_id:
            try:
                label = driver.find_element(By.XPATH, f"//label[@for='{select_id}']")
                select_label = label.text.strip()
            except:
                pass
        
        if not select_label:
            # Tenta encontrar label próximo
            try:
                parent = select.find_element(By.XPATH, './ancestor::div[1]')
                label = parent.find_element(By.TAG_NAME, 'label')
                select_label = label.text.strip()
            except:
                pass
        
        chave = select_id or select_name or f"select_{len(campos_encontrados)}"
        campos_encontrados[chave] = {
            'id': select_id,
            'name': select_name,
            'label': select_label,
            'element': select
        }
    
    # Busca campo de quantidade (pode ser input)
    inputs = driver.find_elements(By.XPATH, "//input[@type='number' or @type='text']")
    for input_elem in inputs:
        input_id = input_elem.get_attribute('id')
        input_name = input_elem.get_attribute('name')
        input_placeholder = input_elem.get_attribute('placeholder') or ''
        
        if 'quantidade' in (input_id or '').lower() or 'quantidade' in (input_name or '').lower() or 'quantidade' in input_placeholder.lower():
            campos_encontrados['quantity'] = {
                'id': input_id,
                'name': input_name,
                'label': 'Quantidade',
                'element': input_elem,
                'type': 'input'
            }
            break
    
    return campos_encontrados

def coletar_precos_livro():
    """
    Automatiza a coleta de preços do site da gráfica para o produto "Impressão de Livro".
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    # Quantidades especificadas pelo usuário
    quantidades = ["50", "100", "150", "200", "250", "300", "350", "400", "450", "500", 
                   "600", "700", "800", "900", "1000", "1250", "1500", "1750", "2000", 
                   "2250", "2500", "2750", "3000", "3250", "3500", "3750", "4000", "4250", 
                   "4500", "4750", "5000"]
    
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option('useAutomationExtension', False)
    
    try:
        driver = webdriver.Chrome(options=options)
        driver.execute_cdp_cmd('Network.setUserAgentOverride', {
            "userAgent": 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36'
        })
    except Exception as e:
        print(f"Erro ao iniciar o Chrome Driver: {e}")
        print("Certifique-se de que o ChromeDriver está instalado e no PATH.")
        return

    driver.get(url)
    print(f"Acessando: {url}")
    time.sleep(3)  # Aguardar página carregar

    matriz_de_precos = {}

    try:
        wait = WebDriverWait(driver, 30)

        # Aceitar cookies se houver
        try:
            cookie_button = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Aceitar') or contains(text(), 'Aceito')]")))
            driver.execute_script("arguments[0].click();", cookie_button)
            print("Banner de cookies aceito.")
            time.sleep(1)
        except TimeoutException:
            print("Nenhum banner de cookies encontrado.")
        
        # Encontrar elemento de preço
        print("\nProcurando elemento de preço...")
        preco_elemento, preco_id = encontrar_elemento_preco(driver)
        if not preco_elemento:
            print("ERRO: Não foi possível encontrar o elemento de preço.")
            print("A página pode ter uma estrutura diferente. Verifique manualmente.")
            driver.quit()
            return
        
        print(f"Elemento de preço encontrado: ID='{preco_id}', Texto='{preco_elemento.text[:50]}...'")
        
        # Aguardar preço carregar
        try:
            wait.until(lambda d: 'R$' in preco_elemento.text or any(c.isdigit() for c in preco_elemento.text))
            print("Preço inicial carregado.")
        except:
            print("Aviso: Preço inicial pode não ter carregado completamente.")
        
        # Encontrar todos os campos do formulário
        print("\nProcurando campos do formulário...")
        campos_encontrados = encontrar_campos_formulario(driver)
        
        print(f"\nEncontrados {len(campos_encontrados)} campos:")
        for chave, info in campos_encontrados.items():
            print(f"  - {chave}: ID='{info.get('id')}', Name='{info.get('name')}', Label='{info.get('label')}'")
        
        # Coletar opções de cada campo
        opcoes = {}
        campos_ids = {}
        
        for chave, info in campos_encontrados.items():
            elemento = info['element']
            campo_id = info.get('id') or info.get('name') or chave
            
            try:
                if info.get('type') == 'input':
                    # Campo de input (quantidade)
                    opcoes['quantity'] = quantidades
                    campos_ids['quantity'] = campo_id
                else:
                    # Campo select
                    select = Select(elemento)
                    valores = [opt.get_attribute('value') for opt in select.options 
                             if opt.get_attribute('value') and opt.get_attribute('value').strip()]
                    if valores:
                        opcoes[chave] = valores
                        campos_ids[chave] = campo_id
                        print(f"  {chave}: {len(valores)} opções")
            except Exception as e:
                print(f"  Erro ao coletar opções de {chave}: {e}")
        
        # Remover quantity das opções se já está definido
        if 'quantity' in opcoes:
            del opcoes['quantity']
        
        # Calcular total de combinações
        total_combinacoes = len(quantidades)
        for campo, valores in opcoes.items():
            if len(valores) > 0:
                total_combinacoes *= len(valores)
        
        tempo_estimado_segundos = total_combinacoes * 3
        horas = int(tempo_estimado_segundos // 3600)
        minutos = int((tempo_estimado_segundos % 3600) // 60)
        
        print(f"\n{'='*70}")
        print(f"RESUMO DA COLETA:")
        print(f"  Quantidades: {len(quantidades)}")
        print(f"  Campos adicionais: {len(opcoes)}")
        print(f"  Total de combinações: {total_combinacoes:,}")
        print(f"  Tempo estimado: ~{horas}h {minutos}min")
        print(f"{'='*70}\n")
        
        resposta = input("Deseja continuar com a coleta? (s/n): ")
        if resposta.lower() != 's':
            print("Coleta cancelada pelo usuário.")
            driver.quit()
            return
        
        combinacao_atual = 0
        
        # Iterar sobre todas as combinações
        for qtd in quantidades:
            print(f"\n{'='*70}")
            print(f"Processando quantidade: {qtd}")
            print(f"{'='*70}")
            
            # Definir quantidade
            try:
                qtd_elemento = driver.find_element(By.ID, campos_ids['quantity'])
                if qtd_elemento.tag_name == 'input':
                    qtd_elemento.clear()
                    qtd_elemento.send_keys(qtd)
                else:
                    Select(qtd_elemento).select_by_value(qtd)
                time.sleep(0.5)
            except Exception as e:
                print(f"Erro ao definir quantidade {qtd}: {e}")
                continue
            
            # Função recursiva para iterar combinações
            def iterar_campos(campos_restantes, valores_atuais):
                nonlocal combinacao_atual
                
                if not campos_restantes:
                    combinacao_atual += 1
                    
                    # Selecionar todas as opções
                    for campo, valor in valores_atuais.items():
                        if campo == 'quantity':
                            continue
                        try:
                            campo_id = campos_ids.get(campo, campo)
                            elemento = driver.find_element(By.ID, campo_id)
                            Select(elemento).select_by_value(valor)
                            time.sleep(0.1)
                        except Exception as e:
                            # Tenta por name
                            try:
                                elemento = driver.find_element(By.NAME, campo)
                                Select(elemento).select_by_value(valor)
                                time.sleep(0.1)
                            except:
                                print(f"  Erro ao selecionar {campo}={valor}: {e}")
                    
                    # Aguardar preço atualizar
                    time.sleep(2)
                    
                    try:
                        preco_texto = preco_elemento.text
                        preco_final = limpar_preco(preco_texto)
                        
                        # Salvar na estrutura
                        chave_completa = json.dumps(valores_atuais, sort_keys=True)
                        matriz_de_precos[chave_completa] = preco_final
                        
                        if combinacao_atual % 100 == 0:
                            print(f"  Progresso: {combinacao_atual:,} combinações processadas...")
                        
                    except Exception as e:
                        print(f"  Erro ao capturar preço: {e}")
                    
                    return
                
                campo_atual, valores_campo = campos_restantes[0]
                for valor in valores_campo:
                    valores_atuais[campo_atual] = valor
                    iterar_campos(campos_restantes[1:], valores_atuais.copy())
            
            # Preparar lista de campos
            campos_lista = [(campo, opcoes[campo]) for campo in sorted(opcoes.keys()) 
                          if len(opcoes[campo]) > 0]
            
            valores_iniciais = {'quantity': qtd}
            iterar_campos(campos_lista, valores_iniciais)
            
            # Salvar progresso parcial
            nome_arquivo_parcial = f'precos_livro_parcial_{qtd}.json'
            with open(nome_arquivo_parcial, 'w', encoding='utf-8') as f:
                json.dump(matriz_de_precos, f, ensure_ascii=False, indent=2)
            print(f"\nProgresso salvo: {nome_arquivo_parcial} ({len(matriz_de_precos)} combinações)")

    except Exception as e:
        print(f"\nERRO CRÍTICO: {e}")
        import traceback
        traceback.print_exc()
    finally:
        driver.quit()
        print("\n" + "-" * 70)
        print("Coleta finalizada.")

    # Salvar resultado final
    nome_arquivo = 'precos_livro.json'
    with open(nome_arquivo, 'w', encoding='utf-8') as f:
        json.dump(matriz_de_precos, f, ensure_ascii=False, indent=2)
        
    print(f"\nDados salvos em: '{nome_arquivo}'")
    print(f"Total de combinações coletadas: {len(matriz_de_precos):,}")

if __name__ == "__main__":
    coletar_precos_livro()
