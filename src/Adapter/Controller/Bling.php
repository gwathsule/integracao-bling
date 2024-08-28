<?php

namespace Src\Adapter\Controller;

class Bling extends apiControleOn
{
    const TOKEN_URL = 'https://www.bling.com.br/Api/v3/oauth/token';

    var $mRoute = 'bling';

    /**
     * validação dos métodos
     */
    function api() {

        switch ($this->mMethod) {
            case 'GET':
                $code = $this->getParamURL("code");
                $state = $this->getParamURL("state");
                $this->blingCallBack($code, $state);
                return $this->returnJson(['success']);
                break;
            default:
                echo "invalid method";
                exit;
        }
    }

    private function blingCallBack(string $code, string $state) {
        $idLoggedUser = $this->getIdLoggedUser();
        $blingAppConfig = $this->getBlingAppConfig();
        $blingResponse = $this->getToken($code, $blingAppConfig["client_id"], $blingAppConfig["client_secret"]);
        if(isset($blingResponse["error"])) {
            throw new \Exception($blingResponse["error"]["description"]);
        }

        $tokenData = [
            "access_token" => $blingResponse["access_token"],
            "refresh_token" => $blingResponse["refresh_token"],
            "token_type" => $blingResponse["token_type"],
            "token_expires_in" => $blingResponse["expires_in"],
            "token_scope" => $blingResponse["scope"],
        ];

        $this->saveCustomerToken($idLoggedUser, $tokenData);
        return $this->returnJson($tokenData);
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

    //salva os dados do token da integração no ambiente do cliente
    private function saveCustomerToken(int $customerId, array $tokenData)
    {

    }

    //pega o id do usuário que está logado
    private function getIdLoggedUser(): int
    {
        return 1;
    }

    //pega as configs do app da Bling da HSE Sistemas
    private function getBlingAppConfig(): array
    {
        return [
            "client_id" => $_ENV["CLIENT_ID"],
            "client_secret" => $_ENV["CLIENT_SECRET"],
        ];
    }
}