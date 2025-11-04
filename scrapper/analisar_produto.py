"""
Script para analisar a estrutura de um produto e mapear os campos
Útil para descobrir os labels e selects de cada produto
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options

def analisar_produto(url_produto):
    """
    Analisa a estrutura de um produto e retorna mapeamento de campos
    """
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    
    driver = None
    
    try:
        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(10)
        
        print(f"\nAnalisando: {url_produto}\n")
        driver.get(url_produto)
        time.sleep(2)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btn = Array.from(document.querySelectorAll('button')).find(
                    b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar')
                );
                if (btn) btn.click();
            """)
            time.sleep(0.5)
        except:
            pass
        
        # Encontrar todos os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        print(f"Encontrados {len(selects)} selects:\n")
        
        mapeamento = {}
        for idx, select in enumerate(selects):
            # Tentar encontrar label associado
            label = None
            try:
                # Procurar label antes do select
                parent = select.find_element(By.XPATH, './..')
                labels = parent.find_elements(By.TAG_NAME, 'label')
                if labels:
                    label = labels[0].text.strip()
                else:
                    # Procurar por texto antes
                    label = select.find_element(By.XPATH, './preceding-sibling::*[1]').text.strip()
            except:
                pass
            
            # Se não encontrou label, tentar pelo atributo name ou id
            if not label:
                label = select.get_attribute('name') or select.get_attribute('id') or f'select_{idx}'
            
            # Listar opções
            opcoes = []
            for opt in select.find_elements(By.TAG_NAME, 'option'):
                valor = opt.get_attribute('value')
                texto = opt.text.strip()
                if valor and texto:
                    opcoes.append({'value': valor, 'text': texto})
            
            mapeamento[idx] = {
                'label': label,
                'name': select.get_attribute('name'),
                'id': select.get_attribute('id'),
                'opcoes': opcoes[:10]  # Primeiras 10 opções
            }
            
            print(f"Select {idx}: {label}")
            print(f"  Name: {select.get_attribute('name')}")
            print(f"  ID: {select.get_attribute('id')}")
            print(f"  Opções encontradas: {len(opcoes)}")
            if opcoes:
                print(f"  Primeiras opções: {[o['text'] for o in opcoes[:5]]}")
            print()
        
        # Salvar resultado
        resultado = {
            'url': url_produto,
            'mapeamento': mapeamento,
            'total_selects': len(selects)
        }
        
        nome_arquivo = url_produto.split('/')[-1] + '_mapeamento.json'
        with open(nome_arquivo, 'w', encoding='utf-8') as f:
            json.dump(resultado, f, indent=2, ensure_ascii=False)
        
        print(f"\nMapeamento salvo em: {nome_arquivo}")
        
        return resultado
        
    except Exception as e:
        print(f"\nErro ao analisar: {e}")
        import traceback
        traceback.print_exc()
        return None
        
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python analisar_produto.py <url_do_produto>")
        print("\nExemplo:")
        print("python analisar_produto.py https://www.lojagraficaeskenazi.com.br/product/impressao-de-panfleto")
        sys.exit(1)
    
    url = sys.argv[1]
    analisar_produto(url)

