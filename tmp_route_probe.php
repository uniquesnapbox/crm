<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$paths=['/account/lead-contact','/account/invoices','/account/payments'];
foreach($paths as $path){
  $req=Illuminate\Http\Request::create($path,'GET');
  $res=$kernel->handle($req);
  echo $path.' => '.$res->getStatusCode().' loc='.($res->headers->get('Location')??'')."\n";
  $kernel->terminate($req,$res);
}
