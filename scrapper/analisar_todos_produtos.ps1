# Script PowerShell para analisar todos os produtos
$produtos = @(
    "https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila",
    "https://www.lojagraficaeskenazi.com.br/product/impressao-online-de-livretos-personalizados",
    "https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista",
    "https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide",
    "https://www.lojagraficaeskenazi.com.br/product/impressao-de-jornal-de-bairro",
    "https://www.lojagraficaeskenazi.com.br/product/impressao-de-guia-de-bairro"
)

Set-Location $PSScriptRoot

foreach ($url in $produtos) {
    Write-Host "`n========================================" -ForegroundColor Cyan
    Write-Host "Analisando: $url" -ForegroundColor Cyan
    Write-Host "========================================`n" -ForegroundColor Cyan
    python analisar_produto.py $url
    Start-Sleep -Seconds 3
}

Write-Host "`nAnalise concluida! Verifique os arquivos *_mapeamento.json" -ForegroundColor Green

