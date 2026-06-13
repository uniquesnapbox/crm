$json = Get-Content 'tmp_route_list.json' -Raw | ConvertFrom-Json
$api = $json | Where-Object { $_.uri -like 'api/*' -or (($_.middleware -join ',') -match 'api') }
"API_ROUTE_COUNT=$($api.Count)"
$named = $api | Where-Object { $_.name -and $_.name -ne '' }
"API_NAMED_COUNT=$($named.Count)"
$rows = @()
foreach($r in $named){
  $name = [string]$r.name
  $esc = [regex]::Escape($name)
  $hits = (rg -n --pcre2 "['\"]$esc['\"]" app routes resources tests config public bootstrap | Measure-Object).Count
  $methodValue = if($r.method -is [System.Array]) { ($r.method -join '|') } else { [string]$r.method }
  $rows += [pscustomobject]@{
    Name = $name
    Uri = [string]$r.uri
    Method = $methodValue
    Hits = $hits
    Action = [string]$r.action
  }
}
$rows | Sort-Object Hits, Name | Select-Object -First 40 | Format-Table -AutoSize
