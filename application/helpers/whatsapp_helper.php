<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('enviar_mensagem_whatsapp')) {
    function enviar_mensagem_whatsapp($telefone, $mensagem, $configuracoes)
    {
        $whatsapp_enabled = $configuracoes['whatsapp_enabled'];
        $whatsapp_api_url = $configuracoes['whatsapp_api_url'];
        $whatsapp_number = $configuracoes['whatsapp_number'];
        $token = $configuracoes['whatsapp_api_token'];

        if (!$whatsapp_enabled || !$whatsapp_api_url || !$whatsapp_number || !$token) {
            log_message('error', 'Configurações do WhatsApp incompletas.'); // Substituímos addLog por log_message
            return [
                'success' => false,
                'message' => 'Configurações do WhatsApp incompletas.'
            ];
        }

        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone) == 11 && substr($telefone, 0, 2) == '55') {
            $telefone = '+' . $telefone;
        } elseif (strlen($telefone) == 10) {
            $telefone = '+55' . $telefone;
        }

        $data = [
            'number' => $telefone,
            'body' => $mensagem,
            'saveOnTicket' => true,
            'linkPreview' => true
        ];

        $ch = curl_init($whatsapp_api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch); // Captura erro do cURL, se houver
        curl_close($ch);

        if ($http_code == 200) {
            log_message('info', 'Mensagem WhatsApp enviada com sucesso para: ' . $telefone);
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso.'
            ];
        } else {
            log_message('error', 'Erro ao enviar mensagem WhatsApp: ' . $response . ' | cURL Error: ' . $error);
            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $response
            ];
        }
    }
}