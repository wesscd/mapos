<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** Private, authenticated transport. The browser never receives the gateway token. */
class Whatsapp_gateway
{
    public function request($method, $path, ?array $payload = null)
    {
        $file = $_ENV['WHATSAPP_TOKEN_FILE'] ?? '/run/secrets/whatsapp_token';
        $token = is_readable($file) ? trim(file_get_contents($file)) : '';
        if (strlen($token) < 32) {
            return ['code' => 503, 'data' => ['error' => 'Serviço WhatsApp ainda não configurado.']];
        }
        $curl = curl_init('http://whatsapp:3000' . $path);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
        }
        $body = curl_exec($curl);
        $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return ['code' => $code, 'data' => json_decode((string) $body, true) ?: []];
    }
}
