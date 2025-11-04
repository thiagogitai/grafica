"""
Script para gerar preços para todas as combinações usando a fórmula descoberta
Baseado nos valores coletados de cada item individualmente
"""
import json
import os
from itertools import product

def carregar_valores_itens():
    """Carrega os valores de cada item coletados"""
    # Tentar vários caminhos possíveis
    caminhos = [
        'valores_cada_item_qtd50.json',  # Pasta atual
        '../valores_cada_item_qtd50.json',  # Pasta pai
        os.path.join(os.path.dirname(__file__), 'valores_cada_item_qtd50.json'),  # Mesma pasta do script
        os.path.join(os.path.dirname(os.path.dirname(__file__)), 'valores_cada_item_qtd50.json'),  # Raiz do projeto
    ]
    
    arquivo = None
    for caminho in caminhos:
        if os.path.exists(caminho):
            arquivo = caminho
            break
    
    if not arquivo:
        print(f"ERRO: Arquivo valores_cada_item_qtd50.json nao encontrado")
        print("Tentou procurar em:")
        for c in caminhos:
            print(f"  - {c}")
        print("\nExecute primeiro: python calcular_valor_cada_item.py")
        return None
    
    print(f"Carregando arquivo: {arquivo}")
    with open(arquivo, 'r', encoding='utf-8') as f:
        return json.load(f)

def calcular_preco_por_formula(valores_data, opcoes_selecionadas, quantidade):
    """
    Calcula o preço usando a fórmula descoberta:
    Preço Total = (Preço Base / 50 * Quantidade) + Soma(Diferenças Unitárias * Quantidade)
    
    Args:
        valores_data: Dados coletados do arquivo JSON
        opcoes_selecionadas: Dict com {campo_id: valor_opcao}
        quantidade: Quantidade desejada (int)
    
    Returns:
        Preço total calculado (float)
    """
    # Preço base para quantidade 50
    preco_base_qtd50 = valores_data['preco_base']['valor']
    
    # Preço unitário base
    preco_unitario_base = preco_base_qtd50 / 50
    
    # Soma das diferenças unitárias de cada opção selecionada
    soma_diferencas_unitarias = 0
    
    for campo_id, valor_opcao in opcoes_selecionadas.items():
        # Encontrar o campo nos dados
        for campo in valores_data['campos']:
            campo_identificador = campo.get('id') or campo.get('name') or campo.get('nome')
            
            # Verificar se é o campo correto (usar parte do ID para matching)
            if campo_id in campo_identificador or campo_identificador in campo_id:
                # Encontrar a opção específica
                for opcao in campo.get('opcoes', []):
                    # Comparar por valor ou texto
                    if (opcao.get('valor') == valor_opcao or 
                        opcao.get('texto', '').strip() == valor_opcao.strip() or
                        valor_opcao in opcao.get('texto', '') or
                        opcao.get('valor', '') == str(valor_opcao)):
                        
                        diferenca_unit = opcao.get('diferenca_unitaria', 0)
                        if diferenca_unit is not None:
                            soma_diferencas_unitarias += diferenca_unit
                        break
                break
    
    # Calcular preço total
    preco_total = (preco_unitario_base * quantidade) + (soma_diferencas_unitarias * quantidade)
    
    return round(preco_total, 2)

def gerar_todas_combinacoes(valores_data):
    """
    Gera todas as combinações possíveis de opções
    """
    # Lista de campos (exceto quantidade)
    campos = []
    for campo in valores_data['campos']:
        campo_id = campo.get('id') or campo.get('name') or campo.get('nome')
        opcoes = [opt.get('valor') for opt in campo.get('opcoes', []) if opt.get('valor')]
        
        if opcoes:
            campos.append({
                'id': campo_id,
                'opcoes': opcoes
            })
    
    # Gerar todas as combinações usando product
    combinacoes = []
    
    # Preparar lista de listas de opções para product
    lista_opcoes = []
    indices_campos = []
    
    for i, campo in enumerate(campos):
        lista_opcoes.append(campo['opcoes'])
        indices_campos.append((i, campo['id']))
    
    print(f"Gerando combinacoes para {len(campos)} campos...")
    print(f"Total estimado: {len(lista_opcoes[0]) if lista_opcoes else 0} * ...")
    
    # Calcular total
    total_combinacoes = 1
    for opcs in lista_opcoes:
        total_combinacoes *= len(opcs)
    
    print(f"Total de combinacoes: {total_combinacoes:,}")
    
    # Gerar combinações
    contador = 0
    for combinacao in product(*lista_opcoes):
        contador += 1
        if contador % 1000 == 0:
            print(f"Processadas: {contador:,} / {total_combinacoes:,} ({contador*100/total_combinacoes:.1f}%)")
        
        # Criar dict com opções selecionadas
        opcoes_selecionadas = {}
        for (i, campo_id), valor in zip(indices_campos, combinacao):
            opcoes_selecionadas[campo_id] = valor
        
        combinacoes.append(opcoes_selecionadas)
    
    return combinacoes, campos

def gerar_precos_para_quantidades(valores_data, opcoes_selecionadas):
    """
    Gera preços para todas as quantidades solicitadas
    """
    quantidades = [50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 
                   600, 700, 800, 900, 1000, 1250, 1500, 1750, 2000, 
                   2250, 2500, 2750, 3000, 3250, 3500, 3750, 4000, 
                   4250, 4500, 4750, 5000]
    
    precos = {}
    for qtd in quantidades:
        preco = calcular_preco_por_formula(valores_data, opcoes_selecionadas, qtd)
        precos[str(qtd)] = preco
    
    return precos

def criar_estrutura_precos_livro(valores_data):
    """
    Cria estrutura de preços similar ao flyer, mas para livro
    Estrutura: precos[combinacao_id][quantidade] = preco
    """
    print("\n" + "="*70)
    print("GERANDO PRECOS PARA TODAS AS COMBINACOES")
    print("="*70)
    
    # Gerar todas as combinações
    combinacoes, campos = gerar_todas_combinacoes(valores_data)
    
    print(f"\nTotal de combinacoes: {len(combinacoes):,}")
    
    # Quantidades alvo
    quantidades = [50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 
                   600, 700, 800, 900, 1000, 1250, 1500, 1750, 2000, 
                   2250, 2500, 2750, 3000, 3250, 3500, 3750, 4000, 
                   4250, 4500, 4750, 5000]
    
    # Estrutura final de preços
    precos_finais = {}
    
    # Processar cada combinação
    total_combinacoes = len(combinacoes)
    print(f"\nProcessando {total_combinacoes:,} combinacoes...")
    
    for idx, opcoes in enumerate(combinacoes):
        if (idx + 1) % 100 == 0:
            progresso = ((idx + 1) / total_combinacoes) * 100
            print(f"Progresso: {idx + 1:,} / {total_combinacoes:,} ({progresso:.1f}%)")
        
        # Criar ID único para esta combinação (usar hash ou concatenar valores)
        combinacao_id = "_".join([f"{k}_{v}" for k, v in sorted(opcoes.items())])
        # Limitar tamanho do ID
        if len(combinacao_id) > 200:
            combinacao_id = combinacao_id[:200]
        
        # Calcular preços para todas as quantidades
        precos_quantidades = {}
        for qtd in quantidades:
            preco = calcular_preco_por_formula(valores_data, opcoes, qtd)
            precos_quantidades[str(qtd)] = preco
        
        precos_finais[combinacao_id] = precos_quantidades
    
    return precos_finais, campos

def main():
    """Função principal"""
    print("="*70)
    print("GERADOR DE PRECOS - IMPRESSAO DE LIVRO")
    print("="*70)
    
    # Carregar valores coletados
    print("\n1. Carregando valores coletados...")
    valores_data = carregar_valores_itens()
    
    if not valores_data:
        return
    
    print(f"OK - Preco base (qtd 50): R$ {valores_data['preco_base']['valor']:.2f}")
    print(f"OK - Campos disponiveis: {len(valores_data['campos'])}")
    
    # Gerar estrutura de preços
    print("\n2. Gerando precos para todas as combinacoes...")
    precos_finais, campos_info = criar_estrutura_precos_livro(valores_data)
    
    # Salvar resultados
    print("\n3. Salvando resultados...")
    arquivo_saida = 'precos_livro.json'
    
    resultado = {
        'metadata': {
            'quantidade_base': 50,
            'preco_base': valores_data['preco_base']['valor'],
            'total_combinacoes': len(precos_finais),
            'quantidades_disponiveis': [50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 
                                        600, 700, 800, 900, 1000, 1250, 1500, 1750, 2000, 
                                        2250, 2500, 2750, 3000, 3250, 3500, 3750, 4000, 
                                        4250, 4500, 4750, 5000],
            'campos': [{'id': c.get('id'), 'nome': c.get('nome')} for c in valores_data['campos']]
        },
        'precos': precos_finais
    }
    
    with open(arquivo_saida, 'w', encoding='utf-8') as f:
        json.dump(resultado, f, ensure_ascii=False, indent=2)
    
    print(f"OK - Arquivo salvo: {arquivo_saida}")
    print(f"OK - Total de combinacoes: {len(precos_finais):,}")
    
    # Estatísticas
    print("\n" + "="*70)
    print("ESTATISTICAS")
    print("="*70)
    
    # Calcular preço mínimo e máximo
    todos_precos = []
    for combinacao, precos_qtd in precos_finais.items():
        todos_precos.extend(precos_qtd.values())
    
    if todos_precos:
        print(f"Preco minimo: R$ {min(todos_precos):.2f}")
        print(f"Preco maximo: R$ {max(todos_precos):.2f}")
        print(f"Preco medio: R$ {sum(todos_precos)/len(todos_precos):.2f}")
    
    print("\n" + "="*70)
    print("CONCLUIDO!")
    print("="*70)
    print(f"\nArquivo gerado: {arquivo_saida}")
    print("Este arquivo contem precos para todas as combinacoes e quantidades solicitadas.")

if __name__ == "__main__":
    main()

