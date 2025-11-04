import time
import json
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import StaleElementReferenceException, NoSuchElementException, TimeoutException
from selenium.webdriver.common.keys import Keys

def limpar_preco(texto_preco):
    """Remove o 'R$', espaços e converte a vírgula para ponto."""
    if not texto_preco or texto_preco.strip() == '':
        return 0.0
    try:
        limpo = ''.join(c for c in texto_preco if c.isdigit() or c == ',')
        return float(limpo.replace(',', '.'))
    except (ValueError, TypeError):
        return 0.0

def encontrar_elemento_preco(driver):
    """Encontra o elemento de preço."""
    possiveis_ids = ['calc-total', 'total-price', 'preco-total', 'price-total', 'total']
    possiveis_xpath = [
        "//*[contains(@id, 'total') and (contains(text(), 'R$') or string-length(text()) > 0)]",
        "//*[contains(@id, 'preco')]",
        "//*[contains(@id, 'price')]",
        "//*[contains(@class, 'total')]",
        "//span[contains(text(), 'R$')]",
        "//div[contains(text(), 'R$')]",
    ]
    
    for id_tentativa in possiveis_ids:
        try:
            elemento = driver.find_element(By.ID, id_tentativa)
            texto = elemento.text
            if 'R$' in texto or any(c.isdigit() for c in texto):
                return elemento, id_tentativa
        except:
            continue
    
    for xpath in possiveis_xpath:
        try:
            elementos = driver.find_elements(By.XPATH, xpath)
            for elemento in elementos:
                texto = elemento.text
                if 'R$' in texto or any(c.isdigit() for c in texto):
                    return elemento, elemento.get_attribute('id') or 'preco_encontrado'
        except:
            continue
    
    return None, None

def encontrar_campo_quantidade(driver):
    """Encontra o campo de quantidade (input number)."""
    # Tenta por ID
    possiveis_ids = ['quantity', 'Q1', 'quantidade', 'qty']
    for id_tentativa in possiveis_ids:
        try:
            elemento = driver.find_element(By.ID, id_tentativa)
            if elemento.tag_name == 'input':
                return elemento, id_tentativa
        except:
            continue
    
    # Tenta por name
    possiveis_names = ['quantity', 'Q1', 'quantidade', 'qty']
    for name_tentativa in possiveis_names:
        try:
            elemento = driver.find_element(By.NAME, name_tentativa)
            if elemento.tag_name == 'input':
                return elemento, name_tentativa
        except:
            continue
    
    # Tenta por tipo
    try:
        elementos = driver.find_elements(By.XPATH, "//input[@type='number']")
        if elementos:
            return elementos[0], elementos[0].get_attribute('id') or 'quantity'
    except:
        pass
    
    return None, None

def encontrar_campos_select(driver):
    """Encontra todos os campos select do formulário."""
    campos_encontrados = {}
    
    selects = driver.find_elements(By.TAG_NAME, 'select')
    
    for select in selects:
        select_id = select.get_attribute('id')
        select_name = select.get_attribute('name')
        
        if not select_id and not select_name:
            continue
        
        chave = select_id or select_name
        
        # Tenta encontrar label
        label_text = None
        if select_id:
            try:
                label = driver.find_element(By.XPATH, f"//label[@for='{select_id}']")
                label_text = label.text.strip()
            except:
                pass
        
        campos_encontrados[chave] = {
            'id': select_id,
            'name': select_name,
            'label': label_text,
            'element': select
        }
    
    return campos_encontrados

def coletar_precos_livro_otimizado(config=None):
    """
    Versão otimizada do scraper para Impressão de Livro.
    
    Args:
        config: Dicionário com configurações:
            - quantidades: lista de quantidades
            - campos_limitar: dicionário {campo: num_opcoes} para limitar campos
            - delay_selecao: tempo de espera entre seleções (padrão 0.3s)
            - delay_preco: tempo de espera para preço atualizar (padrão 1s)
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    # Configurações padrão
    if config is None:
        config = {}
    
    quantidades = config.get('quantidades', [
        "50", "100", "150", "200", "250", "300", "350", "400", "450", "500",
        "600", "700", "800", "900", "1000", "1250", "1500", "1750", "2000",
        "2250", "2500", "2750", "3000", "3250", "3500", "3750", "4000", "4250",
        "4500", "4750", "5000"
    ])
    
    campos_limitar = config.get('campos_limitar', {})
    delay_selecao = config.get('delay_selecao', 0.3)
    delay_preco = config.get('delay_preco', 1.0)
    
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
        return

    driver.get(url)
    print(f"Acessando: {url}")
    time.sleep(3)

    matriz_de_precos = {}

    try:
        wait = WebDriverWait(driver, 30)

        # Aceitar cookies
        try:
            cookie_button = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Aceitar') or contains(text(), 'Aceito')]")))
            driver.execute_script("arguments[0].click();", cookie_button)
            time.sleep(0.5)
        except TimeoutException:
            pass
        
        # Encontrar campo de quantidade
        print("\nProcurando campo de quantidade...")
        qtd_elemento, qtd_id = encontrar_campo_quantidade(driver)
        if not qtd_elemento:
            print("ERRO: Campo de quantidade não encontrado!")
            driver.quit()
            return
        
        print(f"✓ Campo de quantidade encontrado: {qtd_id}")
        
        # Encontrar elemento de preço
        print("Procurando elemento de preço...")
        preco_elemento, preco_id = encontrar_elemento_preco(driver)
        if not preco_elemento:
            print("ERRO: Elemento de preço não encontrado!")
            driver.quit()
            return
        
        print(f"✓ Elemento de preço encontrado: {preco_id}")
        
        # Encontrar campos select
        print("Procurando campos select...")
        campos_select = encontrar_campos_select(driver)
        print(f"✓ Encontrados {len(campos_select)} campos select")
        
        # Coletar opções de cada campo
        opcoes = {}
        campos_ids = {}
        
        for chave, info in campos_select.items():
            elemento = info['element']
            campo_id = info.get('id') or info.get('name')
            
            try:
                select = Select(elemento)
                valores = [opt.get_attribute('value') for opt in select.options 
                         if opt.get_attribute('value') and opt.get_attribute('value').strip()]
                
                # Aplicar limite se especificado
                if chave in campos_limitar:
                    limite = campos_limitar[chave]
                    valores = valores[:limite]
                    print(f"  {chave}: {len(valores)} opções (limitado de {len(select.options)})")
                else:
                    print(f"  {chave}: {len(valores)} opções")
                
                if valores:
                    opcoes[chave] = valores
                    campos_ids[chave] = campo_id
            except Exception as e:
                print(f"  Erro ao coletar opções de {chave}: {e}")
        
        # Calcular total de combinações
        total_combinacoes = len(quantidades)
        for campo, valores in opcoes.items():
            total_combinacoes *= len(valores)
        
        tempo_estimado = total_combinacoes * (delay_selecao * len(opcoes) + delay_preco)
        horas = int(tempo_estimado // 3600)
        minutos = int((tempo_estimado % 3600) // 60)
        
        print(f"\n{'='*70}")
        print(f"RESUMO:")
        print(f"  Quantidades: {len(quantidades)}")
        print(f"  Campos: {len(opcoes)}")
        print(f"  Total de combinações: {total_combinacoes:,}")
        print(f"  Tempo estimado: ~{horas}h {minutos}min")
        print(f"{'='*70}\n")
        
        resposta = input("Deseja continuar? (s/n): ")
        if resposta.lower() != 's':
            print("Cancelado.")
            driver.quit()
            return
        
        combinacao_atual = 0
        inicio_geral = time.time()
        
        # Iterar sobre quantidades
        for idx_qtd, qtd in enumerate(quantidades, 1):
            print(f"\n{'='*70}")
            print(f"[{idx_qtd}/{len(quantidades)}] Quantidade: {qtd}")
            print(f"{'='*70}")
            
            inicio_qtd = time.time()
            
            # Digitar quantidade
            try:
                qtd_elemento.clear()
                qtd_elemento.send_keys(qtd)
                qtd_elemento.send_keys(Keys.TAB)  # Trigger blur/change event
                time.sleep(0.5)
            except Exception as e:
                print(f"  Erro ao definir quantidade {qtd}: {e}")
                continue
            
            # Função recursiva otimizada
            def iterar_campos(campos_restantes, valores_atuais):
                nonlocal combinacao_atual
                
                if not campos_restantes:
                    combinacao_atual += 1
                    
                    # Selecionar todas as opções rapidamente
                    for campo, valor in valores_atuais.items():
                        try:
                            campo_id = campos_ids.get(campo, campo)
                            elemento = driver.find_element(By.ID, campo_id)
                            Select(elemento).select_by_value(valor)
                            time.sleep(delay_selecao)
                        except:
                            try:
                                elemento = driver.find_element(By.NAME, campo)
                                Select(elemento).select_by_value(valor)
                                time.sleep(delay_selecao)
                            except Exception as e:
                                if combinacao_atual == 1:
                                    print(f"  Aviso: Erro ao selecionar {campo}={valor}: {e}")
                    
                    # Aguardar preço atualizar
                    time.sleep(delay_preco)
                    
                    try:
                        preco_texto = preco_elemento.text
                        preco_final = limpar_preco(preco_texto)
                        
                        # Salvar na estrutura
                        valores_atuais['quantity'] = qtd
                        chave_completa = json.dumps(valores_atuais, sort_keys=True)
                        matriz_de_precos[chave_completa] = preco_final
                        
                        # Log progresso
                        if combinacao_atual % 50 == 0:
                            tempo_decorrido = time.time() - inicio_geral
                            tempo_por_comb = tempo_decorrido / combinacao_atual
                            tempo_restante = (total_combinacoes - combinacao_atual) * tempo_por_comb
                            horas_rest = int(tempo_restante // 3600)
                            min_rest = int((tempo_restante % 3600) // 60)
                            print(f"  Progresso: {combinacao_atual:,}/{total_combinacoes:,} | "
                                  f"Tempo restante: ~{horas_rest}h {min_rest}min")
                        
                    except Exception as e:
                        if combinacao_atual == 1:
                            print(f"  Erro ao capturar preço: {e}")
                    
                    return
                
                campo_atual, valores_campo = campos_restantes[0]
                for valor in valores_campo:
                    valores_atuais[campo_atual] = valor
                    iterar_campos(campos_restantes[1:], valores_atuais.copy())
            
            # Preparar lista de campos
            campos_lista = [(campo, opcoes[campo]) for campo in sorted(opcoes.keys()) 
                          if len(opcoes[campo]) > 0]
            
            valores_iniciais = {}
            iterar_campos(campos_lista, valores_iniciais)
            
            # Salvar progresso parcial
            tempo_qtd = time.time() - inicio_qtd
            nome_arquivo_parcial = f'precos_livro_parcial_{qtd}.json'
            with open(nome_arquivo_parcial, 'w', encoding='utf-8') as f:
                json.dump(matriz_de_precos, f, ensure_ascii=False, indent=2)
            print(f"\n✓ Progresso salvo: {nome_arquivo_parcial}")
            print(f"  Tempo para esta quantidade: {tempo_qtd/60:.1f}min")
            print(f"  Total coletado até agora: {len(matriz_de_precos):,} combinações")

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
        
    print(f"\n✓ Dados salvos em: '{nome_arquivo}'")
    print(f"✓ Total de combinações coletadas: {len(matriz_de_precos):,}")

if __name__ == "__main__":
    # CONFIGURAÇÃO - AJUSTE AQUI PARA REDUZIR TEMPO
    config = {
        'quantidades': [
            "50", "100", "150", "200", "250", "300", "350", "400", "450", "500",
            "600", "700", "800", "900", "1000", "1250", "1500", "1750", "2000",
            "2250", "2500", "2750", "3000", "3250", "3500", "3750", "4000", "4250",
            "4500", "4750", "5000"
        ],
        # LIMITE OS CAMPOS AQUI PARA REDUZIR TEMPO
        # Exemplo: limitar quantidade_paginas_miolo a apenas 10 opções
        'campos_limitar': {
            # 'quantidade_paginas_miolo': 10,  # Descomente e ajuste
            # 'formato_miolo_paginas': 5,
            # 'papel_miolo': 5,
            # 'papel_capa': 5,
        },
        'delay_selecao': 0.2,  # Reduzir para acelerar (mínimo 0.1)
        'delay_preco': 0.8,    # Reduzir para acelerar (mínimo 0.5)
    }
    
    coletar_precos_livro_otimizado(config)

