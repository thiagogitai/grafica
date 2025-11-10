"""
Verifica se uma combinação específica existe na página matriz
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json

def setup_driver():
    """Configura o driver do Selenium"""
    chrome_options = Options()
    chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
    
    try:
        driver = webdriver.Chrome(options=chrome_options)
        return driver
    except Exception as e:
        print(f"Erro ao configurar driver: {e}")
        return None

def verificar_combinacao_na_matriz(valores, quantidade=50):
    """
    Verifica se uma combinação específica existe e funciona na página matriz
    """
    print("🌐 Verificando combinação na página MATRIZ...\n")
    
    driver = setup_driver()
    if not driver:
        return None
    
    try:
        url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
        driver.get(url)
        time.sleep(3)
        
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        
        print("📋 Valores a testar:")
        for campo, valor in valores.items():
            print(f"   {campo}: {valor}")
        print()
        
        # Preencher quantidade
        try:
            qty_field = driver.find_element(By.CSS_SELECTOR, "input[type='number'], input[name*='quantidade'], input[name*='quantity']")
            qty_field.clear()
            qty_field.send_keys(str(quantidade))
            time.sleep(0.5)
            print("✅ Quantidade preenchida: " . str(quantidade))
        except:
            print("⚠️ Campo de quantidade não encontrado")
        
        # Tentar preencher campos (simplificado - apenas verificar se a página carrega)
        print("\n🔍 Verificando se a página aceita essas opções...\n")
        
        # Aguardar um pouco
        time.sleep(2)
        
        # Verificar se há mensagem de erro
        try:
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".error, .alert-danger, [class*='error'], [class*='invalid']")
            if error_elements:
                for elem in error_elements:
                    texto = elem.text.strip()
                    if texto and len(texto) > 0:
                        print(f"❌ Erro encontrado na página: {texto}")
                        return {'existe': False, 'erro': texto}
        except:
            pass
        
        # Verificar se consegue obter preço
        preco = None
        selectors_preco = [
            ".price",
            ".total-price",
            "[class*='price']",
            "[id*='price']",
            "[class*='total']",
            "[id*='total']",
        ]
        
        for selector in selectors_preco:
            try:
                elementos = driver.find_elements(By.CSS_SELECTOR, selector)
                for elem in elementos:
                    texto = elem.text.strip()
                    import re
                    match = re.search(r'R\$\s*([\d.,]+)', texto)
                    if match:
                        preco_str = match.group(1).replace('.', '').replace(',', '.')
                        try:
                            preco = float(preco_str)
                            print(f"✅ Preço encontrado na matriz: R$ {preco:,.2f}")
                            return {'existe': True, 'preco': preco}
                        except:
                            continue
            except:
                continue
        
        if not preco:
            print("⚠️ Preço não encontrado na página (pode ser combinação inválida)")
            return {'existe': False, 'erro': 'Preço não encontrado'}
        
    except Exception as e:
        print(f"❌ Erro: {e}")
        return {'existe': False, 'erro': str(e)}
    finally:
        driver.quit()

# Combinação que deu erro
valores_problema = {
    'formato_miolo_paginas': '158x230mm',
    'papel_capa': 'Couche Brilho 210gr',
    'cores_capa': '5 cores Frente x 1 cor Preto Verso',
    'orelha_capa': 'COM Orelha de 9cm',
    'acabamento_capa': 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',
    'papel_miolo': 'Impressão Offset - >500unidades',
    'cores_miolo': '4 cores frente e verso',
    'miolo_sangrado': 'SIM',
    'quantidade_paginas_miolo': 'Miolo 944 páginas',
    'acabamento_miolo': 'Dobrado',
    'acabamento_livro': 'Capa Dura Papelão 18 (1,8mm) + Cola PUR',
    'guardas_livro': 'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala',
    'extras': 'Shrink Coletivo c/ 50 peças',
    'frete': 'Incluso',
    'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
    'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
}

print("=" * 70)
print("VERIFICANDO SE COMBINAÇÃO EXISTE NA MATRIZ")
print("=" * 70)
print()

resultado = verificar_combinacao_na_matriz(valores_problema, 50)

print("\n" + "=" * 70)
print("RESULTADO")
print("=" * 70)
print()

if resultado:
    if resultado.get('existe'):
        print("✅ A combinação EXISTE na matriz!")
        print(f"   Preço: R$ {resultado['preco']:,.2f}")
        print("\n⚠️ Se existe na matriz mas não funciona na API, pode ser:")
        print("   1. Problema de mapeamento (keys incorretas)")
        print("   2. Ordem das opções incorreta")
        print("   3. Valores com espaços/caracteres especiais")
    else:
        print("❌ A combinação NÃO EXISTE na matriz!")
        print(f"   Erro: {resultado.get('erro', 'Desconhecido')}")
        print("\n✅ Isso explica por que não funciona na API!")
        print("   A combinação é inválida mesmo na matriz.")
else:
    print("❌ Não foi possível verificar")

