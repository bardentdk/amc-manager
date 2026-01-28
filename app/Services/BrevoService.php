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
    protected $apiKey;

    public function __construct()
    {
        // On récupère la clé via la config pour supporter le cache de Forge
        $this->apiKey = config('services.brevo.key');
        
        if (!$this->apiKey) {
            Log::error("Clé API Brevo manquante dans la configuration (services.brevo.key). Vérifiez config/services.php");
        }

        // Configuration du client API
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $this->apiInstance = new TransactionalEmailsApi(new Client(['verify' => false]), $config);  
    }

    /**
     * Envoie un email transactionnel simple ou avec pièce jointe
     * Note: Les arguments attachment sont optionnels (= null)
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $attachmentContent = null, $attachmentName = null)
    {
        $senderEmail = config('services.brevo.sender_email');
        $senderName = config('services.brevo.sender_name', 'NEXA App');

        $sendSmtpEmail = new SendSmtpEmail();
        $sender = new SendSmtpEmailSender([
            'name' => $senderName, 
            'email' => $senderEmail
        ]);
        $sendSmtpEmail->setSender($sender);

        $to = [new SendSmtpEmailTo(['email' => $toEmail, 'name' => $toName])];
        $sendSmtpEmail->setTo($to);

        $sendSmtpEmail->setSubject($subject);
        $sendSmtpEmail->setHtmlContent($htmlContent);

        // Ajout de la pièce jointe SEULEMENT si elle existe
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

        // Appel sans pièce jointe (les arguments nulls par défaut seront utilisés)
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }
}