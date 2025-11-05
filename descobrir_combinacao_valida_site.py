#!/usr/bin/env python3
"""
Script para descobrir uma combinação válida de opções diretamente do site matriz
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select
import time
import json

base_url = "https://www.lojagraficaeskenazi.com.br"
url = f"{base_url}/product/impressao-de-livro"

chrome_options = Options()
# chrome_options.add_argument('--headless=new')  # Remover headless para ver o que está acontecendo
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--window-size=1920,1080')

service = Service()
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    print("🔍 Acessando site matriz...")
    driver.get(url)
    time.sleep(5)
    
    # Aceitar cookies
    try:
        driver.execute_script("""
            var btn = Array.from(document.querySelectorAll('button')).find(
                b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar')
            );
            if (btn) btn.click();
        """)
        time.sleep(1)
    except:
        pass
    
    # Aguardar carregamento
    time.sleep(3)
    
    # Encontrar todos os selects
    selects = driver.find_elements(By.TAG_NAME, 'select')
    print(f"📊 Total de selects encontrados: {len(selects)}\n")
    
    # Encontrar input de quantidade
    try:
        qtd_input = driver.find_element(By.ID, "Q1")
        print("✅ Input de quantidade (Q1) encontrado\n")
    except:
        print("❌ Input de quantidade não encontrado\n")
        qtd_input = None
    
    # Aplicar uma combinação simples
    print("🧪 Aplicando combinação de teste...\n")
    
    # Quantidade
    if qtd_input:
        qtd_input.clear()
        qtd_input.send_keys("50")
        time.sleep(0.5)
        driver.execute_script("arguments[0].blur();", qtd_input)
        print("✅ Quantidade: 50")
    
    # Aplicar opções nos selects (primeira opção válida de cada)
    opcoes_aplicadas = {}
    
    for idx, select in enumerate(selects):
        try:
            opcoes = select.find_elements(By.TAG_NAME, 'option')
            if len(opcoes) > 1:  # Pular primeira opção vazia
                # Selecionar segunda opção (primeira válida)
                Select(select).select_by_index(1)
                texto_opcao = opcoes[1].text.strip()
                opcoes_aplicadas[f"select_{idx}"] = texto_opcao
                print(f"✅ Select {idx}: {texto_opcao}")
                time.sleep(0.5)
        except Exception as e:
            print(f"⚠️ Erro ao selecionar select {idx}: {e}")
    
    # Aguardar cálculo
    print("\n⏳ Aguardando cálculo do preço...")
    time.sleep(5)
    
    # Tentar encontrar o preço
    preco_encontrado = None
    try:
        preco_element = driver.find_element(By.ID, "calc-total")
        preco_texto = preco_element.text.strip()
        print(f"\n💰 Preço encontrado: {preco_texto}")
        preco_encontrado = preco_texto
        
        # Verificar se é um valor válido (não R$ 0,00 ou erro)
        if "R$" in preco_texto and "0,00" not in preco_texto:
            print("✅ Preço válido!")
        else:
            print("⚠️ Preço pode estar zerado ou inválido")
    except Exception as e:
        print(f"\n❌ Erro ao encontrar preço: {e}")
    
    # Capturar todas as opções selecionadas
    print("\n📋 OPÇÕES APLICADAS:")
    print("=" * 80)
    for campo, valor in opcoes_aplicadas.items():
        print(f"   {campo}: {valor}")
    
    if preco_encontrado:
        print("\n✅ COMBINAÇÃO VÁLIDA ENCONTRADA!")
        print("=" * 80)
        print(f"Quantidade: 50")
        for campo, valor in opcoes_aplicadas.items():
            print(f"{campo}: {valor}")
        print(f"\nPreço: {preco_encontrado}")
    else:
        print("\n⚠️ Não foi possível confirmar se a combinação é válida")
    
    # Salvar para análise
    resultado = {
        'quantidade': 50,
        'opcoes': opcoes_aplicadas,
        'preco': preco_encontrado
    }
    
    with open('combinacao_valida_livro.json', 'w', encoding='utf-8') as f:
        json.dump(resultado, f, indent=2, ensure_ascii=False)
    
    print("\n💾 Resultado salvo em 'combinacao_valida_livro.json'")
    
    # Manter aberto por 10 segundos para visualizar
    print("\n⏳ Mantendo navegador aberto por 10 segundos para visualização...")
    time.sleep(10)
    
except Exception as e:
    print(f"❌ Erro: {e}")
    import traceback
    traceback.print_exc()
    
finally:
    driver.quit()

