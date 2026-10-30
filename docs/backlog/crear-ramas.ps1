# Crea las 48 ramas feature desde main y las sube a GitHub.
# Uso (en la carpeta del repo, con main actualizado):
#   powershell -ExecutionPolicy Bypass -File .\crear-ramas.ps1
# Si una rama ya existe, la saltea.

$ramas = Get-Content "$PSScriptRoot\ramas.txt" | Where-Object { $_.Trim() -ne "" }

git checkout main
git pull

foreach ($rama in $ramas) {
    git show-ref --verify --quiet "refs/heads/$rama"
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Ya existe: $rama" -ForegroundColor Yellow
        continue
    }
    git branch $rama main
    git push -u origin $rama
    Write-Host "Creada: $rama" -ForegroundColor Green
}

git checkout main
