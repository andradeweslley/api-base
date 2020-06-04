<?php

require_once "../vendor/autoload.php";

use Firebase\JWT\JWT;

/**
 * Classe ApiHandle
 * Possui funções para auxiliar o funcionamento da API
 * @author Weslley Andrade
 */
class ApiHandle {
    public static $jwtData = []; // @todo: Criar getter e setter
    public static $headers = []; // @todo: Criar getter e setter
    
    /* REGION: Funções que lidam com carregamento de arquivos para API */
    
    private const FOLDER_CONTROLLER = './controller/';
    private const FOLDER_MODEL = './model/';
    private const FOLDER_LIB = './lib/';
    private const FOLDER_CORE = './core/';
    
    public static function create(): void {
        // Inicia os outros arquivos necessários para o funcionamento da API
        ApiHandle::loadFile(self::FOLDER_CORE, 'ApiException', 'ApiException', []);
        ApiHandle::loadFile(self::FOLDER_CORE, 'ApiResponse', 'ApiResponse');
        ApiHandle::loadFile(self::FOLDER_CORE, 'ApiConfig', 'ApiConfig');
        
        // Realiza configuração automática da API
        ApiConfig::config();
        
        self::formatHeaders();
    }
    
    /**
     * Função que carrega os recursos do sistema
     * @param string     $controller      Recurso a ser carregado
     * @param array|null $constructValues Parâmetros a serem carregados pelo construtor da classe
     * @return bool|stdClass Booleano false em caso de erro, classe em caso de sucesso
     */
    public static function loadController(string $controller, array $constructValues = null) {
        $controllerClass = ucfirst($controller) . 'Controller';
        
        return self::loadFile(self::FOLDER_CONTROLLER, $controllerClass, $controller, $constructValues);
    }
    
    /**
     * Função que carrega os recursos do sistema relacionado a banco
     * @param string     $model           Recurso a ser carregado
     * @param array|null $constructValues Parâmetros a serem carregados pelo construtor da classe
     * @return bool|stdClass Booleano false em caso de erro, classe em caso de sucesso
     */
    public static function loadModel(string $model, array $constructValues = null) {
        $modelClass = ucfirst($model) . 'Model';
        
        return self::loadFile(self::FOLDER_MODEL, $modelClass, $model, $constructValues, false);
    }
    
    /**
     * Função que carrega bibliotecas
     * @param string $library Biblioteca a ser carregada
     * @return bool|stdClass Booleano false em caso de erro, classe em caso de sucesso
     */
    public static function loadLibrary(string $library) {
        return self::loadFile(self::FOLDER_LIB, $library, $library, null, false);
    }
    
    /**
     * Função que carrega arquivos e inicia a classe
     * @param string     $folder          Pasta que está o arquivo
     * @param string     $class           Classe que será iniciada
     * @param string     $file            Arquivo a ser carregado
     * @param array|null $constructValues Parâmetros a serem carregados pelo construtor da classe
     * @param bool       $startClass      Inicia a classe automaticamente
     * @return bool|stdClass Booleano false em caso de erro, classe em caso de sucesso
     */
    private static function loadFile(
        string $folder,
        string $class,
        string $file,
        array $constructValues = null,
        bool $startClass = true)
    {
        $file = strtolower($file);
        
        if (!class_exists($class, false)) {
            if (!file_exists($folder . $file . '.php')) {
                return false;
            }
            
            require_once $folder . $file . '.php';
            
            if (!class_exists($class, false)) {
                return false;
            }
        }
        
        if ($startClass) {
            return new $class($constructValues);
        }
        
        return true;
    }
    
    /* ENDREGION: Funções que lidam com carregamento de arquivos para API */
    
    /* REGION: Tratativas no Request */
    
    /**
     * Função que realiza autenticação na API
     * @param string $method   Método usado
     * @param string $pathInfo Recurso
     * @throws ApiException
     */
    public static function autenticate(string $method, string $pathInfo): void {
        $needsAutenticate = true;
    
        foreach (ALLOWED_RESOURCES as $resource) {
            if (preg_match($resource['path'], $pathInfo)) {
                if (in_array($method, $resource['methods'])) {
                    $needsAutenticate = false;
                
                    break;
                }
            }
        }
    
        if ($needsAutenticate) {
            // Valida existencia do Header Authorization
            if (empty(self::$headers['authorization'])) {
                ApiResponse::handleErrorsResponse(
                    401,
                    'MissingHeaderAuthorization',
                    'Faltando o header Authorization'
                );
            } else {
                // Verifica se foi informado o Bearer
                if (preg_match('/Bearer/i', self::$headers['authorization'])) {
                    // @todo: Verifica se o JWT é válido
                    $token = trim(str_ireplace("Bearer", "", self::$headers['authorization']));
    
                    try {
                        self::$jwtData = JWT::decode($token, JWT_SECRET, ['algorithm' => 'HS256']);
                    } catch (\Exception $e) {
                        ApiResponse::handleErrorsResponse(
                            401,
                            'InvalidJWT',
                            $e->getMessage()
                        );
                    }
                    
                    // Verifica se o usuário está ativo
                    self::loadModel('Users');
                    
                    $userModel = new UsersModel();
    
                    try {
                        $userActive = $userModel->userIsActive(['id_user' => self::$jwtData->data->id]);
    
                        if ($userActive === false) {
                            ApiResponse::handleErrorsResponse(
                                410,
                                'UserNotLongerAvaliable',
                                'Usuário não possui mais permissão para acessar o sistema'
                            );
                        }
                    } catch (ApiException $e) {
                        $message = json_decode($e->getMessage(), true);
                        
                        if ($e->getCode() == 404) {
                            ApiResponse::handleErrorsResponse(
                                410,
                                'UserNotLongerAvaliable',
                                'Usuário não possui mais permissão para acessar o sistema'
                            );
                        }
                        
                        throw new ApiException($message, $e->getCode());
                    }
                } else {
                    ApiResponse::handleErrorsResponse(
                        401,
                        'MissingBearer',
                        'Bearer não informado'
                    );
                }
            }
        }
    }
    
    /**
     * Função que faz conversão do corpo do request de raw para array
     * @param string $body    Corpo da requisisão em raw
     * @param array  $header  Informações dos Headers
     * @return array Retorno do corpo em array
     */
    public static function rawBodyToArray(string $body, array $header = []): array {
        if (empty($body)) {
            return [];
        }
        
        // @todo: Validar o header de content-type
        if (isset(self::$headers['content-type']) && self::$headers['content-type'] == 'xml') {
            // ...
        }
        
        return json_decode($body, true);
    }
    
    /**
     * Função que formata e padroniza os headers para melhor validação
     */
    public static function formatHeaders(): void {
        $formatedHeaders = [];
        $requestHeaders  = apache_request_headers();
    
        foreach ($requestHeaders as $key => $val) {
            $formatedHeaders[strtolower($key)] = $val;
        }
        
        self::$headers = $formatedHeaders;
    }
    
    /* ENDREGION: Tratativas no Request */
}
