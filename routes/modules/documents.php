<?php

use App\Http\Controllers\Clients\DocumentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Document Management
Route::get('documents', [DocumentController::class, 'index'])->name('documents.index')->middleware('can:view-documents');
Route::get('documents/upload', [DocumentController::class, 'create'])->name('documents.upload')->middleware('can:create-documents');
Route::post('documents', [DocumentController::class, 'store'])->name('documents.store')->middleware('can:create-documents');
Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit')->middleware('can:edit-documents');
// POST + _method=put so file uploads work (browsers cannot send multipart PUT).
Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update')->middleware('can:edit-documents');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->middleware('can:delete-documents');
// Download a library document from the public disk (old index downloadDocument action).
Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file')->middleware('can:view-documents');

// Download client document
Route::get('documents/{document}/download', function (\App\Models\Document $document) {
    if (! Storage::disk('local')->exists($document->file_path)) {
        abort(404, 'File not found');
    }

    return Storage::disk('local')->download($document->file_path, $document->file_name);
})->name('documents.download')->middleware('can:view-documents');
