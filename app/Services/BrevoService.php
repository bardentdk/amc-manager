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
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $attachmentContent, $attachmentName)
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
}