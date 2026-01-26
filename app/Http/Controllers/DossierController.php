<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class DossierController extends Controller
{
    public function index(Request $request)
    {
        $query = Dossier::with(['client', 'lawyer']);

        // Filtre Recherche (Ref, Sujet, Nom Client)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('ref_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre Statut
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $dossiers = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // On charge la liste des clients pour le formulaire de création (simple pour l'instant)
        $clients = Client::orderBy('name')->select('id', 'name')->get();
        
        // On charge les avocats (users avec rôle lawyer ou admin)
        $lawyers = User::whereIn('role', ['admin', 'lawyer'])->select('id', 'name')->get();

        return Inertia::render('Dossiers/Index', [
            'dossiers' => $dossiers,
            'clients' => $clients,
            'lawyers' => $lawyers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        // Génération de ref si vide (Format: ANNEE-00X)
        if (empty($request->ref_number)) {
            $count = Dossier::whereYear('created_at', date('Y'))->count() + 1;
            $request->merge(['ref_number' => date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT)]);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'lawyer_id' => 'nullable|exists:users,id',
            'type' => 'required|string',
            'status' => 'required|string',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ref_number' => 'required|unique:dossiers,ref_number',
        ]);

        $dossier = Dossier::create($validated);

        return Redirect::route('dossiers.show', $dossier->id)->with('success', 'Dossier ouvert avec succès.');
    }

    public function show(Dossier $dossier)
    {
        $dossier->load([
            'client', 
            'lawyer', 
            'payments' => fn($q) => $q->orderBy('payment_date', 'desc'),
            'appointments' => fn($q) => $q->orderBy('start_time', 'asc'),
            'documents' => fn($q) => $q->orderBy('created_at', 'desc'),
            // Ajout des rapports 👇
            'reports' => fn($q) => $q->with('author')->orderBy('report_date', 'desc') 
        ]);
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Dossier::class)
            ->where('subject_id', $dossier->id)
            ->with('causer') // L'utilisateur qui a fait l'action
            ->orderBy('created_at', 'desc')
            ->get();
        // N'oublie pas d'ajouter la relation reports() dans le modèle Dossier si ce n'est pas fait !

        $clients = Client::select('id', 'name')->orderBy('name')->get();
        $lawyers = User::whereIn('role', ['admin', 'lawyer'])->select('id', 'name')->get();

        return Inertia::render('Dossiers/Show', [
            'dossier' => $dossier,
            'clients' => $clients,
            'lawyers' => $lawyers,
            'activities' => $activities, 
        ]);
    }

    public function update(Request $request, Dossier $dossier)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'lawyer_id' => 'nullable|exists:users,id',
            'type' => 'required|string',
            'status' => 'required|string',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $dossier->update($validated);

        return Redirect::back()->with('success', 'Dossier mis à jour.');
    }

    
}