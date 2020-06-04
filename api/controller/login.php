<?php

use Firebase\JWT\JWT;

/**
 * Classe LoginController
 * Responsavel pelo controle de login
 * @author Weslley Andrade
 */
class LoginController
{
    private $method;
    private $userModel;
    
    private $resourceFields = [
        'email' => [
            'required' => true,
            'type' => 'email',
            'length' => 80,
            'value' => null,
        ],
        'password' => [
            'required' => true,
            'type' => 'string',
            'length' => 80,
            'value' => null,
        ],
    ];
    
    /**
     * LoginController constructor.
     */
    function __construct() {
        // Carrega classe de Utils
        ApiHandle::loadLibrary('Utils');
        
        // Carrega model de usuário
        ApiHandle::loadModel('Users');
        
        $this->userModel = new UsersModel();
    }
    
    /**
     * Função responsável por gerenciar a requisição da API RESTFul.
     * @param string $method  Método da requisição
     * @param array  $body    Corpo da requisição
     * @param array  $args    URI da requisição
     * @throws ApiException Handler de erros
     * @return array Retona array com os dados
     */
    public function start(string $method, array $body, ?array $args): array {
        $rawData = false;
        $this->method = $method;
    
        switch ($this->method) {
            case 'POST':
                $rawData = $this->login($body);
                
                break;
            default:
                ApiResponse::returnResponseMethodNotAllowed();
                break;
        }
        
        if ($rawData === false) {
            ApiResponse::returnResponseResourceNotFound();
        }
        
        return $rawData;
    }
    
    /**
     * Função que realiza login do usuário.
     * @param array $obj Dados de login
     * @throws ApiException Retorna se houver erros nos campos
     * @return array Retorna o Bearer de acesso
     */
    public function login(array $obj): array {
        $this->resourceFields = Utils::handleDataReceived($this->resourceFields, $this->method, $obj);
    
        // Consulta o banco de dados pela existência do usuário.
        $user = $this->userModel->getUsersLogin($this->resourceFields['email']['value']);
    
        if (empty($user)) {
            ApiResponse::handleErrorsResponse(
                404,
                'UserNotFound',
                'Usuário não encontrado.'
            );
        }
    
        $user = $user[0];
    
        // Compara se a senha conhecide com a cadastrada no banco
        if (Utils::encript($this->resourceFields['password']['value']) !== $user['password']) {
            ApiResponse::handleErrorsResponse(
                404,
                'UserNotFound',
                'Usuário não encontrado.'
            );
        }
    
        // Remove a senha pra não retornar no body
        unset($user['password']);
    
        // Cria o token JWT
        $time = time();
    
        $payload = [
            "data" => [
                "id" => $user['iduser'],
                "nome" => $user['name'],
                "email" => $user['email'],
            ],
            "iat" => $time,
            "exp" => $time + 3600
        ];
    
        $user['token'] = JWT::encode($payload, JWT_SECRET);
    
        return $user;
    }
}