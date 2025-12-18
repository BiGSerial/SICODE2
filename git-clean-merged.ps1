param(
  [string]$Base = "main",
  [switch]$Remote,
  [switch]$Force,
  [switch]$DryRun = $true
)

function Die($msg) { Write-Host "ERRO: $msg" -ForegroundColor Red; exit 1 }

# Sanity checks
git rev-parse --is-inside-work-tree *> $null
if ($LASTEXITCODE -ne 0) { Die "Não é um repositório git." }

# Atualiza refs
git fetch origin --prune | Out-Null

# Garante que base existe
git show-ref --verify --quiet "refs/heads/$Base"
if ($LASTEXITCODE -ne 0) { Die "Branch base '$Base' não existe localmente." }

$protected = @("main","master","develop",$Base)

$current = (git branch --show-current).Trim()
Write-Host "Base: $Base | Atual: $current | DryRun: $DryRun | Remote: $Remote | Force: $Force"

# Lista branches locais mergeadas na base
$mergedLocal = git branch --merged $Base |
  ForEach-Object { $_.Trim().TrimStart("*").Trim() } |
  Where-Object { $_ -and ($_ -notin $protected) -and ($_ -ne $current) }

if (-not $mergedLocal) {
  Write-Host "Nenhuma branch local mergeada em '$Base' para remover."
} else {
  Write-Host "`nBranches locais mergeadas em '$Base':"
  $mergedLocal | ForEach-Object { Write-Host " - $_" }

  if ($DryRun) {
    Write-Host "`n(DRY-RUN) Não removi nada."
  } else {
    foreach ($b in $mergedLocal) {
      if ($Force) {
        git branch -D $b | Out-Null
      } else {
        git branch -d $b | Out-Null
      }
      Write-Host "Removida local: $b"
    }
  }
}

# Opcional: remover branches remotas mergeadas em origin/Base
if ($Remote) {
  git show-ref --verify --quiet "refs/remotes/origin/$Base"
  if ($LASTEXITCODE -ne 0) { Die "origin/$Base não existe." }

  $mergedRemote = git branch -r --merged "origin/$Base" |
    ForEach-Object { $_.Trim() } |
    Where-Object { $_ -like "origin/*" -and $_ -notlike "origin/HEAD*" } |
    ForEach-Object { $_.Replace("origin/","") } |
    Where-Object { $_ -and ($_ -notin $protected) }

  if (-not $mergedRemote) {
    Write-Host "`nNenhuma branch remota mergeada em 'origin/$Base' para remover."
  } else {
    Write-Host "`nBranches remotas mergeadas em 'origin/$Base':"
    $mergedRemote | ForEach-Object { Write-Host " - origin/$_" }

    if ($DryRun) {
      Write-Host "`n(DRY-RUN) Não removi nada no remoto."
    } else {
      foreach ($b in $mergedRemote) {
        git push origin --delete $b | Out-Null
        Write-Host "Removida remota: origin/$b"
      }
    }
  }
}
