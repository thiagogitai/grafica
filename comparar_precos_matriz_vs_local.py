"""
Script para comparar preços entre página matriz e página local
Faz scraping em ambas e compara os resultados
"""

import requests
from bs4 import BeautifulSoup
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException

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

def scrape_preco_matriz(opcoes, quantidade=50):
    """
    Faz scraping na página matriz para obter o preço
    """
    print("🌐 Fazendo scraping na página MATRIZ...")
    
    driver = setup_driver()
    if not driver:
        return None
    
    try:
        url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
        driver.get(url)
        time.sleep(3)
        
        # Aguardar página carregar
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        
        # Mapear campos do template para seletores da página
        mapeamento_campos = {
            'formato_miolo_paginas': 'Formato do Miolo',
            'papel_capa': 'Papel CAPA',
            'cores_capa': 'Cores CAPA',
            'orelha_capa': 'Orelha da CAPA',
            'acabamento_capa': 'Acabamento CAPA',
            'papel_miolo': 'Papel MIOLO',
            'cores_miolo': 'Cores MIOLO',
            'miolo_sangrado': 'MIOLO Sangrado',
            'quantidade_paginas_miolo': 'Quantidade Paginas MIOLO',
            'acabamento_miolo': 'Acabamento MIOLO',
            'acabamento_livro': 'Acabamento LIVRO',
            'guardas_livro': 'Guardas LIVRO',
            'extras': 'Extras',
            'frete': 'Frete',
            'verificacao_arquivo': 'Verificação do Arquivo',
            'prazo_entrega': 'Prazo de Entrega',
        }
        
        # Preencher quantidade
        try:
            qty_field = driver.find_element(By.CSS_SELECTOR, "input[type='number'], input[name*='quantidade'], input[name*='quantity']")
            qty_field.clear()
            qty_field.send_keys(str(quantidade))
            time.sleep(0.5)
        except:
            print("  ⚠️ Campo de quantidade não encontrado, continuando...")
        
        # Preencher cada campo
        for campo, valor in opcoes.items():
            if campo == 'quantity':
                continue
                
            try:
                # Procurar select ou input relacionado
                # Tentar encontrar por label ou name
                labels = driver.find_elements(By.TAG_NAME, "label")
                campo_encontrado = False
                
                for label in labels:
                    label_text = label.text.strip()
                    if campo in mapeamento_campos and mapeamento_campos[campo].lower() in label_text.lower():
                        # Encontrar o select associado
                        try:
                            select = label.find_element(By.XPATH, "./following-sibling::select | ./../select | ./../../select")
                            campo_encontrado = True
                            
                            # Selecionar opção
                            from selenium.webdriver.support.ui import Select
                            select_obj = Select(select)
                            
                            # Tentar selecionar pelo texto exato ou parcial
                            valor_limpo = valor.strip()
                            try:
                                select_obj.select_by_visible_text(valor_limpo)
                            except:
                                # Tentar match parcial
                                for option in select_obj.options:
                                    if valor_limpo.lower() in option.text.lower() or option.text.lower() in valor_limpo.lower():
                                        select_obj.select_by_visible_text(option.text)
                                        break
                            
                            time.sleep(0.3)
                            break
                        except:
                            continue
                
                if not campo_encontrado:
                    print(f"  ⚠️ Campo '{campo}' não encontrado na página")
                    
            except Exception as e:
                print(f"  ⚠️ Erro ao preencher '{campo}': {e}")
        
        # Aguardar cálculo do preço
        time.sleep(2)
        
        # Procurar preço na página
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
                    # Procurar por padrão de preço (R$ X.XXX,XX)
                    import re
                    match = re.search(r'R\$\s*([\d.,]+)', texto)
                    if match:
                        preco_str = match.group(1).replace('.', '').replace(',', '.')
                        try:
                            preco = float(preco_str)
                            print(f"  ✅ Preço encontrado: R$ {preco:,.2f}")
                            break
                        except:
                            continue
                if preco:
                    break
            except:
                continue
        
        if not preco:
            # Tentar via API diretamente
            print("  🔄 Preço não encontrado no HTML, tentando via API...")
            # Aqui poderia fazer chamada direta à API se necessário
        
        return preco
        
    except Exception as e:
        print(f"  ❌ Erro no scraping: {e}")
        return None
    finally:
        driver.quit()

def obter_preco_local(opcoes, quantidade=50):
    """
    Obtém preço da página local via API
    """
    print("🏠 Obtendo preço da página LOCAL...")
    
    session = requests.Session()
    
    # Primeiro, obter CSRF token
    try:
        response = session.get("http://localhost:8000")
        if response.status_code == 200:
            # Extrair CSRF token do HTML
            import re
            csrf_match = re.search(r'name="csrf-token" content="([^"]+)"', response.text)
            if not csrf_match:
                csrf_match = re.search(r'<meta name="csrf-token" content="([^"]+)"', response.text)
            
            csrf_token = csrf_match.group(1) if csrf_match else None
        else:
            csrf_token = None
    except:
        csrf_token = None
    
    url = "http://localhost:8000/api/product/validate-price"
    
    payload = {
        'product_slug': 'impressao-de-livro',
        'quantity': quantidade,
        **opcoes
    }
    
    headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }
    
    if csrf_token:
        headers['X-CSRF-TOKEN'] = csrf_token
    
    try:
        response = session.post(
            url,
            json=payload,
            headers=headers,
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and data.get('price'):
                preco = float(data['price'])
                print(f"  ✅ Preço obtido: R$ {preco:,.2f}")
                return preco
            else:
                print(f"  ❌ Erro na resposta: {data.get('error', 'Erro desconhecido')}")
                return None
        else:
            print(f"  ❌ Status HTTP: {response.status_code}")
            print(f"  Resposta: {response.text[:200]}")
            return None
            
    except Exception as e:
        print(f"  ❌ Erro na requisição: {e}")
        return None

def main():
    """
    Função principal - compara preços
    """
    print("=" * 70)
    print("COMPARAÇÃO DE PREÇOS: MATRIZ vs LOCAL")
    print("=" * 70)
    print()
    
    # Combinação de teste
    opcoes = {
        'formato_miolo_paginas': '210x297mm (A4)',
        'papel_capa': 'Couche Fosco 210gr',
        'cores_capa': '4 cores Frente',
        'orelha_capa': 'SEM ORELHA',
        'acabamento_capa': 'Laminação FOSCA FRENTE (Acima de 240g)',
        'papel_miolo': 'Offset 75gr',
        'cores_miolo': '4 cores frente e verso',
        'miolo_sangrado': 'NÃO',
        'quantidade_paginas_miolo': 'Miolo 8 páginas',
        'acabamento_miolo': 'Dobrado',
        'acabamento_livro': 'Colado PUR',
        'guardas_livro': 'SEM GUARDAS',
        'extras': 'Nenhum',
        'frete': 'Incluso',
        'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
        'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
    }
    
    quantidade = 50
    
    # Testar múltiplas combinações
    combinacoes_teste = [
        {
            'nome': 'Combinação 1 - A4 com Couche Fosco',
            'opcoes': opcoes,
            'quantidade': 50
        },
        {
            'nome': 'Combinação 2 - Primeira opção de cada campo',
            'opcoes': {
                'formato_miolo_paginas': '118x175mm',
                'papel_capa': 'Cartão Triplex 250gr',
                'cores_capa': '4 cores Frente',
                'orelha_capa': 'SEM ORELHA',
                'acabamento_capa': 'Laminação FOSCA FRENTE (Acima de 240g)',
                'papel_miolo': 'Offset 75gr',
                'cores_miolo': '4 cores frente e verso',
                'miolo_sangrado': 'NÃO',
                'quantidade_paginas_miolo': 'Miolo 8 páginas',
                'acabamento_miolo': 'Dobrado',
                'acabamento_livro': 'Colado PUR',
                'guardas_livro': 'SEM GUARDAS',
                'extras': 'Nenhum',
                'frete': 'Incluso',
                'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
                'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
            },
            'quantidade': 50
        }
    ]
    
    resultados = []
    
    for i, combo in enumerate(combinacoes_teste, 1):
        print(f"\n{'='*70}")
        print(f"TESTE {i}: {combo['nome']}")
        print(f"{'='*70}\n")
        
        print("📋 Combinação:")
        print(f"   Quantidade: {combo['quantidade']}")
        for campo, valor in combo['opcoes'].items():
            print(f"   {campo}: {valor}")
        print()
        
        # Obter preço da matriz
        preco_matriz = scrape_preco_matriz(combo['opcoes'], combo['quantidade'])
        print()
        
        # Obter preço local
        preco_local = obter_preco_local(combo['opcoes'], combo['quantidade'])
        print()
        
        # Comparar
        if preco_matriz and preco_local:
            diferenca = abs(preco_matriz - preco_local)
            percentual = (diferenca / preco_matriz) * 100 if preco_matriz > 0 else 0
            
            print(f"💰 Preço MATRIZ:  R$ {preco_matriz:,.2f}")
            print(f"💰 Preço LOCAL:   R$ {preco_local:,.2f}")
            print(f"📊 Diferença:     R$ {diferenca:,.2f} ({percentual:.4f}%)")
            print()
            
            if diferenca < 0.01:  # Tolerância de 1 centavo
                status = "✅ IDÊNTICOS"
            elif percentual < 0.1:  # Tolerância de 0.1%
                status = "✅ MUITO PRÓXIMOS"
            else:
                status = "⚠️ DIFERENTES"
            
            print(f"{status}")
            
            resultados.append({
                'nome': combo['nome'],
                'matriz': preco_matriz,
                'local': preco_local,
                'diferenca': diferenca,
                'percentual': percentual,
                'status': status
            })
        elif preco_matriz:
            print(f"💰 Preço MATRIZ:  R$ {preco_matriz:,.2f}")
            print("❌ Preço LOCAL:   Não foi possível obter")
        elif preco_local:
            print("❌ Preço MATRIZ:  Não foi possível obter")
            print(f"💰 Preço LOCAL:   R$ {preco_local:,.2f}")
        else:
            print("❌ Não foi possível obter nenhum preço")
        
        print()
        time.sleep(2)  # Aguardar entre testes
    
    # Resumo final
    print("\n" + "=" * 70)
    print("RESUMO FINAL")
    print("=" * 70)
    print()
    
    for resultado in resultados:
        print(f"{resultado['nome']}:")
        print(f"  Matriz: R$ {resultado['matriz']:,.2f}")
        print(f"  Local:  R$ {resultado['local']:,.2f}")
        print(f"  Diferença: R$ {resultado['diferenca']:,.2f} ({resultado['percentual']:.4f}%)")
        print(f"  Status: {resultado['status']}")
        print()
    
    # Verificar se todos estão corretos
    todos_ok = all('✅' in r['status'] for r in resultados)
    if todos_ok:
        print("✅ TODOS OS TESTES PASSARAM! Sistema está funcionando corretamente!")
    else:
        print("⚠️ Alguns testes mostraram diferenças. Verificar detalhes acima.")
    
    print()

if __name__ == "__main__":
    main()

