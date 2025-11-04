"""
Script AUTOMÁTICO para analisar produtos e gerar todos os JSONs
Executa: análise -> geração de JSON -> pronto para usar
"""
import sys
import json
import os
import time
from analisar_produto import analisar_produto
from criar_todos_configs import ler_mapeamento, criar_config_do_mapeamento

# URLs dos produtos
PRODUTOS = {
    'impressao-de-panfleto': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-panfleto',
        'tem_quantidade': True
    },
    'impressao-de-apostila': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila',
        'tem_quantidade': False
    },
    'impressao-online-de-livretos-personalizados': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-online-de-livretos-personalizados',
        'tem_quantidade': False
    },
    'impressao-de-revista': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista',
        'tem_quantidade': False
    },
    'impressao-de-tabloide': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide',
        'tem_quantidade': False
    },
    'impressao-de-jornal-de-bairro': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-jornal-de-bairro',
        'tem_quantidade': False
    },
    'impressao-de-guia-de-bairro': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-guia-de-bairro',
        'tem_quantidade': False
    },
}

def main():
    print("=" * 70)
    print("GERADOR AUTOMATICO DE CONFIGURACOES DE PRODUTOS")
    print("=" * 70)
    print("\nEste script vai:")
    print("1. Analisar cada produto")
    print("2. Gerar arquivo JSON automaticamente")
    print("3. Pronto para usar no sistema!")
    print("\n" + "=" * 70 + "\n")
    
    output_dir = '../resources/data/products'
    os.makedirs(output_dir, exist_ok=True)
    
    sucesso = 0
    erros = 0
    
    for slug, info in PRODUTOS.items():
        print(f"\n{'='*70}")
        print(f"PRODUTO: {slug}")
        print(f"{'='*70}")
        
        config_path = os.path.join(output_dir, f'{slug}.json')
        
        # Se já existe, pular (ou usar flag --force)
        if os.path.exists(config_path) and '--force' not in sys.argv:
            print(f"PULADO - Config ja existe: {config_path}")
            print("Use --force para regenerar")
            continue
        
        # 1. Analisar produto
        print(f"\n[1/2] Analisando produto...")
        resultado = analisar_produto(info['url'])
        
        if not resultado:
            print(f"ERRO - Falha ao analisar {slug}")
            erros += 1
            continue
        
        # 2. Gerar JSON
        print(f"\n[2/2] Gerando JSON de configuracao...")
        config = criar_config_do_mapeamento(resultado, slug, info['tem_quantidade'])
        
        with open(config_path, 'w', encoding='utf-8') as f:
            json.dump(config, f, indent=2, ensure_ascii=False)
        
        print(f"OK - Config gerado: {config_path}")
        sucesso += 1
        
        # Pequeno delay entre produtos
        time.sleep(2)
    
    print("\n" + "=" * 70)
    print("RESUMO")
    print("=" * 70)
    print(f"Sucesso: {sucesso}")
    print(f"Erros: {erros}")
    print(f"Total: {len(PRODUTOS)}")
    print("\n" + "=" * 70)
    print("CONCLUIDO!")
    print("=" * 70)
    print("\nOs arquivos JSON estao em: resources/data/products/")
    print("Agora basta configurar os produtos no banco com template='config:auto'")

if __name__ == "__main__":
    main()

