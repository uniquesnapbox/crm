param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$MySqlPath = 'C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe'
)

$ErrorActionPreference = 'Stop'
$settings = @{}
Get-Content -LiteralPath (Join-Path $ProjectRoot '.env') | ForEach-Object {
    if ($_ -match '^\s*(APP_ENV|DB_HOST|DB_PORT)\s*=\s*(.*?)\s*$') {
        $settings[$matches[1]] = $matches[2].Trim('"', "'")
    }
}

if ($settings['APP_ENV'] -ne 'local' -or $settings['DB_HOST'] -notin @('127.0.0.1', 'localhost')) {
    return
}

$databasePort = 3306
if ($settings['DB_PORT']) {
    $databasePort = [int]$settings['DB_PORT']
}

function Test-DatabasePort {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $connection = $client.ConnectAsync('127.0.0.1', $databasePort)
        return ($connection.Wait(500) -and $client.Connected)
    }
    catch {
        return $false
    }
    finally {
        $client.Dispose()
    }
}

if (Test-DatabasePort) {
    return
}

$dataDirectory = Join-Path (Split-Path $ProjectRoot -Parent) 'mysql-local-data'
if (-not (Test-Path -LiteralPath (Join-Path $dataDirectory 'mysql.ibd'))) {
    throw "Existing CRM MySQL data not found in $dataDirectory."
}
if (-not (Test-Path -LiteralPath $MySqlPath)) {
    throw "MySQL executable not found: $MySqlPath"
}

$baseDirectory = Split-Path (Split-Path $MySqlPath -Parent) -Parent
$errorLog = Join-Path $dataDirectory "crm-mysql-$databasePort.err"
$arguments = @(
    '--no-defaults',
    ('--basedir="{0}"' -f $baseDirectory),
    ('--datadir="{0}"' -f $dataDirectory),
    "--port=$databasePort",
    '--bind-address=127.0.0.1',
    '--mysqlx=0',
    ('--log-error="{0}"' -f $errorLog),
    ('--pid-file="{0}"' -f (Join-Path $dataDirectory "crm-mysql-$databasePort.pid"))
)
$databaseProcess = Start-Process -FilePath $MySqlPath -ArgumentList $arguments -WorkingDirectory $dataDirectory -WindowStyle Hidden -PassThru
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    if (Test-DatabasePort) {
        Write-Host "CRM MySQL ready on 127.0.0.1:$databasePort."
        return
    }
    if ($databaseProcess.HasExited) {
        throw "CRM MySQL exited with code $($databaseProcess.ExitCode). See $errorLog."
    }
    Start-Sleep -Seconds 1
}
throw "CRM MySQL did not become ready within 30 seconds. See $errorLog."
