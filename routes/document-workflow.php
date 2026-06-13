<?php

use App\Http\Controllers\Documents\DocumentApprovalController;
use App\Http\Controllers\Documents\DocumentAuditController;
use App\Http\Controllers\Documents\DocumentCommentController;
use App\Http\Controllers\Documents\DocumentFileController;
use App\Http\Controllers\Documents\DocumentRecipientController;
use App\Http\Controllers\Documents\DocumentSignatureController;
use App\Http\Controllers\Documents\DocumentTemplateController;
use App\Http\Controllers\Documents\DocumentWorkflowController;
use App\Http\Controllers\Documents\PublicDocumentApprovalController;
use App\Http\Controllers\Documents\PublicDocumentController;
use App\Http\Controllers\Documents\PublicDocumentSignatureController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth', 'prefix' => 'account'], function () {
    Route::post('documents/apply-quick-action', [DocumentWorkflowController::class, 'applyQuickAction'])->name('documents.apply_quick_action');
    Route::post('documents/{document}/send', [DocumentWorkflowController::class, 'send'])->name('documents.send');
    Route::post('documents/{document}/cancel', [DocumentWorkflowController::class, 'cancel'])->name('documents.cancel');
    Route::get('documents/{document}/pdf', [DocumentWorkflowController::class, 'downloadPdf'])->name('documents.pdf');
    Route::post('documents/{document}/pdf/regenerate', [DocumentWorkflowController::class, 'regeneratePdf'])->name('documents.pdf_regenerate');
    Route::get('documents/{document}/timeline', [DocumentWorkflowController::class, 'timeline'])->name('documents.timeline');
    Route::resource('documents', DocumentWorkflowController::class);

    Route::post('document-recipients/reorder', [DocumentRecipientController::class, 'reorder'])->name('document-recipients.reorder');
    Route::resource('document-recipients', DocumentRecipientController::class)->only(['store', 'update', 'destroy']);

    Route::post('document-approvals/{document}/approve', [DocumentApprovalController::class, 'approve'])->name('document-approvals.approve');
    Route::post('document-approvals/{document}/reject', [DocumentApprovalController::class, 'reject'])->name('document-approvals.reject');
    Route::post('document-approvals/{document}/return', [DocumentApprovalController::class, 'returnForEdit'])->name('document-approvals.return');

    Route::get('document-signatures/{document}/form', [DocumentSignatureController::class, 'showSignForm'])->name('document-signatures.form');
    Route::post('document-signatures/{document}', [DocumentSignatureController::class, 'storeSignature'])->name('document-signatures.store');
    Route::get('document-signatures/{document}/download', [DocumentSignatureController::class, 'downloadSignedPdf'])->name('document-signatures.download');

    Route::resource('document-files', DocumentFileController::class)->only(['store', 'destroy']);
    Route::get('document-files/download/{id}', [DocumentFileController::class, 'download'])->name('document-files.download');

    Route::resource('document-comments', DocumentCommentController::class)->only(['store', 'update', 'destroy']);
    Route::get('document-audit/{document}', [DocumentAuditController::class, 'index'])->name('document-audit.index');

    Route::get('document-templates/{template}/preview', [DocumentTemplateController::class, 'preview'])->name('document-templates.preview');
    Route::post('document-templates/{template}/duplicate', [DocumentTemplateController::class, 'duplicate'])->name('document-templates.duplicate');
    Route::resource('document-templates', DocumentTemplateController::class);
});

Route::get('document/public/{hash}', [PublicDocumentController::class, 'show'])->name('public.documents.show');
Route::get('document/public/{hash}/download', [PublicDocumentController::class, 'download'])->name('public.documents.download');
Route::post('document/public/{hash}/approve', [PublicDocumentApprovalController::class, 'approve'])->name('public.documents.approve');
Route::post('document/public/{hash}/reject', [PublicDocumentApprovalController::class, 'reject'])->name('public.documents.reject');
Route::post('document/public/{hash}/sign', [PublicDocumentSignatureController::class, 'sign'])->name('public.documents.sign');
