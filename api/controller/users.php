<?php

/**
 * Classe UsersController
 * Responsável pelo controle de usuários
 * @author Weslley Andrade
 */
class UsersController
{
    const USER_ID   = 2;
    const SUBACTION = 3;
    
    private $method;
    private $userModel;
    
    private $resourceFields = [
        'email' => [
            'required' => true,
            'type' => 'email',
            'length' => 80,
            'value' => null,
        ],
        'name' => [
            'required' => true,
            'type' => 'string',
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
     * UsersController constructor.
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
        $userId       = $args[self::USER_ID]   ?? null;
        $subaction    = $args[self::SUBACTION] ?? null;
        
        switch ($this->method) {
            case 'GET':
                if (empty($userId)) {
                    $rawData = $this->getAll();
                } else if (is_numeric($userId)){
                    if (!empty($subaction)) {
                        switch ($subaction) {
                            case 'history':
                                $rawData = $this->drinkHistory($userId);
                                
                                break;
                            default:
                                break;
                        }
                    } else {
                        $rawData = $this->getOne($userId);
                    }
                }
                
                break;
            case 'POST':
                if (empty($userId)) {
                    $rawData = $this->post($body);
                } else if (is_numeric($userId)) {
                    if (!empty($subaction)) {
                        switch ($subaction) {
                            case 'drink':
                                $rawData = $this->drinkWater($userId, $body);
            
                                break;
                            default:
                                break;
                        }
                    }
                }
                
                break;
            case 'PUT':
                $rawData = $this->put($body);
                
                break;
            case 'DELETE':
                $rawData = $this->delete();
                
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
     * Função que busca usuário por ID
     * @param int $id Id do usuário que está sendo buscado
     * @throws ApiException
     * @return array Retorna um usuário
     */
    public function getOne(int $id): array {
        $user = $this->userModel->getUsers(['user.id_user' => $id]);
        
        if (empty($user)) {
            ApiResponse::handleErrorsResponse(
                404,
                'UserNotFound',
                'Usuário não encontrado'
            );
        }
        
        return $user[0];
    }
    
    /**
     * Função que busca lista de usuários
     * @return array Retorna lista de usuários
     * @throws ApiException
     */
    public function getAll(): array {
        $totalRecords = $this->userModel->getUsersCount();
        
        if (empty($totalRecords)) {
            header('X-Total-Count: 0');
        } else {
            header('X-Total-Count: ' . $totalRecords[0]['total_records']);
        }
        
        return $this->userModel->getUsers();
    }
    
    /**
     * Função que cria um usuário.
     * @param array $obj Dados do usuário
     * @throws ApiException
     * @return array Retorna id e dados do usuário
     */
    private function post(array $obj): array {
        $this->resourceFields = Utils::handleDataReceived($this->resourceFields, $this->method, $obj);
        
        $userExists = $this->userModel->getUsers(['email' => $this->resourceFields['email']['value']]);
        
        if (count($userExists) > 0) {
            ApiResponse::handleErrorsResponse(
                409,
                'UserAlreadyExists',
                'Já existe um usuário cadastrado pra esse e-mail'
            );
        }
        
        $id = $this->userModel->insertUser(
            $this->resourceFields['email']['value'],
            $this->resourceFields['name']['value'],
            $this->resourceFields['password']['value']
        );
        
        $userData = array_merge(['iduser' => $id], Utils::extractValues($this->resourceFields));
        unset($userData['password']);
        
        return ['statusCode' => 201, 'data' => $userData];
    }
    
    /**
     * Função que atualiza dados do usuário
     * @param array $obj Dados do usuário
     * @throws ApiException
     * @return array Retorno de No Content
     */
    private function put(array $obj): array {
        $this->resourceFields = Utils::handleDataReceived($this->resourceFields, $this->method, $obj);
        
        if (!empty($this->resourceFields['email']['value'])) {
            // Verifica se o e-mail não está cadastrado em outro usuário
            $checkEmail = $this->getUser([
                'email' => $this->resourceFields['email']['value'],
                'id_user' => [
                    'dif' => ApiHandle::$jwtData->data->id,
                ]
            ]);
    
            if (count($checkEmail) > 0) {
                ApiResponse::handleErrorsResponse(
                    409,
                    'EmailUnavailable',
                    'Esse e-mail já está sendo utilizado por outro usuário'
                );
            }
        }
    
        // Atualiza usuário com novas informações
        $this->userModel->updateUser(
            ApiHandle::$jwtData->data->id,
            $this->resourceFields['email']['value'],
            $this->resourceFields['name']['value'],
            $this->resourceFields['password']['value']
        );
        
        return ['statusCode' => 204, 'data' => []];
    }
    
    /**
     * Função que desativa um usuário
     * @throws ApiException
     * @return array Retorno de No Content
     */
    private function delete(): array {
        $this->userModel->deactivateUser(ApiHandle::$jwtData->data->id);
        
        return ['statusCode' => 204, 'data' => []];
    }
    
    /**
     * Função para beber água
     * @param int   $userId
     * @param array $obj
     * @return array
     * @throws ApiException
     */
    private function drinkWater(int $userId, array $obj): array {
        $resourceDrinkFields = [
            'drink_ml' => [
                'required' => true,
                'type' => 'integer',
                'value' => null,
            ],
        ];
    
        $resourceDrinkFields = Utils::handleDataReceived($resourceDrinkFields, $this->method, $obj);
        
        if (!$this->userModel->userIsActive(['id_user' => $userId])) {
            ApiResponse::handleErrorsResponse(
                404,
                'UserNotFound',
                'Usuário não encontrado'
            );
        }
        
        ApiHandle::loadModel('Drink');
        
        $drinkModel = new DrinkModel();
        
        $drinkModel->drinkWater($userId, $resourceDrinkFields['drink_ml']['value']);
        
        return $this->getOne($userId);
    }
}