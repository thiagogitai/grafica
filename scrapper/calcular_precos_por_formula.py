"""
Script para calcular preços usando a fórmula descoberta
Baseado nos valores coletados de cada item individualmente
"""
import json
import os

def carregar_valores_itens():
    """Carrega os valores de cada item coletados"""
    arquivo = 'valores_cada_item_qtd50.json'
    if not os.path.exists(arquivo):
        print(f"ERRO: Arquivo {arquivo} nao encontrado")
        print("Execute primeiro: python calcular_valor_cada_item.py")
        return None
    
    with open(arquivo, 'r', encoding='utf-8') as f:
        return json.load(f)

def calcular_preco_baseado_em_opcoes(valores_data, opcoes_selecionadas, quantidade):
    """
    Calcula o preço baseado nas opções selecionadas
    
    Args:
        valores_data: Dados coletados do arquivo JSON
        opcoes_selecionadas: Dict com {campo_nome: valor_opcao}
        quantidade: Quantidade desejada
    
    Returns:
        Preço total calculado
    """
    # Preço base (quantidade 50)
    preco_base_qtd50 = valores_data['preco_base']['valor']
    
    # Calcular diferenças de cada opção selecionada
    diferenca_total = 0
    
    for campo_nome, valor_opcao in opcoes_selecionadas.items():
        # Encontrar o campo nos dados
        for campo in valores_data['campos']:
            campo_id = campo.get('id') or campo.get('name') or campo.get('nome')
            
            # Verificar se é o campo correto
            if campo_nome in campo_id or campo_id in campo_nome:
                # Encontrar a opção
                for opcao in campo.get('opcoes', []):
                    if opcao['valor'] == valor_opcao or opcao['texto'] == valor_opcao:
                        # Pegar a diferença unitária (por unidade)
                        diferenca_unit = opcao.get('diferenca_unitaria', 0)
                        if diferenca_unit:
                            diferenca_total += diferenca_unit * quantidade
                        break
                break
    
    # Preço base ajustado para a quantidade
    # O preço base é para 50 unidades, então por unidade seria: preco_base_qtd50 / 50
    preco_unitario_base = preco_base_qtd50 / 50
    
    # Preço total = (preço unitário base + diferenças unitárias) * quantidade
    preco_total = (preco_unitario_base * quantidade) + diferenca_total
    
    return preco_total

def gerar_precos_para_quantidades(valores_data, opcoes_selecionadas, quantidades):
    """
    Gera preços para uma lista de quantidades
    """
    resultados = {}
    
    for qtd in quantidades:
        qtd_int = int(qtd)
        preco = calcular_preco_baseado_em_opcoes(valores_data, opcoes_selecionadas, qtd_int)
        resultados[str(qtd_int)] = round(preco, 2)
    
    return resultados

if __name__ == "__main__":
    print("Carregando valores coletados...")
    valores_data = carregar_valores_itens()
    
    if not valores_data:
        exit(1)
    
    print(f"OK - Valores carregados")
    print(f"Preco base (qtd 50): R$ {valores_data['preco_base']['valor']:.2f}")
    print(f"Campos disponiveis: {len(valores_data['campos'])}")
    
    # Exemplo de uso
    print("\n" + "="*70)
    print("EXEMPLO DE USO")
    print("="*70)
    
    # Opções de exemplo (usando os valores padrão do site)
    opcoes_exemplo = {
        'Options[1].Value': 'Cartão Triplex 250gr',  # Papel capa
        'Options[2].Value': '4 cores FxV',  # Cores capa
        'Options[3].Value': 'SEM ORELHA',  # Orelha
        # ... outras opções
    }
    
    quantidades = [50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 
                   600, 700, 800, 900, 1000, 1250, 1500, 1750, 2000, 
                   2250, 2500, 2750, 3000, 3250, 3500, 3750, 4000, 
                   4250, 4500, 4750, 5000]
    
    print("\nPara usar esta funcao:")
    print("1. Defina as opcoes selecionadas em um dict")
    print("2. Chame calcular_preco_baseado_em_opcoes()")
    print("3. Ou use gerar_precos_para_quantidades() para gerar todas as quantidades")
    
    print("\nArquivo de funcoes criado: calcular_precos_por_formula.py")
    print("Importe as funcoes em seu scraper para calcular precos sem fazer scraping!")

