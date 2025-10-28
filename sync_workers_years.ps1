# sync_workers_years.ps1
# Ejecuta workers:sync mes a mes, año por año, con reintentos y log.

param(
  [string]$PhpExe = "php",                     # Cambia a 'C:\xampp\php\php.exe' si no está en PATH
  [datetime]$Start = [datetime]"2025-01-01",   # Inicio por defecto: 01/01/2024
  [datetime]$End   = (Get-Date).Date           # Fin por defecto: hoy
)

$log = Join-Path (Get-Location) "sync_workers.log"
"==== $(Get-Date -Format s) | Inicio: $($Start.ToShortDateString()) → $($End.ToShortDateString()) ====" | Out-File -FilePath $log -Encoding UTF8 -Append

# Regex para detectar errores cuando spark sale con código 0
$errRe = '(?i)(error|transacción fallida|assertionerror|operation timed out|undefined|call to .* function|db error|mysql)'

# Reintentos por mes
$maxRetries   = 3
$retryBackoff = 10  # segundos

$curr = Get-Date -Year $Start.Year -Month 1 -Day 1
while ($curr -le $End) {
  $yearEnd = Get-Date -Year $curr.Year -Month 12 -Day 31
  if ($yearEnd -gt $End) { $yearEnd = $End }

  Write-Host "=== Año $($curr.Year) ===" -ForegroundColor Yellow
  $m = Get-Date -Year $curr.Year -Month 1 -Day 1
  while ($m -le $yearEnd) {
    $monthEnd = (Get-Date -Year $m.Year -Month $m.Month -Day 1).AddMonths(1).AddDays(-1)
    if ($monthEnd -gt $yearEnd) { $monthEnd = $yearEnd }

    $from = $m.ToString("dd/MM/yyyy")
    $to   = $monthEnd.ToString("dd/MM/yyyy")

    $attempt = 0; $ok = $false
    while (-not $ok -and $attempt -lt $maxRetries) {
      $attempt++
      Write-Host ">>> Ejecutando ($attempt/$maxRetries): $PhpExe spark workers:sync $from $to" -ForegroundColor Cyan
      $out = & $PhpExe spark workers:sync $from $to 2>&1
      $code = $LASTEXITCODE

      # Log del output
      "[$(Get-Date -Format s)] $from → $to (try $attempt) | exit:$code" | Out-File -FilePath $log -Append
      $out | Out-File -FilePath $log -Append

      # Evaluar éxito: exitcode 0 y sin texto de error
      if ($code -eq 0 -and ($out -notmatch $errRe)) {
        $ok = $true
        Write-Host "✓ OK $from → $to" -ForegroundColor Green
      } else {
        Write-Host "✗ Falló $from → $to. Reintentando en $retryBackoff s..." -ForegroundColor Red
        Start-Sleep -Seconds $retryBackoff
      }
    }

    if (-not $ok) {
      Write-Host "Error persistente en $($m.ToString('yyyy-MM')). Deteniendo..." -ForegroundColor Red
      "[$(Get-Date -Format s)] ABORT | $from → $to" | Out-File -FilePath $log -Append
      break
    }

    # Pausa corta para no saturar API/DB
    Start-Sleep -Seconds 2
    $m = $monthEnd.AddDays(1)
  }

  $curr = Get-Date -Year ($curr.Year + 1) -Month 1 -Day 1
}

"==== $(Get-Date -Format s) | Fin ====" | Out-File -FilePath $log -Append
Write-Host "Listo. Revisa el log: $log" -ForegroundColor Yellow
