# Guarda como sync_workers_years.ps1 y ejecútalo en la carpeta del proyecto
$start = Get-Date "2004-01-01"
$end   = Get-Date "2025-10-28"

$curr = $start
while ($curr -le $end) {
    $yearEnd = Get-Date -Year $curr.Year -Month 12 -Day 31
    if ($yearEnd -gt $end) { $yearEnd = $end }

    $m = Get-Date -Year $curr.Year -Month 1 -Day 1
    while ($m -le $yearEnd) {
        $monthEnd = (Get-Date -Year $m.Year -Month $m.Month -Day 1).AddMonths(1).AddDays(-1)
        if ($monthEnd -gt $yearEnd) { $monthEnd = $yearEnd }

        $from = $m.ToString("dd/MM/yyyy")
        $to   = $monthEnd.ToString("dd/MM/yyyy")
        Write-Host ">>> Ejecutando: php spark workers:sync $from $to" -ForegroundColor Cyan
        & php spark workers:sync $from $to
        if ($LASTEXITCODE -ne 0) { Write-Host "Error en $($m.ToString('yyyy-MM')). Deteniendo..." -ForegroundColor Red; break }

        $m = $monthEnd.AddDays(1)
    }

    $curr = Get-Date -Year ($curr.Year + 1) -Month 1 -Day 1
}

