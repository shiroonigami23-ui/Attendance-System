param(
    [string]$BaseUrl = "http://localhost/Attendance_System"
)

$ErrorActionPreference = "Stop"

$allowedCodes = @(200, 301, 302, 303, 307, 308, 401, 403)
$fatalPattern = "Fatal error|Parse error|Uncaught|Warning:"
$curl = "curl.exe"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$phpFiles = Get-ChildItem -Path $repoRoot -Recurse -Filter *.php |
    Where-Object {
        $_.FullName -notmatch "\\includes\\" -and
        $_.FullName -notmatch "\\tests\\" -and
        $_.FullName -notmatch "\\scripts\\"
    }

$results = @()

foreach ($file in $phpFiles) {
    $relative = $file.FullName.Substring($repoRoot.Path.Length).TrimStart('\').Replace('\', '/')
    $url = "$BaseUrl/$relative"
    $statusCode = $null
    $fatal = $false
    $errorText = ""

    try {
        $tmp = New-TemporaryFile
        $statusRaw = & $curl -s -o $tmp.FullName -w "%{http_code}" $url
        $statusCode = [int]$statusRaw
        $body = Get-Content $tmp.FullName -Raw -ErrorAction SilentlyContinue
        if ($body -match $fatalPattern) {
            $fatal = $true
        }
        Remove-Item $tmp.FullName -Force -ErrorAction SilentlyContinue
    } catch {
        $errorText = $_.Exception.Message
    }

    $okCode = $statusCode -in $allowedCodes
    $ok = $okCode -and (-not $fatal)

    $results += [PSCustomObject]@{
        File   = $relative
        Status = if ($statusCode) { $statusCode } else { "NO_RESPONSE" }
        Fatal  = $fatal
        Pass   = $ok
        Error  = $errorText
    }
}

$passCount = @($results | Where-Object { $_.Pass -eq $true }).Count
$failures = @($results | Where-Object { $_.Pass -ne $true })

Write-Host "Smoke test complete: $passCount / $($results.Count) passed."

if ($failures.Count -gt 0) {
    Write-Host "`nFailures:"
    $failures | Select-Object File, Status, Fatal, Error | Format-Table -AutoSize
}

if ($failures.Count -gt 0) {
    exit 1
}

exit 0
