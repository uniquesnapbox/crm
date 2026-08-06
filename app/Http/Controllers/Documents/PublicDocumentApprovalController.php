<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicDocumentApprovalController extends Controller
{
    public function approve(Request $request, $hash) {}
    public function reject(Request $request, $hash) {}
}
