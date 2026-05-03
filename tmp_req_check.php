<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$tests = [
    ['GET', '/', []],
    ['GET', '/account/lead-contact', []],
    ['POST', '/api/login', ['email' => 'invalid@example.com', 'password' => 'wrong-pass']],
];

foreach ($tests as [$method, $uri, $data]) {
    $request = Illuminate\Http\Request::create($uri, $method, $data);
    $request->headers->set('Accept', 'application/json');
    $response = $kernel->handle($request);
    echo $method.' '.$uri.' => '.$response->getStatusCode()."\n";
    $location = $response->headers->get('Location');
    if ($location) { echo 'Location: '.$location."\n"; }
    if ($uri === '/api/login') { echo $response->getContent()."\n"; }
    $kernel->terminate($request, $response);
}
