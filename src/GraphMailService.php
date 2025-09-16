<?php

namespace Mail\MsGraphMail;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class GraphMailService
{
    protected string $tenantId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $fromAddress;

    public function __construct()
    {
        $this->tenantId = config('msgraph.tenant_id');
        $this->clientId = config('msgraph.client_id');
        $this->clientSecret = config('msgraph.secret_id');
        $this->fromAddress = config('msgraph.from_address');
    }

    public function sendMail(array $toRecipients, array $ccRecipients , string $subject, string $body, string $contentType = 'HTML', array $attachment_paths = []): bool
    {
        // Token holen
        $tokenResponse = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
        ]);

        $accessToken = $tokenResponse->json('access_token');
        if (!$accessToken) {
            throw new \Exception('Konnte Access Token nicht abrufen.');
        }

        $attachments = [];
        if (!empty($attachment_paths)) {
            foreach ($attachment_paths as $path) {
                if (!file_exists($path)) {
                    throw new \Exception('Datei nicht gefunden: ' . $path);
                }
                $attachments[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => basename($path),
                    'contentType' => mime_content_type($path),
                    'contentBytes' => base64_encode(file_get_contents($path))
                ];
            }
        }

        // ToRecipients vorbereiten
        $toRecipients = collect((array) $toRecipients)->map(fn($addr) => [
            'emailAddress' => ['address' => $addr],
        ])->toArray();

        // CCRecipients vorbereiten (nur, wenn vorhanden)
        $ccRecipients = collect($ccRecipients)->map(fn($addr) => [
            'emailAddress' => ['address' => $addr],
        ])->toArray();

        $message = [
            'subject' => $subject,
            'body' => [
                'contentType' => $contentType,
                'content' => $body,
            ],
            'toRecipients' => $toRecipients,
            'attachments' => $attachments,
        ];

        // Nur hinzufügen, wenn auch CCs existieren
        if (!empty($ccRecipients)) {
            $message['ccRecipients'] = $ccRecipients;
        }

        // Graph API senden
        $response = Http::withToken($accessToken)->post(
            "https://graph.microsoft.com/v1.0/users/{$this->fromAddress}/sendMail",
            [
                'message' => $message,
                'saveToSentItems' => true,
            ]
        );

        return $response->successful();
    }

    private function bodyHtml(string $recipientName): string
    {
        return <<<HTML
<p><strong>Neues Beratungsformular eingegangen</strong></p>
<p>Ein Interessent hat soeben das Beratungsformular ausgefüllt und an uns übermittelt.<br>
Bitte prüfen Sie die beigefügte PDF-Zusammenfassung im Anhang und kontaktieren den Interessenten zeitnah.</p>
<hr>
<p style="color:red;"><strong>⚠️ Hinweis:</strong><br>
Diese E-Mail ist ausschließlich für den internen Gebrauch bestimmt und enthält vertrauliche Informationen.</p>
<p>Vielen Dank!<br>
DHS Service Portal</p>
HTML;
    }

}
