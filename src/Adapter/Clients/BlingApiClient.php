<?php

namespace Src\Adapter\Clients;

class BlingApiClient
{
    const AUTHORIZATION_URL = 'https://www.bling.com.br/Api/v3/oauth/authorize';
    const TOKEN_URL = 'https://www.bling.com.br/Api/v3/oauth/token';

    public function authorize($clientId, $state) {
        $callbackUrl = "ngrok.com/callback";
        $params = array(
            'response_type' => 'code',
            'client_id' => $clientId,
            'state' => $state,
            'redirect_uri' => $callbackUrl,
        );
        // Redireciona o usuário para a página de autorização
        header('Location: ' . self::AUTHORIZATION_URL . '?' . http_build_query($params));
        exit();
    }


    public function getToken($code, $clientId, $clientSecret): array {
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code
        );

        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => '1.0',
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
        );

        // Inicializa o cURL
        $ch = curl_init();

        // Define as opções do cURL
        curl_setopt_array($ch, array(
            CURLOPT_URL => self::TOKEN_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => http_build_query($params)
        ));

        // Executa a requisição e obtém a resposta
        $response = curl_exec($ch);

        $error = null;
        // Verifica se ocorreu algum erro
        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }

        // Fecha o cURL
        curl_close($ch);

        if ($error) {
            throw new \Exception($error);
        }

        return json_decode($response, true);
    }
}