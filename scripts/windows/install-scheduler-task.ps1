param(
    [string]$TaskName = 'CRM Laravel Scheduler',
    [string]$RunAsUser = 'SYSTEM'
)

$launcher = Join-Path $PSScriptRoot 'start-scheduler.ps1'
$ErrorActionPreference = 'Stop'

if (-not (Test-Path $launcher)) {
    throw "Missing scheduler launcher script: $launcher"
}

function Test-Administrator {
    $currentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentIdentity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-InteractiveUser {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    return $identity.Name
}

$isAdministrator = Test-Administrator

if (-not $isAdministrator -and $RunAsUser -ieq 'SYSTEM') {
    $RunAsUser = Get-InteractiveUser
}

$arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "{0}"' -f $launcher
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $arguments
$trigger = if ($RunAsUser -ieq 'SYSTEM' -and $isAdministrator) {
    New-ScheduledTaskTrigger -AtStartup
} else {
    New-ScheduledTaskTrigger -AtLogOn
}

if ($RunAsUser -ieq 'SYSTEM' -and $isAdministrator) {
    $principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
} elseif ($isAdministrator) {
    $principal = New-ScheduledTaskPrincipal -UserId $RunAsUser -LogonType Interactive -RunLevel Highest
} else {
    $principal = New-ScheduledTaskPrincipal -UserId $RunAsUser -LogonType Interactive
}

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -MultipleInstances IgnoreNew `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -StartWhenAvailable

try {
    Register-ScheduledTask `
        -TaskName $TaskName `
        -Action $action `
        -Trigger $trigger `
        -Principal $principal `
        -Settings $settings `
        -Description 'Keeps Laravel schedule:work running continuously for CRM reminders.' `
        -Force -ErrorAction Stop | Out-Null
} catch {
    $startupDirectory = [Environment]::GetFolderPath('Startup')
    $shortcutPath = Join-Path $startupDirectory ($TaskName + '.lnk')
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = 'powershell.exe'
    $shortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "{0}"' -f $launcher
    $shortcut.WorkingDirectory = Split-Path $launcher -Parent
    $shortcut.WindowStyle = 7
    $shortcut.Description = 'Fallback user-login launcher for the CRM Laravel scheduler.'
    $shortcut.Save()

    Write-Warning "Scheduled Task registration was unavailable ($($_.Exception.Message))."
    Write-Host "Installed login-startup fallback: $shortcutPath"
    Write-Host 'Run this script as Administrator later if an AtStartup SYSTEM task is required.'
    exit 0
}

Write-Host "Scheduled task [$TaskName] registered successfully."
Write-Host "Launcher: $launcher"
Write-Host "RunAs: $RunAsUser"
Write-Host "Trigger: $($trigger.ToString())"
