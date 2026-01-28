<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailAttachment;
use Brevo\Client\Model\SendSmtpEmailSender;
use Brevo\Client\Model\SendSmtpEmailTo;
use Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class BrevoService
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', config('app.brevo_api_key', env('BREVO_API_KEY')));
        $client = new Client(['verify' => false]);
        // On utilise env() ici pour tester rapidement
        $apiKey = env('BREVO_API_KEY'); 
        
        if (!$apiKey) {
            Log::error("Clé API Brevo manquante dans le .env");
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $this->apiInstance = new TransactionalEmailsApi($client, $config);    
    }

    /**
     * Envoie un email transactionnel simple ou avec pièce jointe
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $attachmentContent = null, $attachmentName = null)
    {
        $sendSmtpEmail = new SendSmtpEmail();
        $sender = new SendSmtpEmailSender([
            'name' => env('BREVO_SENDER_NAME', 'NEXA App'), 
            'email' => env('BREVO_SENDER_EMAIL')
        ]);
        $sendSmtpEmail->setSender($sender);

        $to = [new SendSmtpEmailTo(['email' => $toEmail, 'name' => $toName])];
        $sendSmtpEmail->setTo($to);

        $sendSmtpEmail->setSubject($subject);
        $sendSmtpEmail->setHtmlContent($htmlContent);

        if ($attachmentContent && $attachmentName) {
            $attachment = new SendSmtpEmailAttachment();
            $attachment->setName($attachmentName);
            $attachment->setContent(base64_encode($attachmentContent));
            $sendSmtpEmail->setAttachment([$attachment]);
        }

        try {
            $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);
            return $result;
        } catch (Exception $e) {
            Log::error('Erreur Brevo : ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Envoie les identifiants au nouvel utilisateur
     */
    public function sendWelcomeEmail($toEmail, $toName, $password, $role)
    {
        $subject = "Bienvenue chez NEXA - Vos identifiants de connexion";
        
        // On traduit le rôle pour l'affichage (ex: lawyer -> Avocat)
        $roleDisplay = match($role) {
            'admin' => 'Administrateur',
            'lawyer' => 'Avocat',
            'assistant' => 'Assistant(e)',
            default => $role,
        };

        $htmlContent = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h1 style='color: #4f46e5;'>Bienvenue chez NEXA, $toName !</h1>
                <p>Un compte administrateur vient de vous créer un accès <strong>$roleDisplay</strong> sur la plateforme de gestion du cabinet.</p>
                
                <div style='background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>Vos identifiants de connexion :</strong></p>
                    <ul style='list-style: none; padding-left: 0;'>
                        <li>📧 Email : <strong>$toEmail</strong></li>
                        <li>🔑 Mot de passe temporaire : <strong>$password</strong></li>
                    </ul>
                </div>

                <p>Veuillez vous connecter dès maintenant : <a href='" . config('app.url') . "' style='color: #4f46e5; font-weight: bold;'>Accéder à mon espace</a></p>
                <p><em>Pour votre sécurité, nous vous conseillons de changer ce mot de passe dès votre première connexion.</em></p>
                <br>
                <p>Cordialement,<br>L'équipe technique NEXA.</p>
            </div>
        ";

        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }
}