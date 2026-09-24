$ssh = "$env:WINDIR\System32\OpenSSH\ssh.exe"
$key = "C:\Users\USER\.ssh\crm_live_db_tunnel"

while ($true) {
    & $ssh -i $key -N `
        -L "13306:127.0.0.1:3306" `
        -o "BatchMode=yes" `
        -o "ExitOnForwardFailure=yes" `
        -o "ServerAliveInterval=30" `
        -o "ServerAliveCountMax=3" `
        root@66.116.234.170

    Start-Sleep -Seconds 10
}
