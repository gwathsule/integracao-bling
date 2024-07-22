<?php

namespace Src\Adapter\Controller;

use Klein\Request;
use Klein\Response;

interface Controller
{
    public function handle(Request $request, Response $response) : Response;
}