param(
    [string]$BaseUrl = "http://localhost/Attendance_System",
    [int]$Requests = 10000,
    [int]$Concurrency = 250
)

$ErrorActionPreference = "Stop"
$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$php = "C:\xampp\php\php.exe"

if (-not (Test-Path $php)) {
    throw "PHP CLI not found at $php"
}

Push-Location $repoRoot
try {
    Write-Host "1) PHP lint check..."
    $lintFailures = @()
    Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
        $out = & $php -l $_.FullName 2>&1
        if ($LASTEXITCODE -ne 0 -or ($out -match "Parse error|Fatal error")) {
            $lintFailures += [PSCustomObject]@{ File = $_.FullName; Output = ($out -join "`n") }
        }
    }
    if ($lintFailures.Count -gt 0) {
        $lintFailures | Format-List
        throw "Lint check failed."
    }
    Write-Host "Lint passed."

    Write-Host "`n2) Environment validation..."
    & $php tests\validate_env.php
    if ($LASTEXITCODE -ne 0) {
        throw "Environment validation failed."
    }

    Write-Host "`n3) HTTP smoke verification..."
    & powershell -ExecutionPolicy Bypass -File tests\smoke_verify.ps1 -BaseUrl $BaseUrl
    if ($LASTEXITCODE -ne 0) {
        throw "Smoke verification failed."
    }

    Write-Host "`n4) 10k load test..."
    & $php tests\load_test_10k.php --requests=$Requests --concurrency=$Concurrency
    if ($LASTEXITCODE -ne 0) {
        throw "Load test failed."
    }

    Write-Host "`nAll checks passed."
}
finally {
    Pop-Location
}
