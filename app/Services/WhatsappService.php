<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = 'Ogo5DapE+qKn7IGEUpQSN2zQVqHeuFl8FgBsoOtxG40o+NwgASIbU+Xi';
        $this->baseUrl = 'http://84.247.174.15:8081';
    }

    public function isWhatsAppNumber($phone)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apikey' => $this->apiKey
        ])->post("{$this->baseUrl}/chat/whatsappNumbers/5564999425822", [
            'numbers' => [$phone]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return isset($data[0]['exists']) && $data[0]['exists'];
        }

        return false;
    }

    public function fetchInstance($instanceName)
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey
        ])->get("{$this->baseUrl}/instance/fetchInstances", [
            'instanceName' => $instanceName
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function connectInstance($instanceName)
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey
        ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function createInstance($instanceName, $number)
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey
        ])->post("{$this->baseUrl}/instance/create", [
            "instanceName" => $instanceName,
            "token" => "",
            "qrcode" => true,
            "number" => $number,
            "webhook" => "http://84.247.174.15:5678/webhook/15cea603-1568-4e01-bb9a-c1d2e7a04093",
            "webhook_by_events" => false,
            "webhook_base64" => true,
            "events" => [
                "MESSAGES_UPSERT"
            ]
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function logoutInstance($instanceName)
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey
        ])->delete("{$this->baseUrl}/instance/logout/{$instanceName}");

        return $response->successful();
    }
}