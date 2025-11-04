"""
Script para criar arquivos JSON de configuração manualmente
baseado nos mapeamentos já analisados
"""
import json
import os

# Baseado no mapeamento do panfleto
PANFLETO_CONFIG = {
    "title_override": "Impressão de Panfleto Online",
    "base_price": None,
    "redirect_to_upload": True,
    "options": [
        {
            "name": "quantity",
            "label": "1- Quantidade",
            "type": "number",
            "default": 50,
            "min": 50,
            "step": 1
        },
        {
            "name": "papel",
            "label": "2- Papel",
            "type": "select",
            "choices": [
                {"value": "Couche Brilho 150gr ", "label": "Couche Brilho 150gr"},
                {"value": "Couche Brilho 90gr", "label": "Couche Brilho 90gr"},
                {"value": "Couche Brilho 115gr ", "label": "Couche Brilho 115gr"},
                {"value": "Couche Brilho 210gr ", "label": "Couche Brilho 210gr"},
                {"value": "Offset 75gr ", "label": "Offset 75gr"},
                {"value": "Offset 90gr ", "label": "Offset 90gr"},
                {"value": "Offset 120gr ", "label": "Offset 120gr"},
                {"value": "Offset 180gr ", "label": "Offset 180gr"},
                {"value": "Couche Fosco 150gr ", "label": "Couche Fosco 150gr"},
                {"value": "Cartão Triplex C2S 300gr ", "label": "Cartão Triplex C2S 300gr"}
            ]
        },
        {
            "name": "formato",
            "label": "3- Formato",
            "type": "select",
            "choices": [
                {"value": "A4: 210mmx297mm", "label": "A4: 210mmx297mm"},
                {"value": "A5: 210mmx148mm", "label": "A5: 210mmx148mm"},
                {"value": "A6: 105mmx148mm", "label": "A6: 105mmx148mm"},
                {"value": "DL: 210mmx100mm", "label": "DL: 210mmx100mm"},
                {"value": "300mmx310mm", "label": "300mmx310mm"},
                {"value": "420mmx300mm", "label": "420mmx300mm"},
                {"value": "210mmx210mm", "label": "210mmx210mm"},
                {"value": "150mmx150mm", "label": "150mmx150mm"},
                {"value": "90mmx120mm", "label": "90mmx120mm"},
                {"value": "100mmx100mm", "label": "100mmx100mm"}
            ]
        },
        {
            "name": "cores",
            "label": "4- Cores",
            "type": "select",
            "choices": [
                {"value": "4 cores frente e verso", "label": "4 cores frente e verso"},
                {"value": "4 cores frente", "label": "4 cores frente"},
                {"value": "1 cor frente preto", "label": "1 cor frente preto"},
                {"value": "1 cor frente e verso preto", "label": "1 cor frente e verso preto"}
            ]
        },
        {
            "name": "acabamento",
            "label": "5- Acabamento",
            "type": "select",
            "choices": [
                {"value": "Cantos Retos", "label": "Cantos Retos"},
                {"value": "Cantos Retos + Laminação FOSCO Bopp Frente e Verso (acima de 200g)", "label": "Cantos Retos + Laminação FOSCO Bopp Frente e Verso (acima de 200g)"},
                {"value": "Cantos Retos + Laminação BRILHO Bopp Frente e Verso (Acima de 200g)", "label": "Cantos Retos + Laminação BRILHO Bopp Frente e Verso (Acima de 200g)"}
            ]
        },
        {
            "name": "extras",
            "label": "6- Extras",
            "type": "select",
            "choices": [
                {"value": "Nenhum", "label": "Nenhum"}
            ]
        },
        {
            "name": "formato_arquivo",
            "label": "7- Formato do Arquivo",
            "type": "select",
            "choices": [
                {"value": "Arquivo PDF (fechado para impressão) (Grátis)", "label": "Arquivo PDF (fechado para impressão) (Grátis)"},
                {"value": "Arquvo CDR / INDD / AI / JPG / PNG (aberto) (R$ 80,00)", "label": "Arquvo CDR / INDD / AI / JPG / PNG (aberto) (R$ 80,00)"}
            ]
        },
        {
            "name": "verificacao_arquivo",
            "label": "8- Verificação do Arquivo",
            "type": "select",
            "choices": [
                {"value": "Digital On-Line (Grátis)", "label": "Digital On-Line (Grátis)"},
                {"value": "Prova de Cor Impressa - SOMENTE para São Paulo (R$ 150,00)", "label": "Prova de Cor Impressa - SOMENTE para São Paulo (R$ 150,00)"}
            ]
        },
        {
            "name": "prazo_entrega",
            "label": "9- Prazo de Entrega",
            "type": "select",
            "choices": [
                {"value": "Padrão: 5 dias úteis de Produção + tempo de FRETE*", "label": "Padrão: 5 dias úteis de Produção + tempo de FRETE*"}
            ]
        }
    ]
}

def salvar_config(slug, config):
    output_file = os.path.join('..', 'resources', 'data', 'products', f'{slug}.json')
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(config, f, indent=2, ensure_ascii=False)
    print(f"Config criado: {output_file}")

if __name__ == "__main__":
    salvar_config('impressao-de-panfleto', PANFLETO_CONFIG)
    print("Config do panfleto atualizado!")

