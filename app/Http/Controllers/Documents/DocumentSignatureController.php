<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\AccountBaseController;
use Illuminate\Http\Request;

class DocumentSignatureController extends AccountBaseController
{
    public function showSignForm($documentId) {}
    public function storeSignature(Request $request, $documentId) {}
    public function companySignature(Request $request, $documentId) {}
    public function downloadSignedPdf($documentId) {}
}
