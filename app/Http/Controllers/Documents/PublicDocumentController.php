<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;

class PublicDocumentController extends Controller
{
    public function show($hash) {}
    public function download($hash) {}
    public function verifyAccess($hash) {}
}
