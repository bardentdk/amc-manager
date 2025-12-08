<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistiques Globales (KPIs)
        $stats = [
            'total_clients' => Client::count(),
            
            // Dossiers actifs (non clôturés)
            'active_dossiers' => Dossier::where('status', '!=', 'closed')->count(),
            
            // Finances : Encaissé ce mois-ci
            'revenue_month' => Payment::where('status', 'paid')
                ->whereMonth('payment_date', Carbon::now()->month)
                ->whereYear('payment_date', Carbon::now()->year)
                ->sum('amount'),
            
            // Finances : En attente (global)
            'pending_payments' => Payment::where('status', 'pending')->sum('amount'),
        ];

        // 2. Prochains Rendez-vous (Les 5 prochains à venir)
        $upcomingAppointments = Appointment::with(['dossier.client'])
            ->where('start_time', '>=', Carbon::now())
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get()
            ->map(function ($apt) {
                return [
                    'id' => $apt->id,
                    'title' => $apt->title,
                    'start_time' => $apt->start_time,
                    'type' => $apt->type,
                    // Si le RDV est lié à un dossier, on affiche le client, sinon "Divers"
                    'client_name' => $apt->dossier ? $apt->dossier->client->name : ($apt->client ? $apt->client->name : 'N/A'),
                ];
            });

        // 3. Dossiers Récents (Les 5 derniers créés ou modifiés)
        $recentDossiers = Dossier::with('client')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'ref' => $d->ref_number,
                    'subject' => $d->subject,
                    'client_name' => $d->client->name,
                    'status' => $d->status,
                    'updated_at' => $d->updated_at,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'upcomingAppointments' => $upcomingAppointments,
            'recentDossiers' => $recentDossiers,
        ]);
    }
}