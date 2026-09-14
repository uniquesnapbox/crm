$ErrorActionPreference = 'Stop'

Get-NetFirewallRule -DisplayName 'CRM Web LAN 8081' -ErrorAction SilentlyContinue |
    Remove-NetFirewallRule -ErrorAction SilentlyContinue

New-NetFirewallRule `
    -DisplayName 'CRM Web LAN 8081' `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 8081 `
    -Profile Any `
    -RemoteAddress LocalSubnet | Out-Null

Write-Output 'CRM Web LAN 8081 firewall rule created for LocalSubnet.'
