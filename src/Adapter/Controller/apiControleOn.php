<?php

namespace Src\Adapter\Controller;

use Klein\Request;
use Klein\Response;

class apiControleOn
{
    protected string $mMethod;
    protected Response $response;
    protected Request $request;

    public function __construct(Request $request, Response $response)
    {
        $this->mMethod = $request->method();
        $this->response = $response;
        $this->request = $request;
    }

    protected function returnJson(array $data, int $code = 200)
    {
        return $this->response->code($code)->json($data);
    }

    protected function getParamURL(string $param): ?string
    {
        return $this->request->param($param);
    }
}