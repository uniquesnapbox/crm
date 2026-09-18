param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$PhpPath = $env:PHP_PATH
)

$ErrorActionPreference = 'Stop'
$logDirectory = Join-Path $ProjectRoot 'storage\logs'
$webLog = Join-Path $logDirectory 'crm-web.log'
$mutexName = 'Local\CRMWebLanServerLauncher'
$createdNew = $false
$mutex = New-Object System.Threading.Mutex($true, $mutexName, ([ref]$createdNew))

if (-not $createdNew) {
    exit 0
}

if (-not (Test-Path $logDirectory)) {
    New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
}

if ([string]::IsNullOrWhiteSpace($PhpPath)) {
    $PhpPath = (Get-Command php -ErrorAction SilentlyContinue).Source
}

if ([string]::IsNullOrWhiteSpace($PhpPath) -or -not (Test-Path $PhpPath)) {
    $PhpPath = 'C:\xampp\php\php.exe'
}

if (-not (Test-Path $PhpPath)) {
    throw "Unable to locate PHP executable. Set PHP_PATH or install PHP in C:\xampp\php\php.exe."
}

Set-Location $ProjectRoot

& (Join-Path $PSScriptRoot 'start-crm-database.ps1') -ProjectRoot $ProjectRoot

while ($true) {
    try {
        $webProcess = Start-Process `
            -FilePath $PhpPath `
            -ArgumentList @('-S', '0.0.0.0:8081', '-t', 'public', 'server.php') `
            -WorkingDirectory $ProjectRoot `
            -WindowStyle Hidden `
            -PassThru

        $webProcess.WaitForExit()
        Add-Content -Path $webLog -Value ("[{0}] CRM web process exited with code {1}. Restarting." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $webProcess.ExitCode)
    }
    catch {
        Add-Content -Path $webLog -Value ("[{0}] CRM web launcher error: {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $_.Exception.Message)
    }

    Start-Sleep -Seconds 5
}
