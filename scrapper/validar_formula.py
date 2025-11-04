"""
Script para validar se a fórmula descoberta é real ou apenas aproximação
Testa combinações reais no site vs cálculo pela fórmula
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json
import re
import os

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

def carregar_valores_e_formula():
    """Carrega valores coletados"""
    caminhos = [
        'valores_cada_item_qtd50.json',
        '../valores_cada_item_qtd50.json',
    ]
    
    for caminho in caminhos:
        if os.path.exists(caminho):
            with open(caminho, 'r', encoding='utf-8') as f:
                return json.load(f)
    return None

def calcular_preco_por_formula(valores_data, opcoes_selecionadas, quantidade):
    """Calcula usando a fórmula assumida"""
    preco_base_qtd50 = valores_data['preco_base']['valor']
    preco_unitario_base = preco_base_qtd50 / 50
    soma_diferencas_unitarias = 0
    
    mapeamento_campos = {
        'papel_capa': 'Options[1].Value',
        'cores_capa': 'Options[2].Value',
        'orelha_capa': 'Options[3].Value',
        'acabamento_capa': 'Options[4].Value',
        'papel_miolo': 'Options[5].Value',
        'cores_miolo': 'Options[6].Value',
        'miolo_sangrado': 'Options[7].Value',
        'quantidade_paginas_miolo': 'Options[8].Value',
    }
    
    for campo_nome, valor_opcao in opcoes_selecionadas.items():
        campo_id = mapeamento_campos.get(campo_nome)
        if not campo_id:
            continue
        
        for campo in valores_data['campos']:
            campo_identificador = campo.get('id') or campo.get('name') or campo.get('nome')
            
            if campo_id == campo_identificador:
                for opcao in campo.get('opcoes', []):
                    valor_coletado = opcao.get('valor', '').strip()
                    texto_coletado = opcao.get('texto', '').strip()
                    valor_procurado = str(valor_opcao).strip()
                    
                    if (valor_coletado == valor_procurado or 
                        texto_coletado == valor_procurado or
                        valor_procurado in valor_coletado or
                        valor_procurado in texto_coletado):
                        
                        diferenca_unit = opcao.get('diferenca_unitaria', 0)
                        if diferenca_unit is not None:
                            soma_diferencas_unitarias += diferenca_unit
                        break
                break
    
    preco_total = (preco_unitario_base * quantidade) + (soma_diferencas_unitarias * quantidade)
    return round(preco_total, 2)

def testar_combinacao_real(driver, preco_element, qtd_select, opcoes_testar, quantidade):
    """Testa uma combinação real no site"""
    try:
        # Definir quantidade
        opcoes_qtd = qtd_select.find_elements(By.TAG_NAME, 'option')
        for opt in opcoes_qtd:
            if opt.get_attribute('value') == str(quantidade) or str(quantidade) in opt.text:
                Select(qtd_select).select_by_value(opt.get_attribute('value'))
                break
        
        time.sleep(1)
        
        # Selecionar todas as opções
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        for campo_nome, valor_opcao in opcoes_testar.items():
            # Encontrar o select correspondente
            # Assumindo ordem: Options[1], Options[2], etc.
            # Isso precisa ser ajustado baseado na estrutura real
            pass
        
        # Por enquanto, vamos testar apenas mudando algumas opções manualmente
        # e comparando com a fórmula
        
        time.sleep(2)
        preco_real = extrair_valor_preco(preco_element.text)
        return preco_real
        
    except Exception as e:
        print(f"Erro ao testar: {e}")
        return None

def validar_formula():
    """Valida se a fórmula funciona"""
    
    print("="*70)
    print("VALIDACAO DA FORMULA")
    print("="*70)
    
    # Carregar valores
    valores_data = carregar_valores_e_formula()
    if not valores_data:
        print("ERRO: Arquivo valores_cada_item_qtd50.json nao encontrado")
        return
    
    print("\nCarregando valores coletados...")
    print(f"Preco base (qtd 50): R$ {valores_data['preco_base']['valor']:.2f}")
    
    # Abrir site para testes reais
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    options = Options()
    options.add_argument("--start-maximized")
    
    driver = webdriver.Chrome(options=options)
    
    try:
        print("\nAcessando site para validacao...")
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
        
        # Encontrar elementos
        preco_element = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "calc-total"))
        )
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        qtd_select = selects[0]  # Primeiro é quantidade
        
        print(f"\nEncontrados {len(selects)} campos select")
        
        # Testar algumas combinações
        print("\n" + "="*70)
        print("TESTANDO COMBINACOES REAIS")
        print("="*70)
        
        testes = [
            {
                'quantidade': 50,
                'descricao': 'Base (qtd 50, opcoes padrao)'
            },
            {
                'quantidade': 100,
                'descricao': 'Qtd 100, opcoes padrao'
            },
            {
                'quantidade': 500,
                'descricao': 'Qtd 500, opcoes padrao'
            },
        ]
        
        resultados = []
        
        for teste in testes:
            qtd = teste['quantidade']
            
            # Definir quantidade no site
            opcoes_qtd = qtd_select.find_elements(By.TAG_NAME, 'option')
            for opt in opcoes_qtd:
                if opt.get_attribute('value') == str(qtd) or str(qtd) in opt.text:
                    Select(qtd_select).select_by_value(opt.get_attribute('value'))
                    break
            
            time.sleep(2)
            preco_real = extrair_valor_preco(preco_element.text)
            
            # Calcular pela fórmula (opções padrão = sem diferenças)
            preco_formula = (valores_data['preco_base']['valor'] / 50) * qtd
            
            diferenca = abs(preco_real - preco_formula) if preco_real and preco_formula else None
            percentual_erro = (diferenca / preco_real * 100) if diferenca and preco_real else None
            
            resultado = {
                'quantidade': qtd,
                'preco_real': preco_real,
                'preco_formula': preco_formula,
                'diferenca': diferenca,
                'percentual_erro': percentual_erro
            }
            
            resultados.append(resultado)
            
            print(f"\nTeste: {teste['descricao']}")
            print(f"  Preco REAL:    R$ {preco_real:.2f}")
            print(f"  Preco FORMULA: R$ {preco_formula:.2f}")
            if diferenca:
                print(f"  Diferenca:     R$ {diferenca:.2f} ({percentual_erro:.2f}%)")
        
        # Salvar resultados
        with open('validacao_formula.json', 'w', encoding='utf-8') as f:
            json.dump(resultados, f, ensure_ascii=False, indent=2)
        
        print("\n" + "="*70)
        print("ANALISE")
        print("="*70)
        
        erros = [r['percentual_erro'] for r in resultados if r.get('percentual_erro')]
        if erros:
            erro_medio = sum(erros) / len(erros)
            erro_max = max(erros)
            
            print(f"\nErro medio: {erro_medio:.2f}%")
            print(f"Erro maximo: {erro_max:.2f}%")
            
            if erro_max < 1:
                print("\nCONCLUSÃO: A formula parece ser REAL e precisa!")
            elif erro_max < 5:
                print("\nCONCLUSÃO: A formula e uma boa aproximacao, mas pode ter interacoes")
            else:
                print("\nCONCLUSÃO: A formula pode nao ser linear - pode haver interacoes ou tabelas")
        
        input("\nPressione ENTER para fechar...")
        
    except Exception as e:
        print(f"ERRO: {e}")
        import traceback
        traceback.print_exc()
    finally:
        driver.quit()

if __name__ == "__main__":
    validar_formula()

