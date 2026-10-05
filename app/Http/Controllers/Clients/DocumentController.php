<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Document;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Replaces the Volt components livewire/documents/{index,upload,edit}
 * (general document library, files stored on the "public" disk).
 */
class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $category = (string) $request->query('category', '');
        $expiry = (string) $request->query('expiry', '');

        $documents = Document::with(['uploadedBy', 'client', 'vehicle', 'booking'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%'.$search.'%')
                        ->orWhere('file_name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($expiry, function ($q) use ($expiry) {
                if ($expiry === 'expired') {
                    $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
                } elseif ($expiry === 'expiring_soon') {
                    $q->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>=', now())
                        ->whereDate('expiry_date', '<=', now()->addDays(30));
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'file_name' => $document->file_name,
                'file_type' => $document->file_type,
                'description' => $document->description ? Str::limit($document->description, 50) : null,
                'category' => $document->category,
                'related' => $this->related($document),
                'uploaded_by' => $document->uploadedBy?->name,
                'created_at' => $document->created_at?->format('Y-m-d'),
                'expiry_date' => $document->expiry_date?->format('Y-m-d'),
                'is_expired' => (bool) $document->isExpired(),
                'is_expiring_soon' => (bool) $document->isExpiringSoon(),
                'days_left' => $document->expiry_date ? $this->daysLeft($document) : null,
                'file_size_formatted' => $document->file_size_formatted,
            ]);

        $expiringSoon = Document::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();

        $user = $request->user();

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'filters' => ['search' => $search, 'category' => $category, 'expiry' => $expiry],
            'stats' => [
                'total' => Document::count(),
                'expired' => Document::whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->count(),
                'expiring_soon' => $expiringSoon,
                'by_category' => [
                    'contracts' => Document::where('category', 'contract')->count(),
                    'licenses' => Document::where('category', 'license')->count(),
                    'insurance' => Document::where('category', 'insurance')->count(),
                    'receipts' => Document::where('category', 'receipt')->count(),
                ],
            ],
            'can' => [
                'create' => $user->can('create-documents'),
                'edit' => $user->can('edit-documents'),
                'delete' => $user->can('delete-documents'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('documents/upload', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'file_upload' => 'required|file|max:10240',
        ]);

        $file = $request->file('file_upload');

        Document::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'file_path' => $file->store('documents', 'public'),
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            ...$this->links($validated),
            'version' => 1,
        ]);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully!');
    }

    public function edit(Document $document): Response
    {
        $document->load(['uploadedBy', 'versions.uploadedBy']);

        return Inertia::render('documents/edit', [
            ...$this->formOptions(),
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'category' => $document->category,
                'client_id' => $document->client_id,
                'vehicle_id' => $document->vehicle_id,
                'booking_id' => $document->booking_id,
                'expiry_date' => $document->expiry_date?->format('Y-m-d'),
                'description' => $document->description,
                'notes' => $document->notes,
                'version' => (int) $document->version,
                'is_expired' => (bool) $document->isExpired(),
                'is_expiring_soon' => (bool) $document->isExpiringSoon(),
                'file_name' => $document->file_name,
                'file_type' => $document->file_type,
                'file_size_formatted' => $document->file_size_formatted,
                'file_url' => Storage::disk('public')->url($document->file_path),
                'uploaded_by' => $document->uploadedBy?->name,
                'created_at' => $document->created_at?->format('Y-m-d\TH:i:s'),
            ],
            'versions' => $document->versions
                ->sortByDesc('version')
                ->values()
                ->map(fn (Document $version) => [
                    'id' => $version->id,
                    'version' => (int) $version->version,
                    'file_name' => $version->file_name,
                    'created_at' => $version->created_at?->format('Y-m-d\TH:i:s'),
                    'uploaded_by' => $version->uploadedBy?->name,
                    'file_size_formatted' => $version->file_size_formatted,
                    'file_url' => Storage::disk('public')->url($version->file_path),
                ]),
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'file_upload' => 'nullable|file|max:10240',
        ]);

        $data = [
            'title' => $validated['title'],
            'category' => $validated['category'],
            ...$this->links($validated),
        ];

        if ($request->hasFile('file_upload')) {
            $file = $request->file('file_upload');

            if ($request->boolean('createNewVersion')) {
                Document::create([
                    ...$data,
                    'file_path' => $file->store('documents', 'public'),
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                    'version' => $document->version + 1,
                    'parent_document_id' => $document->parent_document_id ?: $document->id,
                ]);

                return redirect()->route('documents.index')->with('success', 'New document version created successfully!');
            }

            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $data['file_path'] = $file->store('documents', 'public');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
        }

        $document->update($data);

        return redirect()->route('documents.index')->with('success', 'Document updated successfully!');
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        if ($request->user()->can('delete-documents')) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return back()->with('success', 'Document deleted successfully');
        }

        return back();
    }

    /**
     * Download a library document from the public disk (the old index's
     * downloadDocument action). Client documents use documents.download.
     */
    public function file(Document $document): BinaryFileResponse|RedirectResponse
    {
        if (Storage::disk('public')->exists($document->file_path)) {
            return response()->download(Storage::disk('public')->path($document->file_path), $document->file_name);
        }

        return back()->with('error', 'File not found');
    }

    /**
     * Validation shared by upload and edit (the file rule differs).
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|in:contract,license,insurance,receipt,invoice,quote,other',
            'client_id' => 'nullable|exists:clients,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function links(array $validated): array
    {
        return [
            'client_id' => ($validated['client_id'] ?? null) ?: null,
            'vehicle_id' => ($validated['vehicle_id'] ?? null) ?: null,
            'booking_id' => ($validated['booking_id'] ?? null) ?: null,
            'expiry_date' => ($validated['expiry_date'] ?? null) ?: null,
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @return array{label: string, type: string}|null
     */
    private function related(Document $document): ?array
    {
        if ($document->client) {
            return ['label' => $document->client->name, 'type' => 'Client'];
        }
        if ($document->vehicle) {
            return ['label' => $document->vehicle->reg_number, 'type' => 'Vehicle'];
        }
        if ($document->booking) {
            return ['label' => $document->booking->booking_number, 'type' => 'Booking'];
        }

        return null;
    }

    /**
     * Whole days until expiry. The old view printed expiry_date->diffInDays(now()),
     * which under Carbon 3 is a signed fraction (e.g. "-9.3 days left").
     */
    private function daysLeft(Document $document): int
    {
        return (int) abs(now()->startOfDay()->diffInDays($document->expiry_date->copy()->startOfDay()));
    }

    /**
     * Select options for the upload and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Client $client) => [
                    'value' => $client->id,
                    'label' => $client->name.($client->company_name ? ' ('.$client->company_name.')' : ''),
                ]),
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get()
                ->map(fn (Vehicle $vehicle) => [
                    'value' => $vehicle->id,
                    'label' => $vehicle->reg_number.' - '.$vehicle->vehicleType?->name,
                ]),
            'bookings' => Booking::with('client')
                ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn (Booking $booking) => [
                    'value' => $booking->id,
                    'label' => $booking->booking_number.' - '.$booking->client?->name,
                ]),
        ];
    }
}
