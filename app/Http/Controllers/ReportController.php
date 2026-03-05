<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Services\BrevoService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\SystemAlert;
use App\Models\Dossier;
use App\Services\GroqService;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation parfaitement alignée sur l'objet envoyé par Vue.js
        $validated = $request->validate([
            'dossier_id'  => 'required|exists:dossiers,id',
            'type'        => 'required|in:closing,legal_meeting,phone_call,court_hearing',
            'report_date' => 'required|date',
            'content'     => 'required|array',         // Laravel vérifie que content est bien un objet/tableau
            'content.body'=> 'required|string',        // Laravel vérifie la présence de la clé 'body'
            'status'      => 'required|in:draft,finalized',
        ]);

        // 2. On ajoute automatiquement l'auteur (l'utilisateur connecté)
        $validated['author_id'] = auth()->id();

        // 3. Création propre (Laravel va convertir automatiquement le tableau 'content' en JSON)
        // ⚠️ Prérequis : dans le modèle Report.php, tu dois avoir : protected $casts = ['content' => 'array'];
        $report = Report::create($validated);

        // 4. Gestion des notifications (La Cloche)
        $dossier = \App\Models\Dossier::with('lawyer')->find($validated['dossier_id']);
        $authorName = auth()->user()->name;

        // Cas A : L'auteur n'est PAS l'avocat du dossier -> on notifie l'avocat
        if ($dossier->lawyer_id && $dossier->lawyer_id !== auth()->id()) {
            $dossier->lawyer->notify(new SystemAlert(
                "Nouveau compte rendu",
                "Une nouvelle note a été ajoutée au dossier {$dossier->ref_number} par {$authorName}.",
                route('dossiers.show', $dossier->id)
            ));
        }

        // Cas B : L'auteur EST l'avocat du dossier -> on notifie les admins
        if (auth()->id() === $dossier->lawyer_id) {
            $admins = \App\Models\User::role('admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new SystemAlert(
                    "Compte rendu Avocat",
                    "Me {$authorName} a rédigé un CR sur le dossier {$dossier->ref_number}.",
                    route('dossiers.show', $dossier->id)
                ));
            }
        }

        return redirect()->back()->with('success', 'Compte rendu sauvegardé avec succès.');
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
    public function generateAi(Request $request, GroqService $groq)
    {
        $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'type' => 'required|string',
            'notes' => 'required|string|max:2000',
        ]);

        $dossier = Dossier::findOrFail($request->dossier_id);
        $context = "Réf: {$dossier->ref_number} - Objet: {$dossier->subject}";

        try {
            $generatedText = $groq->generateReport($request->type, $request->notes, $context);
            
            return response()->json([
                'success' => true,
                'content' => $generatedText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération avec l\'IA.'
            ], 500);
        }
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