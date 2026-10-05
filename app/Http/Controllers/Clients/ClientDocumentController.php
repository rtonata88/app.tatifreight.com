<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/clients/documents (documents attached to
 * a client through the documentable morph, stored on the "local" disk).
 */
class ClientDocumentController extends Controller
{
    public function index(Request $request, Client $client): Response
    {
        $documents = Document::with('uploader')
            ->where('documentable_type', Client::class)
            ->where('documentable_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'category' => $document->category,
                'file_name' => $document->file_name,
                'file_type' => $document->file_type,
                'file_size_kb' => round($document->file_size / 1024, 1),
                'created_at' => $document->created_at?->format('Y-m-d'),
                'uploader' => $document->uploader?->name,
            ]);

        return Inertia::render('clients/documents', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'is_active' => (bool) $client->is_active,
            ],
            'documents' => $documents,
            'can' => [
                'create' => $request->user()->can('create-documents'),
                'delete' => $request->user()->can('delete-documents'),
            ],
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'uploadFile' => 'required|file|max:10240', // 10MB max
            'uploadTitle' => 'required|string|max:255',
            'uploadCategory' => 'required|string',
            'uploadDescription' => 'nullable|string',
        ]);

        try {
            $file = $request->file('uploadFile');
            $path = $file->store('documents/clients/'.$client->id, 'local');

            Document::create([
                'documentable_type' => Client::class,
                'documentable_id' => $client->id,
                'title' => $request->input('uploadTitle'),
                'category' => $request->input('uploadCategory'),
                'description' => $request->input('uploadDescription'),
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->extension(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload document: '.$e->getMessage());
        }

        return back()->with('success', 'Document uploaded successfully');
    }

    public function destroy(Client $client, Document $document): RedirectResponse
    {
        abort_unless(
            $document->documentable_type === Client::class && (int) $document->documentable_id === $client->id,
            404
        );

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully');
    }
}
