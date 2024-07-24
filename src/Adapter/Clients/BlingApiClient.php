<?php

namespace Src\Adapter\Clients;

class BlingApiClient
{
    const AUTHORIZATION_URL = 'https://www.bling.com.br/Api/v3/oauth/authorize';
    const TOKEN_URL = 'https://www.bling.com.br/Api/v3/oauth/token';

    public function authorize($clientId, $state) {
        //$callbackUrl = "https://tx5z4uxt7j.sharedwithexpose.com/bling-callback";
        $params = array(
            'response_type' => 'code',
            'client_id' => $clientId,
            'state' => $state
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

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


    public function refreshToken($clientId, $clientSecret, $refreshToken)
    {
        $params = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        );

        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => '1.0',
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

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