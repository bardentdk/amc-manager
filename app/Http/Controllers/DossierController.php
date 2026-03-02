<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;
use App\Services\BrevoService;

class DossierController extends Controller
{
    public function index(Request $request)
    {
        // On charge les relations nécessaires pour l'affichage du tableau
        // Note: 'lawyer' est la relation dans le modèle Dossier (belongsTo User)
        $query = Dossier::with(['client', 'lawyer']);

        // 1. Filtre Recherche (Ref, Sujet, Nom Client)
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

        // 2. Filtre Statut
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 3. Pagination et Tri
        $dossiers = $query->orderBy('updated_at', 'desc') // Tri par mise à jour récente c'est souvent mieux
            ->paginate(10)
            ->withQueryString();

        // 4. Données pour les formulaires (Modales Création/Edition)
        $clients = Client::orderBy('name')->select('id', 'name')->get();
        
        // --- CORRECTION ICI (Liste des Avocats) ---
        // On utilise le scope de Spatie pour trouver ceux qui ont le RÔLE 'lawyer'
        // Si tu veux aussi les admins dans la liste, tu peux faire : User::role(['admin', 'lawyer'])
        $lawyers = User::role('lawyer')->orderBy('name')->select('id', 'name')->get();

        return Inertia::render('Dossiers/Index', [
            'dossiers' => $dossiers,
            'clients' => $clients,
            'lawyers' => $lawyers, // La liste corrigée est envoyée ici
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    // public function store(Request $request)
    // {
    //     // Génération de ref si vide (Format: ANNEE-00X)
    //     if (empty($request->ref_number)) {
    //         $count = Dossier::whereYear('created_at', date('Y'))->count() + 1;
    //         $request->merge(['ref_number' => date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT)]);
    //     }

    //     $validated = $request->validate([
    //         'client_id' => 'required|exists:clients,id',
    //         'lawyer_id' => 'nullable|exists:users,id',
    //         'type' => 'required|string',
    //         'status' => 'required|string',
    //         'subject' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'ref_number' => 'required|unique:dossiers,ref_number',
    //     ]);

    //     $dossier = Dossier::create($validated);

    //     return Redirect::route('dossiers.show', $dossier->id)->with('success', 'Dossier ouvert avec succès.');
    // }

    public function store(Request $request, BrevoService $brevo)
    {
        $dossier = Dossier::create($request->all());

        // Si on attribue l'avocat dès la création du dossier
        if ($dossier->lawyer_id) {
            $lawyer = \App\Models\User::find($dossier->lawyer_id);
            if ($lawyer && $lawyer->email) {
                try {
                    // Pas de RDV possible à la seconde où on crée le dossier, donc on envoie une liste vide collect([])
                    $brevo->sendLawyerAssignmentNotification($lawyer->email, $lawyer->name, $dossier, collect([]));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erreur email assignation avocat: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Dossier créé.');
    }

    public function show(Dossier $dossier)
    {
        // 1. Chargement de toutes les relations pour les onglets
        $dossier->load([
            'client', 
            'lawyer', // L'avocat référent
            'payments' => fn($q) => $q->orderBy('payment_date', 'desc'),
            'appointments' => fn($q) => $q->orderBy('start_time', 'asc'),
            'documents' => fn($q) => $q->orderBy('created_at', 'desc'), // Pour la GED
            'reports' => fn($q) => $q->with('author')->orderBy('report_date', 'desc') // Pour les Comptes Rendus
        ]);

        // 2. Récupération de l'historique (Timeline)
        // Assure-toi que 'subject_type' en base est bien 'App\Models\Dossier'
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Dossier::class)
            ->where('subject_id', $dossier->id)
            ->with('causer') // L'utilisateur qui a fait l'action
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Données pour le formulaire d'édition (Modale "Modifier")
        $clients = Client::select('id', 'name')->orderBy('name')->get();
        
        // --- CORRECTION ICI AUSSI ---
        $lawyers = User::role('lawyer')->orderBy('name')->select('id', 'name')->get();

        return Inertia::render('Dossiers/Show', [
            'dossier' => $dossier,
            'clients' => $clients,
            'lawyers' => $lawyers, // La liste corrigée
            'activities' => $activities, 
        ]);
    }

    // public function update(Request $request, Dossier $dossier)
    // {
    //     $validated = $request->validate([
    //         'client_id' => 'required|exists:clients,id',
    //         'lawyer_id' => 'nullable|exists:users,id',
    //         'type' => 'required|string',
    //         'status' => 'required|string',
    //         'subject' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //     ]);

    //     $dossier->update($validated);

    //     return Redirect::back()->with('success', 'Dossier mis à jour.');
    // }
    public function update(Request $request, Dossier $dossier, BrevoService $brevo)
    {
        // On mémorise l'ancien avocat pour comparer
        $oldLawyerId = $dossier->lawyer_id;

        $dossier->update($request->all());

        // CONDITION : Si l'avocat a été modifié ET qu'un avocat est bien sélectionné
        if ($request->lawyer_id && $request->lawyer_id != $oldLawyerId) {
            
            $lawyer = \App\Models\User::find($request->lawyer_id);
            
            // On cherche tous les RDV de ce dossier qui sont dans le futur
            $upcomingAppointments = Appointment::where('dossier_id', $dossier->id)
                ->where('start_time', '>=', now())
                ->where('status', '!=', 'cancelled')
                ->orderBy('start_time', 'asc')
                ->get();
                
            if ($lawyer && $lawyer->email) {
                try {
                    $brevo->sendLawyerAssignmentNotification($lawyer->email, $lawyer->name, $dossier, $upcomingAppointments);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erreur email assignation avocat update: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Dossier mis à jour.');
    }

    
}