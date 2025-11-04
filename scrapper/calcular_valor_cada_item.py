"""
Script para calcular o valor de cada item/opção individualmente
Começa com quantidade 50 e testa cada campo isoladamente
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json
import re

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

def calcular_valor_cada_item():
    """Calcula o valor de cada opção individualmente"""
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    options = Options()
    options.add_argument("--start-maximized")
    
    driver = webdriver.Chrome(options=options)
    
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
        
        # Encontrar elemento de preço
        print("\nProcurando elemento de preco...")
        preco_element = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "calc-total"))
        )
        print("OK - Elemento encontrado: calc-total")
        
        # Encontrar todos os selects
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"\nEncontrados {len(selects)} campos select")
        
        if not selects:
            print("ERRO - Nenhum campo select encontrado")
            return
        
        # Identificar campo de quantidade (geralmente o primeiro ou que contém "quantity")
        qtd_select = None
        qtd_index = None
        
        for i, select in enumerate(selects):
            select_id = select.get_attribute('id') or ''
            select_name = select.get_attribute('name') or ''
            if 'quantity' in select_id.lower() or 'quantity' in select_name.lower() or i == 0:
                qtd_select = select
                qtd_index = i
                print(f"OK - Campo de quantidade identificado: {select_id or select_name} (indice {i})")
                break
        
        if not qtd_select:
            qtd_select = selects[0]
            qtd_index = 0
            print(f"Usando primeiro select como quantidade")
        
        # Definir quantidade como 50
        print("\n" + "="*70)
        print("DEFININDO QUANTIDADE: 50")
        print("="*70)
        
        opcoes_qtd = qtd_select.find_elements(By.TAG_NAME, 'option')
        quantidade_definida = False
        
        for opt in opcoes_qtd:
            if opt.get_attribute('value') == "50" or opt.text.strip() == "50":
                Select(qtd_select).select_by_value(opt.get_attribute('value'))
                quantidade_definida = True
                print(f"OK - Quantidade 50 selecionada")
                break
        
        if not quantidade_definida:
            # Tentar por índice ou texto
            for i, opt in enumerate(opcoes_qtd):
                if "50" in opt.text or "50" in opt.get_attribute('value'):
                    Select(qtd_select).select_by_index(i)
                    quantidade_definida = True
                    print(f"OK - Quantidade 50 selecionada (por texto)")
                    break
        
        time.sleep(3)
        preco_base = preco_element.text
        valor_base = extrair_valor_preco(preco_base)
        print(f"Preco base (qtd 50): {preco_base} (R$ {valor_base:.2f})")
        
        # Agora testar cada campo individualmente
        print("\n" + "="*70)
        print("TESTANDO CADA CAMPO INDIVIDUALMENTE")
        print("="*70)
        
        resultados = {
            'quantidade_base': 50,
            'preco_base': {
                'texto': preco_base,
                'valor': valor_base
            },
            'campos': []
        }
        
        for i, select in enumerate(selects):
            # Pular campo de quantidade
            if i == qtd_index:
                continue
            
            select_id = select.get_attribute('id') or ''
            select_name = select.get_attribute('name') or ''
            campo_nome = select_id or select_name or f'campo_{i}'
            
            print(f"\n{'='*70}")
            print(f"Campo {i+1}: {campo_nome}")
            print(f"{'='*70}")
            
            opcoes = select.find_elements(By.TAG_NAME, 'option')
            opcoes_validas = [opt for opt in opcoes if opt.get_attribute('value')]
            
            if not opcoes_validas:
                print(f"  Pulando - sem opcoes validas")
                continue
            
            campo_resultado = {
                'indice': i,
                'nome': campo_nome,
                'id': select_id,
                'name': select_name,
                'opcoes': []
            }
            
            # Testar cada opção deste campo
            for j, opt in enumerate(opcoes_validas):
                valor_opt = opt.get_attribute('value')
                texto_opt = opt.text.strip()
                
                if not valor_opt:
                    continue
                
                try:
                    # Selecionar esta opção
                    Select(select).select_by_value(valor_opt)
                    time.sleep(1.5)  # Aguardar cálculo
                    
                    preco_atual = preco_element.text
                    valor_atual = extrair_valor_preco(preco_atual)
                    
                    # Calcular diferença em relação ao preço base
                    diferenca = None
                    diferenca_unitaria = None
                    if valor_base and valor_atual:
                        diferenca = valor_atual - valor_base
                        diferenca_unitaria = diferenca / 50  # Por unidade (qtd 50)
                    
                    opcao_resultado = {
                        'valor': valor_opt,
                        'texto': texto_opt[:100],  # Limitar tamanho
                        'preco_total': valor_atual,
                        'preco_texto': preco_atual,
                        'diferenca_total': diferenca,
                        'diferenca_unitaria': diferenca_unitaria
                    }
                    
                    campo_resultado['opcoes'].append(opcao_resultado)
                    
                    if diferenca:
                        print(f"  {j+1}. {texto_opt[:50]:<50} | Preco: {preco_atual:<15} | Diferenca: R$ {diferenca:>10.2f} (R$ {diferenca_unitaria:.4f}/un)")
                    else:
                        print(f"  {j+1}. {texto_opt[:50]:<50} | Preco: {preco_atual}")
                    
                except Exception as e:
                    print(f"  Erro ao testar opcao {j+1}: {e}")
            
            resultados['campos'].append(campo_resultado)
        
        # Salvar resultados
        arquivo_resultado = 'valores_cada_item_qtd50.json'
        with open(arquivo_resultado, 'w', encoding='utf-8') as f:
            json.dump(resultados, f, ensure_ascii=False, indent=2)
        
        print("\n" + "="*70)
        print("ANALISE COMPLETA")
        print("="*70)
        print(f"Arquivo salvo: {arquivo_resultado}")
        
        # Resumo
        print("\n" + "="*70)
        print("RESUMO")
        print("="*70)
        print(f"Quantidade base: 50")
        print(f"Preco base: {preco_base} (R$ {valor_base:.2f})")
        print(f"Campos analisados: {len(resultados['campos'])}")
        
        # Mostrar campos com maior variação
        print("\nCampos com maior impacto no preco:")
        campos_impacto = []
        for campo in resultados['campos']:
            if campo['opcoes']:
                valores = [opt['diferenca_total'] for opt in campo['opcoes'] if opt.get('diferenca_total') is not None]
                if valores:
                    max_diff = max(abs(v) for v in valores)
                    campos_impacto.append((campo['nome'], max_diff))
        
        campos_impacto.sort(key=lambda x: x[1], reverse=True)
        for nome, max_diff in campos_impacto[:10]:
            print(f"  {nome}: ate R$ {max_diff:.2f} de diferenca")
        
        input("\nPressione ENTER para fechar...")
        
    except Exception as e:
        print(f"ERRO: {e}")
        import traceback
        traceback.print_exc()
    finally:
        driver.quit()

if __name__ == "__main__":
    calcular_valor_cada_item()
