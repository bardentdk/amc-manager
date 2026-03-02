<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\BrevoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'app:send-appointment-reminders';
    protected $description = 'Envoie les rappels de rendez-vous aux clients (J-1) et aux avocats (Jour J)';

    public function handle(BrevoService $brevo)
    {
        $this->info('Démarrage de l\'envoi des rappels...');

        // 1. Rappels CLIENTS (Pour les RDV de demain)
        $tomorrow = Carbon::tomorrow();
        $appointmentsTomorrow = Appointment::with(['dossier.client'])
            ->whereDate('start_time', $tomorrow)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($appointmentsTomorrow as $apt) {
            $client = $apt->dossier?->client;
            if ($client && $client->email) {
                try {
                    $brevo->sendAppointmentNotification($client->email, $client->name, $apt, 'client_reminder');
                    $this->info("Rappel Client envoyé à : {$client->email}");
                } catch (\Exception $e) {
                    Log::error("Échec rappel client {$client->id} : " . $e->getMessage());
                }
            }
        }

        // 2. Rappels AVOCATS (Pour les RDV d'aujourd'hui)
        $today = Carbon::today();
        $appointmentsToday = Appointment::with(['dossier.lawyer'])
            ->whereDate('start_time', $today)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($appointmentsToday as $apt) {
            $lawyer = $apt->dossier?->lawyer;
            if ($lawyer && $lawyer->email) {
                try {
                    $brevo->sendAppointmentNotification($lawyer->email, $lawyer->name, $apt, 'lawyer_reminder');
                    $this->info("Rappel Avocat envoyé à : {$lawyer->email}");
                } catch (\Exception $e) {
                    Log::error("Échec rappel avocat {$lawyer->id} : " . $e->getMessage());
                }
            }
        }

        $this->info('Terminé avec succès !');
    }
}