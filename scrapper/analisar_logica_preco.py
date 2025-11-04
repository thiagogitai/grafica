"""
Script para analisar a lógica de cálculo de preços do site
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException
import time
import json

def analisar_logica_preco():
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")
    
    try:
        driver = webdriver.Chrome(options=options)
        driver.get(url)
        print(f"Acessando: {url}")
        time.sleep(5)
        
        # Aceitar cookies
        try:
            cookie_button = driver.find_element(By.XPATH, "//button[contains(text(), 'Aceitar')]")
            cookie_button.click()
            time.sleep(1)
        except:
            pass
        
        # Procurar por scripts JavaScript
        print("\n" + "="*70)
        print("ANALISANDO JAVASCRIPT DA PAGINA")
        print("="*70)
        
        scripts = driver.find_elements(By.TAG_NAME, 'script')
        print(f"\nEncontrados {len(scripts)} scripts na pagina")
        
        # Procurar por funções de cálculo
        calculo_keywords = ['calculate', 'preco', 'price', 'total', 'calcular', 'calc']
        scripts_com_calculo = []
        
        for i, script in enumerate(scripts):
            script_text = script.get_attribute('innerHTML') or ''
            if any(keyword in script_text.lower() for keyword in calculo_keywords):
                scripts_com_calculo.append((i, script_text[:500]))  # Primeiros 500 chars
        
        print(f"\nScripts que podem conter logica de calculo: {len(scripts_com_calculo)}")
        for idx, (num, preview) in enumerate(scripts_com_calculo[:5], 1):
            print(f"\n{idx}. Script #{num}:")
            print(preview[:200] + "...")
        
        # Procurar por elementos de preço e seus atributos
        print("\n" + "="*70)
        print("ANALISANDO ELEMENTOS DE PRECO")
        print("="*70)
        
        preco_elements = driver.find_elements(By.XPATH, "//*[contains(@id, 'preco') or contains(@id, 'price') or contains(@id, 'total') or contains(@class, 'preco') or contains(@class, 'price')]")
        print(f"\nEncontrados {len(preco_elements)} elementos relacionados a preco")
        
        for elem in preco_elements[:10]:
            print(f"\n- ID: {elem.get_attribute('id')}")
            print(f"  Class: {elem.get_attribute('class')}")
            print(f"  Texto: {elem.text[:100]}")
            print(f"  Data attributes: {elem.get_attribute('data-*')}")
        
        # Procurar por campos select e seus atributos data-add
        print("\n" + "="*70)
        print("ANALISANDO CAMPOS SELECT E ATRIBUTOS DATA-ADD")
        print("="*70)
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"\nEncontrados {len(selects)} campos select")
        
        campos_com_data_add = []
        for select in selects:
            select_id = select.get_attribute('id') or select.get_attribute('name')
            options = select.find_elements(By.TAG_NAME, 'option')
            
            for opt in options[:3]:  # Verificar apenas as 3 primeiras opções
                data_add = opt.get_attribute('data-add')
                if data_add:
                    campos_com_data_add.append({
                        'campo': select_id,
                        'opcao': opt.get_attribute('value'),
                        'data-add': data_add
                    })
        
        if campos_com_data_add:
            print("\nCampos com atributo data-add encontrados:")
            for campo in campos_com_data_add[:5]:
                print(f"  Campo: {campo['campo']}")
                print(f"    Opcao: {campo['opcao']}")
                print(f"    data-add: {campo['data-add']}")
        else:
            print("\nNenhum campo com atributo 'data-add' encontrado")
        
        # Procurar por requisições AJAX/fetch
        print("\n" + "="*70)
        print("INSTRUCOES PARA ANALISE MANUAL")
        print("="*70)
        print("""
1. Abra o DevTools (F12) no navegador
2. Vá na aba 'Network' (Rede)
3. Selecione diferentes opções no formulário
4. Procure por requisições XHR/Fetch que retornam preços
5. Verifique se há uma API endpoint que calcula preços
6. Verifique o JavaScript no console para ver funções de cálculo

Para capturar o JavaScript:
- Abra o Console (F12 > Console)
- Digite: Object.keys(window).filter(k => k.toLowerCase().includes('calc') || k.toLowerCase().includes('price'))
- Verifique se há funções globais de cálculo
        """)
        
        # Tentar fazer uma mudança e ver o que acontece
        print("\n" + "="*70)
        print("TESTE: ALTERANDO OPCOES E OBSERVANDO")
        print("="*70)
        
        # Encontrar campo de quantidade
        try:
            qtd_input = driver.find_element(By.XPATH, "//input[@type='number']")
            qtd_original = qtd_input.get_attribute('value')
            print(f"\nQuantidade original: {qtd_original}")
            
            # Alterar quantidade
            qtd_input.clear()
            qtd_input.send_keys("100")
            time.sleep(2)
            
            # Verificar se o preço mudou
            preco_elemento = driver.find_element(By.XPATH, "//*[contains(text(), 'R$')]")
            print(f"Preco apos mudar quantidade: {preco_elemento.text}")
            
        except Exception as e:
            print(f"Erro ao testar mudanca: {e}")
        
        input("\nPressione ENTER para fechar o navegador...")
        
    except Exception as e:
        print(f"Erro: {e}")
        import traceback
        traceback.print_exc()
    finally:
        try:
            driver.quit()
        except:
            pass

if __name__ == "__main__":
    analisar_logica_preco()

