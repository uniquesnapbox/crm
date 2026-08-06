<?php

namespace App\Http\Controllers;

class DisabledFeatureController extends Controller
{
    public function notFound()
    {
        abort(404);
    }
}

