<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Services\BrevoService;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'type' => 'required|in:closing,legal_meeting,phone_call,court_hearing',
            'report_date' => 'required|date',
            'content_body' => 'required|string', // On valide le texte brut
            'status' => 'required|in:draft,finalized',
        ]);

        // On construit le JSON
        $data = [
            'dossier_id' => $validated['dossier_id'],
            'author_id' => Auth::id(), // L'utilisateur connecté est l'auteur
            'type' => $validated['type'],
            'report_date' => $validated['report_date'],
            'status' => $validated['status'],
            'content' => ['body' => $validated['content_body']], // Structure JSON
        ];

        Report::create($data);

        return Redirect::back()->with('success', 'Compte rendu sauvegardé.');
    }

    public function update(Request $request, Report $report)
    {
        // Petite sécurité : seul l'auteur ou un admin peut modifier (optionnel)
        if ($report->author_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'Action non autorisée.');
        }

        $validated = $request->validate([
            'type' => 'required|in:closing,legal_meeting,phone_call,court_hearing',
            'report_date' => 'required|date',
            'content_body' => 'required|string',
            'status' => 'required|in:draft,finalized',
        ]);

        $report->update([
            'type' => $validated['type'],
            'report_date' => $validated['report_date'],
            'status' => $validated['status'],
            'content' => ['body' => $validated['content_body']],
        ]);

        return Redirect::back()->with('success', 'Compte rendu mis à jour.');
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return Redirect::back()->with('success', 'Compte rendu supprimé.');
    }

    // 1. TÉLÉCHARGER LE PDF
    public function download(Report $report)
    {
        // On charge les relations nécessaires pour la vue PDF
        $report->load(['dossier.client', 'author']);

        $pdf = Pdf::loadView('pdf.report', ['report' => $report]);
        
        $fileName = 'CR_' . $report->dossier->ref_number . '_' . $report->report_date->format('Ymd') . '.pdf';

        return $pdf->download($fileName);
    }

    // 2. ENVOYER PAR EMAIL VIA BREVO
    public function sendEmail(Report $report, BrevoService $brevoService)
    {
        $report->load(['dossier.client', 'author']);

        // 1. Générer le PDF en mémoire (string)
        $pdfContent = Pdf::loadView('pdf.report', ['report' => $report])->output();
        $fileName = 'CompteRendu_' . $report->id . '.pdf';

        // 2. Préparer le contenu de l'email
        $clientEmail = $report->dossier->client->email;
        $clientName = $report->dossier->client->name;
        
        if (!$clientEmail) {
            return Redirect::back()->with('error', 'Ce client n\'a pas d\'adresse email.');
        }

        $subject = "Nouveau document disponible : Dossier " . $report->dossier->ref_number;
        $htmlContent = "
            <h1>Bonjour $clientName,</h1>
            <p>Veuillez trouver ci-joint le compte rendu daté du {$report->report_date->format('d/m/Y')} concernant votre dossier <strong>{$report->dossier->subject}</strong>.</p>
            <p>Cordialement,<br>Votre Cabinet.</p>
        ";

        try {
            $result = $brevoService->sendEmail(
                $clientEmail,
                $clientName,
                $subject,
                $htmlContent,
                $pdfContent,
                $fileName
            );

            // 👇 AJOUTE CECI POUR DEBUGGER 👇
            dd($result); 
            // 👆 Cela va arrêter le code et t'afficher ce que Brevo a répondu

            return Redirect::back()->with('success', 'Email envoyé...');
        } catch (\Exception $e) {
            dd($e->getMessage()); // Affiche l'erreur exacte si ça plante
        }
    }
}