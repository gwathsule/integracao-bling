<?php

namespace Src\Adapter\Controller;

use Src\Adapter\Clients\BlingApiClient;
use Src\Adapter\Repository\Sqlite\BlingCustomerRepository;
use Klein\Request;
use Klein\Response;
use Throwable;

class APIBlingController
{

    const STATE = "121e63b56ab3d845622ca137b1e1e4";

    public function __construct(
        private BlingCustomerRepository $repository = new BlingCustomerRepository(),
        private BlingApiClient $client = new BlingApiClient(),
    ) {
    }

    public function registerAppBling(Request $request, Response $response)
    {
        try {
            $clientId = $request->param("client_id");
            $clientSecret = $request->param("client_secret");
            $state = substr(md5(rand()), 0, 6);
            $this->repository->insertNewBlingCustomerData($clientId, $clientSecret, $state);
            $this->client->authorize($clientId, $state);
        } catch (Throwable $exception) {
            return $response
                ->code(500)
                ->json(['message' => 'Internal server error.']);
        }
    }

    public function callback(Request $request, Response $response): Response
    {
        try {

            $code = $request->param("code");
            $state = $request->param("state");
            $customer = $this->repository->filterByClientState($state);
            $blingResponse = $this->client->getToken($code, $customer["client_id"], $customer["client_secret"]);
            $attributesToUpdate = [
                "access_token" => $blingResponse["access_token"],
                "refresh_token" => $blingResponse["refresh_token"],
                "token_type" => $blingResponse["token_type"],
                "token_expires_in" => $blingResponse["expires_in"],
                "token_scope" => $blingResponse["scope"],
            ];
            $data = $this->repository->update($attributesToUpdate, $customer["id"]);

            return $response->code(200)->json(['message' => 'OK']);

        } catch (Throwable $exception) {
            return $response
                ->code(500)
                ->json(['message' => 'Internal server error.']);
        }
    }
}