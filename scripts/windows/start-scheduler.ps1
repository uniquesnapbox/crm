param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$PhpPath = $env:PHP_PATH
)

Set-Location $ProjectRoot

$logDirectory = Join-Path $ProjectRoot 'storage\logs'
$schedulerLog = Join-Path $logDirectory 'scheduler.log'
$schedulerErrorLog = Join-Path $logDirectory 'scheduler-error.log'
$createdNew = $false
$mutex = New-Object System.Threading.Mutex($true, 'Local\\CRMLaravelSchedulerLauncher', ([ref]$createdNew))

if (-not $createdNew) {
    exit 0
}

if (-not (Test-Path $logDirectory)) {
    New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
}

function Resolve-PhpExecutable {
    param([string]$PreferredPath)

    $candidates = @(
        $PreferredPath,
        'php',
        'C:\xampp\php\php.exe'
    )

    foreach ($candidate in $candidates) {
        if ([string]::IsNullOrWhiteSpace($candidate)) {
            continue
        }

        $resolved = Get-Command $candidate -ErrorAction SilentlyContinue
        if ($resolved -and $resolved.Source) {
            return $resolved.Source
        }

        if (Test-Path $candidate) {
            return (Resolve-Path $candidate).Path
        }
    }

    throw 'Unable to locate a PHP executable. Set PHP_PATH or install PHP in C:\xampp\php\php.exe.'
}

function Write-SchedulerLog {
    param(
        [string]$Path,
        [string]$Message
    )

    Add-Content -Path $Path -Value ('[{0}] {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message)
}

$phpExecutable = Resolve-PhpExecutable -PreferredPath $PhpPath

Write-SchedulerLog -Path $schedulerLog -Message "Launching Laravel scheduler with $phpExecutable"

while ($true) {
    try {
        Write-SchedulerLog -Path $schedulerLog -Message 'Starting `php artisan schedule:work --no-interaction`'

        & $phpExecutable artisan schedule:work --no-interaction 2>&1 | Tee-Object -FilePath $schedulerLog -Append | Out-Null

        $exitCode = $LASTEXITCODE
        Write-SchedulerLog -Path $schedulerErrorLog -Message "Laravel scheduler exited with code $exitCode. Restarting in 5 seconds."
    } catch {
        Write-SchedulerLog -Path $schedulerErrorLog -Message ("Laravel scheduler loop failed: {0}" -f $_.Exception.Message)
    }

    Start-Sleep -Seconds 5
}
