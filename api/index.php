<?php

// Inicia os Headers para retorno
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');

// Inicia handle
require_once 'core/apihandle.php';

// Realiza configuração da API
ApiHandle::create();

// Busca dados de requisição
$method     = $_SERVER['REQUEST_METHOD'];
$pathInfo   = $_SERVER['PATH_INFO'] ?? '';
$body       = ApiHandle::rawBodyToArray(file_get_contents('php://input'));
$queryParam = '';

// Resolve o path
$pathInfo = rtrim(str_replace('\\\\', '\\', strtolower($pathInfo)), '/');

// Primeira ocorrência é o recurso. Busco se existe a model.
try {
    // Transforma o path em array
    $arrPathInfo = explode('/', $pathInfo);
    
    if (empty($arrPathInfo) || count($arrPathInfo) === 1) {
        ApiResponse::handleErrorsResponse(
            404,
            'ResourceNotSent',
            'Recurso não enviado'
        );
    }
    
    // Realiza processo de autenticação quando necessário
    ApiHandle::autenticate($method, $pathInfo);
    
    // Busca o recurso
    $controller = ApiHandle::loadController($arrPathInfo[1]);
    
    if(!$controller){
        ApiResponse::returnResponseResourceNotFound();
    } else {
        // Realiza funções do recurso
        $rawData = $controller->start($method, $body, $arrPathInfo);
    
        if (isset($rawData['data'])) {
            ApiResponse::handleResponse($rawData['data'], $rawData['statusCode'] ?? 200);
        }
        
        // Retorna pro cliente a resposta
        ApiResponse::handleResponse($rawData);
    }
} catch (ApiException $e){
    // Devolve erros da API
    ApiResponse::handleResponse(json_decode($e->getMessage(), true), $e->getCode());
}