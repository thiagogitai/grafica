"""
Script para capturar e entender a lógica de cálculo de preços do site Eskenazi
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
from selenium.webdriver.chrome.service import Service
import time
import json

def capturar_logica_calculo():
    """
    Acessa o site e tenta entender como os preços são calculados:
    1. Monitora requisições de rede
    2. Analisa mudanças de preço ao alterar opções
    3. Tenta identificar padrões e fórmulas
    """
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    # Configurar Chrome para capturar logs de rede
    caps = DesiredCapabilities.CHROME
    caps['goog:loggingPrefs'] = {'performance': 'ALL'}
    
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")
    options.add_argument('--enable-logging')
    options.add_argument('--v=1')
    
    driver = webdriver.Chrome(options=options, desired_capabilities=caps)
    
    try:
        print("Acessando o site...")
        driver.get(url)
        time.sleep(5)
        
        # Aceitar cookies
        try:
            cookie_btn = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Aceitar')]"))
            )
            cookie_btn.click()
            time.sleep(1)
        except:
            pass
        
        # Encontrar campo de quantidade
        print("\nProcurando campo de quantidade...")
        qtd_field = None
        try:
            qtd_field = driver.find_element(By.XPATH, "//input[@type='number']")
            print(f"OK - Campo encontrado: {qtd_field.get_attribute('id') or qtd_field.get_attribute('name')}")
        except:
            print("Campo de quantidade nao encontrado como input, tentando select...")
            try:
                qtd_field = driver.find_element(By.XPATH, "//select[contains(@id, 'quantity') or contains(@name, 'quantity')]")
                print(f"OK - Select encontrado: {qtd_field.get_attribute('id')}")
            except:
                print("ERRO - Campo de quantidade nao encontrado")
                return
        
        # Encontrar elemento de preço
        print("\nProcurando elemento de preco...")
        preco_element = None
        preco_texts = []
        
        # Tentar vários seletores
        seletores_preco = [
            "//*[contains(@id, 'calc-total')]",
            "//*[contains(@id, 'total-price')]",
            "//*[contains(@id, 'preco')]",
            "//*[contains(text(), 'R$')]",
            "//span[contains(text(), 'R$')]",
            "//div[contains(text(), 'R$')]",
        ]
        
        for seletor in seletores_preco:
            try:
                preco_element = driver.find_element(By.XPATH, seletor)
                if preco_element and preco_element.text:
                    print(f"OK - Preco encontrado: {preco_element.get_attribute('id')}, Texto: {preco_element.text[:50]}")
                    break
            except:
                continue
        
        if not preco_element:
            print("AVISO - Elemento de preco nao encontrado automaticamente")
            print("Tente encontrar manualmente e pressione ENTER...")
            input()
            return
        
        # Encontrar todos os selects
        print("\nEncontrando campos select...")
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"Encontrados {len(selects)} campos select")
        
        # Fazer testes de mudança e capturar preços
        print("\n" + "="*70)
        print("TESTE 1: Alterar quantidade e observar mudanca de preco")
        print("="*70)
        
        preco_inicial = preco_element.text
        print(f"Preco inicial: {preco_inicial}")
        
        # Testar diferentes quantidades
        quantidades_teste = ["50", "100", "500"]
        resultados = []
        
        for qtd in quantidades_teste:
            try:
                if qtd_field.tag_name == 'input':
                    qtd_field.clear()
                    qtd_field.send_keys(qtd)
                else:
                    Select(qtd_field).select_by_value(qtd)
                
                time.sleep(2)
                
                novo_preco = preco_element.text
                resultados.append({
                    'quantidade': qtd,
                    'preco': novo_preco
                })
                print(f"  Qtd {qtd}: {novo_preco}")
            except Exception as e:
                print(f"  Erro ao testar qtd {qtd}: {e}")
        
        # Testar mudança de um select
        print("\n" + "="*70)
        print("TESTE 2: Alterar um campo select e observar")
        print("="*70)
        
        if selects:
            primeiro_select = selects[0]
            select_id = primeiro_select.get_attribute('id') or primeiro_select.get_attribute('name')
            print(f"Testando select: {select_id}")
            
            opcoes = primeiro_select.find_elements(By.TAG_NAME, 'option')
            if len(opcoes) > 1:
                # Primeira opção
                Select(primeiro_select).select_by_index(0)
                time.sleep(2)
                preco_opcao1 = preco_element.text
                print(f"  Opcao 1: {preco_opcao1}")
                
                # Segunda opção
                Select(primeiro_select).select_by_index(1)
                time.sleep(2)
                preco_opcao2 = preco_element.text
                print(f"  Opcao 2: {preco_opcao2}")
                
                if preco_opcao1 != preco_opcao2:
                    print(f"  DIFERENCA DETECTADA! O preco muda com a opcao")
                else:
                    print(f"  Preco nao mudou (pode ser que mude com combinacao de outras opcoes)")
        
        # Capturar logs de rede
        print("\n" + "="*70)
        print("ANALISANDO REQUISICOES DE REDE")
        print("="*70)
        
        logs = driver.get_log('performance')
        requisicoes = []
        
        for log in logs:
            message = json.loads(log['message'])
            if message['message']['method'] == 'Network.responseReceived':
                url_resp = message['message']['params']['response']['url']
                if 'preco' in url_resp.lower() or 'price' in url_resp.lower() or 'calc' in url_resp.lower() or 'api' in url_resp.lower():
                    requisicoes.append(url_resp)
        
        if requisicoes:
            print(f"\nEncontradas {len(requisicoes)} requisicoes relacionadas a preco/API:")
            for req in requisicoes[:10]:
                print(f"  {req}")
        else:
            print("\nNenhuma requisicao de API encontrada.")
            print("Isso pode indicar que o calculo e feito apenas no JavaScript do lado do cliente.")
        
        # Salvar resultados
        resultado = {
            'preco_inicial': preco_inicial,
            'testes_quantidade': resultados,
            'requisicoes_encontradas': requisicoes[:20]
        }
        
        with open('logica_calculo_analise.json', 'w', encoding='utf-8') as f:
            json.dump(resultado, f, ensure_ascii=False, indent=2)
        
        print("\n" + "="*70)
        print("ANALISE CONCLUIDA")
        print("="*70)
        print("\nResultados salvos em: logica_calculo_analise.json")
        print("\nPROXIMOS PASSOS:")
        print("1. Abra o DevTools (F12) no navegador")
        print("2. Vá em Network > XHR/Fetch")
        print("3. Altere opcoes no formulario")
        print("4. Veja se aparecem requisicoes de API")
        print("5. Verifique o Console para funcoes JavaScript")
        print("6. Procure por: window.calculatePrice, calcPrice, etc.")
        
        input("\nPressione ENTER para fechar...")
        
    except Exception as e:
        print(f"ERRO: {e}")
        import traceback
        traceback.print_exc()
    finally:
        driver.quit()

if __name__ == "__main__":
    capturar_logica_calculo()

