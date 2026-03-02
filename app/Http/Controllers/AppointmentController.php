<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

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
            'title' => 'required|string|max:255',
            'type' => 'required|in:legal,closing,phone,other',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Si lié à un dossier, on lie aussi le client automatiquement
        if ($request->dossier_id) {
            $dossier = \App\Models\Dossier::find($request->dossier_id);
            $validated['client_id'] = $dossier->client_id;
        }

        $appointment = Appointment::create($request->all());

        // On charge le dossier et le client pour récupérer l'email
        $appointment->load(['dossier.client']);

        // Envoi de l'email au client si on a son adresse
        $client = $appointment->dossier->client;
        
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
                Log::error("Erreur d'envoi de confirmation de RDV: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Rendez-vous créé et email de confirmation envoyé.');
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