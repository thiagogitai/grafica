Param(
    [switch]$Rebuild
)

Write-Host "Iniciando ambiente Docker (Windows/PowerShell)..." -ForegroundColor Cyan

function Invoke-DockerCompose {
    param([string]$Args)
    $cmd = "docker compose $Args"
    Write-Host "> $cmd" -ForegroundColor DarkGray
    iex $cmd
}

try {
    Invoke-DockerCompose "pull"
} catch {
    Write-Host "Aviso: docker compose pull falhou (seguindo)." -ForegroundColor Yellow
}

if ($Rebuild) {
    Invoke-DockerCompose "build --no-cache"
} else {
    Invoke-DockerCompose "build"
}

Invoke-DockerCompose "up -d"

# Preparar app Laravel dentro do container
$prep = @(
    'set -e',
    '[ -f .env ] || cp .env.example .env',
    'php artisan key:generate --force',
    'php artisan migrate --force || true',
    'php artisan storage:link || true',
    'php artisan optimize:clear || true'
) -join ' && '

Write-Host "Configurando Laravel dentro do container..." -ForegroundColor Cyan
Invoke-DockerCompose "exec -T app sh -lc \"$prep\""

# Build de assets (se projeto usa Vite)
Write-Host "Executando build de assets (Node)..." -ForegroundColor Cyan
try {
    Invoke-DockerCompose "exec -T node sh -lc 'npm ci && npm run build'"
} catch {
    Write-Host "Aviso: build de assets falhou (verifique logs)." -ForegroundColor Yellow
}

Write-Host "Ambiente no ar: http://localhost:8097" -ForegroundColor Green

