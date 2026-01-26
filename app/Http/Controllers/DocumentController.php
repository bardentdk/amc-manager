<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        
        // Stockage dans le dossier privé "documents"
        $path = $file->store('documents');

        Document::create([
            'dossier_id' => $request->dossier_id,
            'user_id' => Auth::id(),
            'name' => $originalName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return Redirect::back()->with('success', 'Document ajouté.');
    }

    public function download(Document $document)
    {
        // Vérification basique de sécurité (à renforcer avec des Policies plus tard)
        if (!Storage::exists($document->path)) {
            abort(404);
        }
        return Storage::download($document->path, $document->name);
    }

    public function preview(Document $document)
    {
        // Pour afficher dans le navigateur (PDF/Images) au lieu de télécharger
        if (!Storage::exists($document->path)) {
            abort(404);
        }
        return Storage::response($document->path);
    }

    public function destroy(Document $document)
    {
        Storage::delete($document->path);
        $document->delete();
        return Redirect::back()->with('success', 'Document supprimé.');
    }
}