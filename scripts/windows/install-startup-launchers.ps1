param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'
$startupDirectory = [Environment]::GetFolderPath('Startup')
$shell = New-Object -ComObject WScript.Shell

$launchers = @(
    @{
        Name = 'CRM Laravel Scheduler'
        Script = Join-Path $ProjectRoot 'scripts\windows\start-scheduler.ps1'
    },
    @{
        Name = 'CRM WhatsApp Bridge'
        Script = Join-Path $ProjectRoot 'scripts\windows\start-whatsapp-service.ps1'
    }
)

foreach ($launcher in $launchers) {
    if (-not (Test-Path $launcher.Script)) {
        throw "Missing launcher script: $($launcher.Script)"
    }

    $shortcutPath = Join-Path $startupDirectory ($launcher.Name + '.lnk')
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = 'powershell.exe'
    $shortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "{0}" -ProjectRoot "{1}"' -f $launcher.Script, $ProjectRoot
    $shortcut.WorkingDirectory = $ProjectRoot
    $shortcut.WindowStyle = 7
    $shortcut.Description = "Starts $($launcher.Name) when this Windows user signs in."
    $shortcut.Save()

    Write-Host "Installed startup shortcut: $shortcutPath"
}

