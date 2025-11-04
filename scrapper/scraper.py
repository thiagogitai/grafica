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
        limpo = ''.join(c for c in texto_preco if c.isdigit() or c == ',')
        return float(limpo.replace(',', '.'))
    except (ValueError, TypeError):
        print(f"Aviso: Não foi possível converter o valor '{texto_preco}' para número.")
        return 0.0

def coletar_precos():
    """
    Automatiza a coleta de preços do site da gráfica, iterando sobre as
    opções de quantidade, papel, formato e cores.
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-panfleto"
    
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

    matriz_de_precos = {}

    try:
        wait = WebDriverWait(driver, 30)

        try:
            cookie_button = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Aceitar')]")))
            driver.execute_script("arguments[0].click();", cookie_button)
            print("Banner de cookies aceito.")
            time.sleep(1)
        except TimeoutException:
            print("Nenhum banner de cookies encontrado ou já foi aceito.")
        
        # Mapeamento dos campos desejados
        campos_ids = {
            "quantidade": "Q1",
            "papel": "Options[0].Value",
            "formato": "Options[1].Value",
            "cores": "Options[2].Value"
        }

        for campo, id_seletor in campos_ids.items():
            wait.until(EC.presence_of_element_located((By.ID, id_seletor)))
            print(f"Campo '{campo}' encontrado.")

        ID_DO_PRECO = 'calc-total'
        print(f"Aguardando preço inicial carregar no elemento '{ID_DO_PRECO}'...")
        try:
            wait.until(EC.text_to_be_present_in_element((By.ID, ID_DO_PRECO), 'R$'))
            print("Preço inicial carregado. Iniciando coleta.")
        except TimeoutException:
            print(f"ERRO CRÍTICO: O preço inicial não carregou no elemento '{ID_DO_PRECO}' após 30 segundos.")
            driver.quit()
            return

        # Coleta as opções dos campos desejados
        opcoes = {campo: [opt.get_attribute('value') for opt in Select(driver.find_element(By.ID, id_seletor)).options] 
                  for campo, id_seletor in campos_ids.items()}
        
        # Define a lista customizada de quantidades
        opcoes['quantidade'] = ["100", "500", "1000", "2000", "5000", "10000"]

        total_combinacoes = 1
        for campo, valores in opcoes.items():
            total_combinacoes *= len(valores)
            print(f"- {len(valores)} opções para '{campo}'")

        tempo_estimado_segundos = total_combinacoes * 2.5
        horas = int(tempo_estimado_segundos // 3600)
        minutos = int((tempo_estimado_segundos % 3600) // 60)

        print(f"\nTotal de combinações a serem verificadas: {total_combinacoes}")
        print(f"Tempo estimado para a coleta: Aproximadamente {horas} horas e {minutos} minutos.")
        print("-" * 30)
        
        combinacao_atual = 0

        # Loops aninhados para as combinações selecionadas
        for qtd_valor in opcoes["quantidade"]:
            Select(driver.find_element(By.ID, campos_ids["quantidade"])).select_by_value(qtd_valor)
            matriz_de_precos[qtd_valor] = {}
            for formato_valor in opcoes["formato"]:
                Select(driver.find_element(By.ID, campos_ids["formato"])).select_by_value(formato_valor)
                matriz_de_precos[qtd_valor][formato_valor] = {}
                for papel_valor in opcoes["papel"]:
                    preco_antigo = driver.find_element(By.ID, ID_DO_PRECO).text
                    Select(driver.find_element(By.ID, campos_ids["papel"])).select_by_value(papel_valor)
                    # Espera principal pela mudança de preço
                    wait.until(lambda d: d.find_element(By.ID, ID_DO_PRECO).text != preco_antigo)
                    
                    matriz_de_precos[qtd_valor][formato_valor][papel_valor] = {}
                    for cor_valor in opcoes["cores"]:
                        combinacao_atual += 1
                        preco_antigo = driver.find_element(By.ID, ID_DO_PRECO).text
                        Select(driver.find_element(By.ID, campos_ids["cores"])).select_by_value(cor_valor)
                        
                        try:
                            # CORREÇÃO: Cria uma nova instância de WebDriverWait com o timeout desejado
                            WebDriverWait(driver, 5).until(lambda d: d.find_element(By.ID, ID_DO_PRECO).text != preco_antigo)
                        except TimeoutException:
                            # Se não mudar (ex: mesmo preço), continua normalmente
                            pass
                        
                        preco_atual_texto = driver.find_element(By.ID, ID_DO_PRECO).text
                        preco_final = limpar_preco(preco_atual_texto)
                        
                        print(f"({combinacao_atual}/{total_combinacoes}) Qtd:{qtd_valor}, Formato:{formato_valor[:6]}, Papel:{papel_valor[:10]}, Cores:{cor_valor[:10]} -> R$ {preco_final:.2f}")
                        
                        matriz_de_precos[qtd_valor][formato_valor][papel_valor][cor_valor] = preco_final

    finally:
        driver.quit()
        print("-" * 30)
        print("Coleta finalizada.")

    nome_arquivo = 'precos_grafica.json'
    with open(nome_arquivo, 'w', encoding='utf-8') as f:
        json.dump(matriz_de_precos, f, ensure_ascii=False, indent=4)
        
    print(f"Dados salvos com sucesso no arquivo: '{nome_arquivo}'")

if __name__ == "__main__":
    coletar_precos()

