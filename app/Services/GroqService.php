<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.groq.com/openai/v1';

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
    }

    /**
     * Génère un compte rendu basé sur des notes brutes
     */
    public function generateReport($type, $notes, $dossierContext)
    {
        if (!$this->apiKey) {
            throw new \Exception("Clé API Groq non configurée.");
        }

        // On traduit le type de compte rendu pour le prompt
        $typeLabel = match($type) {
            'legal_meeting' => 'Rendez-vous client / avocat',
            'court_hearing' => 'Audience au tribunal',
            'closing' => 'Closing / Signature',
            'phone_call' => 'Entretien téléphonique',
            default => 'Compte rendu juridique',
        };

        $prompt = "Voici des notes brutes concernant un(e) $typeLabel pour le dossier suivant : $dossierContext.\n\nNotes brutes : $notes\n\nÀ partir de ces notes, rédige un compte rendu propre, formel et structuré (avec des paragraphes ou des puces si nécessaire). N'invente aucune information qui ne figure pas dans les notes. Ne mets pas de formule de politesse finale ni d'introduction type 'Voici le compte rendu', donne uniquement le contenu brut du rapport.";

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile', // Excellent modèle, rapide et performant en français
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un assistant juridique expert travaillant dans un cabinet d'avocats. Ton rôle est de transformer des notes rapides en rapports professionnels et clairs."
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3, // Température basse pour rester factuel et précis
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::error("Erreur API Groq : " . $response->body());
            throw new \Exception("Erreur lors de la génération IA.");

        } catch (\Exception $e) {
            Log::error("Exception Groq : " . $e->getMessage());
            throw $e;
        }
    }
}