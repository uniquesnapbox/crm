<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\AccountBaseController;
use Illuminate\Http\Request;

class DocumentApprovalController extends AccountBaseController
{
    public function approve(Request $request, $documentId) {}
    public function reject(Request $request, $documentId) {}
    public function returnForEdit(Request $request, $documentId) {}
}
