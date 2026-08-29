param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$NodePath = $env:NODE_PATH
)

$ErrorActionPreference = 'Stop'
$serviceRoot = Join-Path $ProjectRoot 'whatsapp-service\whatsapp-service'
$logDirectory = Join-Path $ProjectRoot 'storage\logs'
$serviceLog = Join-Path $logDirectory 'whatsapp-service.log'
$serviceErrorLog = Join-Path $logDirectory 'whatsapp-service-error.log'
$createdNew = $false
$mutex = New-Object System.Threading.Mutex($true, 'Local\CRMWhatsAppBridgeLauncher', ([ref]$createdNew))

if (-not $createdNew) {
    exit 0
}

if (-not (Test-Path (Join-Path $serviceRoot 'src\server.js'))) {
    throw "WhatsApp service entry point not found in $serviceRoot"
}

if (-not (Test-Path $logDirectory)) {
    New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
}

function Resolve-NodeExecutable {
    param([string]$PreferredPath)

    $candidates = @(
        $PreferredPath,
        'node',
        'C:\Program Files\nodejs\node.exe'
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

    throw 'Unable to locate Node.js. Set NODE_PATH or install Node.js.'
}

function Write-ServiceLog {
    param(
        [string]$Path,
        [string]$Message
    )

    Add-Content -Path $Path -Value ('[{0}] {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message)
}

function Test-BridgePort {
    $listener = Get-NetTCPConnection -LocalPort 3100 -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
    return $null -ne $listener
}

$nodeExecutable = Resolve-NodeExecutable -PreferredPath $NodePath
Set-Location $serviceRoot

Write-ServiceLog -Path $serviceLog -Message "Launching WhatsApp bridge with $nodeExecutable"

while ($true) {
    try {
        if (Test-BridgePort) {
            Write-ServiceLog -Path $serviceLog -Message 'Port 3100 is already listening. Monitoring before retry.'
            Start-Sleep -Seconds 15
            continue
        }

        Write-ServiceLog -Path $serviceLog -Message 'Starting Node WhatsApp bridge'
        & $nodeExecutable src\server.js 2>&1 | Tee-Object -FilePath $serviceLog -Append | Out-Null

        $exitCode = $LASTEXITCODE
        Write-ServiceLog -Path $serviceErrorLog -Message "WhatsApp bridge exited with code $exitCode. Restarting in 5 seconds."
    } catch {
        Write-ServiceLog -Path $serviceErrorLog -Message ("WhatsApp bridge loop failed: {0}" -f $_.Exception.Message)
    }

    Start-Sleep -Seconds 5
}
