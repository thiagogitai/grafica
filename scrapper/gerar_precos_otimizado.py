"""
Script otimizado para gerar preços usando a fórmula
Processa em lotes e salva incrementalmente
"""
import json
import os
from itertools import product
import hashlib

def carregar_valores_itens():
    """Carrega os valores de cada item coletados"""
    caminhos = [
        'valores_cada_item_qtd50.json',
        '../valores_cada_item_qtd50.json',
        os.path.join(os.path.dirname(__file__), 'valores_cada_item_qtd50.json'),
        os.path.join(os.path.dirname(os.path.dirname(__file__)), 'valores_cada_item_qtd50.json'),
    ]
    
    for caminho in caminhos:
        if os.path.exists(caminho):
            with open(caminho, 'r', encoding='utf-8') as f:
                return json.load(f)
    return None

def carregar_config_livro():
    """Carrega o JSON de configuração do livro (já reduzido)"""
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    json_path = os.path.join(base_dir, 'resources', 'data', 'products', 'impressao-de-livro.json')
    
    with open(json_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def calcular_preco_por_formula(valores_data, opcoes_selecionadas, quantidade):
    """Calcula preço usando fórmula"""
    preco_base_qtd50 = valores_data['preco_base']['valor']
    preco_unitario_base = preco_base_qtd50 / 50
    soma_diferencas_unitarias = 0
    
    # Mapear campos do config para IDs dos valores coletados
    # Baseado na ordem coletada: Options[1] = papel_capa, Options[2] = cores_capa, etc.
    mapeamento_campos = {
        'papel_capa': 'Options[1].Value',
        'cores_capa': 'Options[2].Value',
        'orelha_capa': 'Options[3].Value',
        'acabamento_capa': 'Options[4].Value',
        'papel_miolo': 'Options[5].Value',
        'cores_miolo': 'Options[6].Value',
        'miolo_sangrado': 'Options[7].Value',
        'quantidade_paginas_miolo': 'Options[8].Value',
        'acabamento_miolo': 'Options[9].Value',
        'acabamento_livro': 'Options[10].Value',
        'guardas_livro': 'Options[11].Value',
        'extras': 'Options[12].Value',
        'formato_miolo_paginas': 'Options[1].Value',  # Pode ser que formato esteja em outro lugar
    }
    
    for campo_nome, valor_opcao in opcoes_selecionadas.items():
        campo_id = mapeamento_campos.get(campo_nome)
        if not campo_id:
            continue
        
        # Encontrar o campo nos dados
        for campo in valores_data['campos']:
            campo_identificador = campo.get('id') or campo.get('name') or campo.get('nome')
            
            if campo_id == campo_identificador:
                # Encontrar a opção (comparação mais flexível)
                for opcao in campo.get('opcoes', []):
                    valor_coletado = opcao.get('valor', '').strip()
                    texto_coletado = opcao.get('texto', '').strip()
                    valor_procurado = str(valor_opcao).strip()
                    
                    # Tentar várias formas de matching
                    if (valor_coletado == valor_procurado or 
                        texto_coletado == valor_procurado or
                        valor_procurado in valor_coletado or
                        valor_procurado in texto_coletado or
                        valor_coletado in valor_procurado or
                        texto_coletado in valor_procurado):
                        
                        diferenca_unit = opcao.get('diferenca_unitaria', 0)
                        if diferenca_unit is not None:
                            soma_diferencas_unitarias += diferenca_unit
                        break
                break
    
    preco_total = (preco_unitario_base * quantidade) + (soma_diferencas_unitarias * quantidade)
    return round(preco_total, 2)

def gerar_combinacoes_do_config(config):
    """Gera combinações baseado no JSON de configuração (já reduzido)"""
    campos_com_opcoes = []
    
    for opt in config.get('options', []):
        nome = opt.get('name')
        if nome == 'quantity':
            continue
        
        choices = opt.get('choices', [])
        valores = [c.get('value') for c in choices if c.get('value')]
        
        if valores:
            campos_com_opcoes.append({
                'nome': nome,
                'valores': valores
            })
    
    return campos_com_opcoes

def criar_id_combinacao(opcoes):
    """Cria ID único e curto para a combinação"""
    # Ordenar por nome do campo para garantir consistência
    chave = "_".join([f"{k}:{v}" for k, v in sorted(opcoes.items())])
    # Usar hash para ID curto
    return hashlib.md5(chave.encode()).hexdigest()[:16]

def main():
    """Função principal otimizada"""
    print("="*70)
    print("GERADOR DE PRECOS OTIMIZADO - IMPRESSAO DE LIVRO")
    print("="*70)
    
    # Carregar dados
    print("\n1. Carregando dados...")
    valores_data = carregar_valores_itens()
    if not valores_data:
        print("ERRO: Arquivo valores_cada_item_qtd50.json nao encontrado")
        return
    
    config = carregar_config_livro()
    print(f"OK - Preco base (qtd 50): R$ {valores_data['preco_base']['valor']:.2f}")
    print(f"OK - Config carregado: {len(config.get('options', []))} opcoes")
    
    # Gerar campos e opções do config
    print("\n2. Preparando combinacoes do config...")
    campos_info = gerar_combinacoes_do_config(config)
    
    # Calcular total de combinações
    total_combinacoes = 1
    for campo in campos_info:
        total_combinacoes *= len(campo['valores'])
    
    print(f"Total de combinacoes: {total_combinacoes:,}")
    
    if total_combinacoes > 10_000_000:
        print(f"\nAVISO: Muitas combinacoes ({total_combinacoes:,})")
        print("Processando em lotes e salvando incrementalmente...")
    
    # Quantidades alvo
    quantidades = [50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 
                   600, 700, 800, 900, 1000, 1250, 1500, 1750, 2000, 
                   2250, 2500, 2750, 3000, 3250, 3500, 3750, 4000, 
                   4250, 4500, 4750, 5000]
    
    # Preparar estrutura de preços
    precos_finais = {}
    arquivo_saida = 'precos_livro.json'
    arquivo_temp = 'precos_livro_temp.json'
    
    # Processar combinações
    print(f"\n3. Processando {total_combinacoes:,} combinacoes...")
    
    # Preparar lista de valores para product
    lista_valores = [campo['valores'] for campo in campos_info]
    nomes_campos = [campo['nome'] for campo in campos_info]
    
    contador = 0
    lote_atual = {}
    tamanho_lote = 10000  # Salvar a cada 10k combinações
    
    try:
        # Tentar carregar progresso anterior
        if os.path.exists(arquivo_temp):
            with open(arquivo_temp, 'r', encoding='utf-8') as f:
                lote_atual = json.load(f)
                contador = len(lote_atual)
                print(f"Continuando de: {contador:,} combinacoes ja processadas")
    except:
        pass
    
    for combinacao in product(*lista_valores):
        contador += 1
        
        # Criar dict com opções
        opcoes = dict(zip(nomes_campos, combinacao))
        
        # Criar ID único
        combinacao_id = criar_id_combinacao(opcoes)
        
        # Calcular preços para todas as quantidades
        precos_quantidades = {}
        for qtd in quantidades:
            preco = calcular_preco_por_formula(valores_data, opcoes, qtd)
            precos_quantidades[str(qtd)] = preco
        
        lote_atual[combinacao_id] = precos_quantidades
        
        # Salvar incrementalmente
        if contador % tamanho_lote == 0:
            # Mesclar com arquivo principal se existir
            if os.path.exists(arquivo_temp):
                with open(arquivo_temp, 'r', encoding='utf-8') as f:
                    existente = json.load(f)
                    lote_atual.update(existente)
            
            # Salvar lote
            with open(arquivo_temp, 'w', encoding='utf-8') as f:
                json.dump(lote_atual, f, ensure_ascii=False, indent=2)
            
            progresso = (contador / total_combinacoes) * 100
            print(f"Salvo: {contador:,} / {total_combinacoes:,} ({progresso:.1f}%) - {len(lote_atual):,} combinacoes")
            
            # Limpar lote atual (manter apenas para evitar duplicatas)
            lote_atual = {}
        
        if contador >= total_combinacoes:
            break
    
    # Salvar final
    print("\n4. Salvando arquivo final...")
    
    # Mesclar último lote
    if os.path.exists(arquivo_temp):
        with open(arquivo_temp, 'r', encoding='utf-8') as f:
            existente = json.load(f)
            lote_atual.update(existente)
    
    precos_finais = lote_atual
    
    resultado = {
        'metadata': {
            'quantidade_base': 50,
            'preco_base': valores_data['preco_base']['valor'],
            'total_combinacoes': len(precos_finais),
            'quantidades_disponiveis': quantidades,
        },
        'precos': precos_finais
    }
    
    with open(arquivo_saida, 'w', encoding='utf-8') as f:
        json.dump(resultado, f, ensure_ascii=False, indent=2)
    
    # Remover arquivo temporário
    if os.path.exists(arquivo_temp):
        os.remove(arquivo_temp)
    
    print(f"OK - Arquivo salvo: {arquivo_saida}")
    print(f"OK - Total de combinacoes: {len(precos_finais):,}")
    
    print("\n" + "="*70)
    print("CONCLUIDO!")
    print("="*70)

if __name__ == "__main__":
    main()

