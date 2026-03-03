<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Notifications\SystemAlert;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['dossier.client']);

        // 1. Filtrage par Type (Tout / À venir / Passés)
        $filter = $request->input('filter', 'all'); // 'all' par défaut

        if ($filter === 'upcoming') {
            $query->where('start_time', '>=', Carbon::now())
                  ->orderBy('start_time', 'asc'); // Le plus proche d'abord
        } elseif ($filter === 'past') {
            $query->where('start_time', '<', Carbon::now())
                  ->orderBy('start_time', 'desc'); // Le plus récent d'abord
        } else {
            // 'all'
            $query->orderBy('start_time', 'desc');
        }

        // 2. Recherche Textuelle (Titre ou Lieu)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Appointments/Index', [
            'appointments' => $query->paginate(20)->withQueryString(),
            // On renvoie les filtres actuels au frontend pour garder l'état actif
            'filters' => $request->only(['filter', 'search']),
        ]);
    }
    public function store(Request $request, BrevoService $brevo)
    {
        $validated = $request->validate([
            'dossier_id' => 'nullable|exists:dossiers,id',
            'client_id' => 'nullable|exists:clients,id', // Ajouté au cas où tu l'envoies directement
            'title' => 'required|string|max:255',
            'type' => 'required|in:legal,closing,phone,other',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        // Si lié à un dossier, on récupère le client lié à ce dossier automatiquement
        if ($request->dossier_id) {
            $dossier = \App\Models\Dossier::find($request->dossier_id);
            if ($dossier) {
                $validated['client_id'] = $dossier->client_id;
            }
        }

        // CORRECTION 1 : On utilise $validated au lieu de $request->all()
        // Cela garantit que le 'client_id' qu'on vient d'injecter au-dessus est bien sauvegardé !
        $appointment = Appointment::create($validated);

        // On charge le dossier (et son client), ainsi que la relation 'client' directe
        $appointment->load(['dossier.client', 'client']);

        // CORRECTION 2 : L'opérateur "?->" (Null Safe)
        // PHP va chercher le client via le dossier. Si le dossier est null, il ne plante pas 
        // et passe à la suite (??) pour chercher s'il y a un client direct.
        $client = $appointment->dossier?->client ?? $appointment->client;
        
        if ($client && $client->email) {
            try {
                $brevo->sendAppointmentNotification(
                    $client->email, 
                    $client->name, 
                    $appointment, 
                    'confirmation'
                );
            } catch (\Exception $e) {
                // On log l'erreur pour ne pas bloquer l'interface de l'assistant(e)
                \Illuminate\Support\Facades\Log::error("Erreur d'envoi de confirmation de RDV: " . $e->getMessage());
            }
        }
        // 🔔 NOTIFICATION CLOCHE : On prévient l'avocat du dossier si ce n'est pas lui qui a créé le RDV
        $lawyer = $appointment->dossier?->lawyer;
        
        if ($lawyer && $lawyer->id !== auth()->id()) {
            $dateFormatee = \Carbon\Carbon::parse($appointment->start_time)->format('d/m/Y à H:i');
            
            $lawyer->notify(new SystemAlert(
                "Nouveau Rendez-vous",
                "Un RDV a été planifié le {$dateFormatee} pour le dossier {$appointment->dossier->ref_number}.",
                route('dossiers.show', $appointment->dossier_id) // Redirige vers le dossier
            ));
        }
        return redirect()->back()->with('success', 'Rendez-vous créé et email de confirmation envoyé (le cas échéant).');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:legal,closing,phone,other',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,cancelled,postponed',
        ]);

        $appointment->update($validated);

        return Redirect::back()->with('success', 'Rendez-vous mis à jour.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return Redirect::back()->with('success', 'Rendez-vous supprimé.');
    }

    
}