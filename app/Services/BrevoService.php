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

    /**
     * Envoie une notification de rendez-vous (Confirmation, Rappel Client, Rappel Avocat)
     */
    public function sendAppointmentNotification($toEmail, $toName, $appointment, $type = 'confirmation')
    {
        // Formatage de la date pour un affichage lisible
        $date = \Carbon\Carbon::parse($appointment->start_time)->timezone(config('app.timezone'))->format('d/m/Y');
        $time = \Carbon\Carbon::parse($appointment->start_time)->timezone(config('app.timezone'))->format('H:i');
        
        $location = $appointment->location ? "<p>📍 <strong>Lieu / Lien :</strong> {$appointment->location}</p>" : "";

        if ($type === 'confirmation') {
            $subject = "Confirmation de votre rendez-vous - NEXA";
            $title = "Rendez-vous confirmé";
            $message = "Votre rendez-vous a bien été enregistré dans notre agenda.";
        } elseif ($type === 'client_reminder') {
            $subject = "Rappel : Votre rendez-vous de demain - NEXA";
            $title = "Rappel de rendez-vous";
            $message = "Nous vous rappelons que vous avez un rendez-vous prévu demain avec notre cabinet.";
        } elseif ($type === 'lawyer_reminder') {
            $subject = "📅 Vos rendez-vous du jour - NEXA";
            $title = "Rendez-vous d'aujourd'hui";
            $message = "Voici un rappel pour votre rendez-vous de ce jour concernant le dossier <strong>{$appointment->dossier->ref_number}</strong>.";
        }

        $htmlContent = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #4f46e5;'>$title</h2>
                <p>Bonjour $toName,</p>
                <p>$message</p>
                
                <div style='background-color: #f8fafc; border-left: 4px solid #4f46e5; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0 0 10px 0;'>📝 <strong>Motif :</strong> {$appointment->title}</p>
                    <p style='margin: 0 0 10px 0;'>📅 <strong>Date :</strong> Le $date à $time</p>
                    $location
                </div>
                
                <p>En cas d'empêchement, merci de nous prévenir au plus vite.</p>
                <br>
                <p>Cordialement,<br>Le cabinet.</p>
            </div>
        ";

        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    /**
     * Notifie l'avocat qu'un dossier lui a été attribué, avec les RDV à venir
     */
    public function sendLawyerAssignmentNotification($lawyerEmail, $lawyerName, $dossier, $upcomingAppointments)
    {
        $subject = "Nouveau dossier attribué : " . $dossier->ref_number;
        
        $htmlContent = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #4f46e5;'>Nouveau dossier en charge</h2>
                <p>Bonjour Maître {$lawyerName},</p>
                <p>Le dossier <strong>{$dossier->ref_number} - {$dossier->subject}</strong> vient de vous être attribué par l'administration.</p>
        ";
        
        // S'il y a des rendez-vous à venir, on les liste
        if ($upcomingAppointments->count() > 0) {
            $htmlContent .= "
                <div style='background-color: #f8fafc; border-left: 4px solid #4f46e5; padding: 15px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #1e293b; font-size: 16px;'>📅 Rendez-vous à venir pour ce dossier :</h3>
                    <ul style='padding-left: 20px; margin-bottom: 0;'>
            ";
            
            foreach ($upcomingAppointments as $apt) {
                // On s'assure d'être sur le bon fuseau horaire pour l'affichage
                $date = \Carbon\Carbon::parse($apt->start_time)->timezone(config('app.timezone'))->format('d/m/Y à H:i');
                $htmlContent .= "<li><strong>{$apt->title}</strong> - Le {$date}</li>";
            }
            
            $htmlContent .= "</ul></div>";
        } else {
            $htmlContent .= "<p><em>Aucun rendez-vous n'est planifié pour ce dossier pour le moment.</em></p>";
        }
        
        $htmlContent .= "
                <p>Vous pouvez consulter l'intégralité du dossier sur votre espace NEXA.</p>
                <br>
                <p>Cordialement,<br>Le cabinet.</p>
            </div>
        ";

        return $this->sendEmail($lawyerEmail, $lawyerName, $subject, $htmlContent);
    }
}