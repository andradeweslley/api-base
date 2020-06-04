<?php

/**
 * Classe ApiConfig
 * Possui funções para configurar recursos da API
 * @author Weslley Andrade
 */
class ApiConfig {
    /**
     * Executa as funções de configuração da API
     */
    public static function config(): void {
        self::startRouters();
        self::setEnvironment();
    }
    
    /**
     * Inicia validação de rotas
     */
    private static function startRouters(): void {
        require_once './config/routes.php';
        
        $allowedResources = array_map(function($resource){
            // Retira / do final
            $resource['path'] = rtrim($resource['path'], '/');
        
            // Cria regex
            $resource['path'] = preg_replace('/:(\w+)/', '(\w+)', $resource['path']);
        
            // ^\/users\/(\w+)$
            $resource['path'] = '~^' . $resource['path'] . '$~';
        
            return $resource;
        }, $allowedResources);
        
        define('ALLOWED_RESOURCES', $allowedResources);
    }
    
    /**
     * Função que cuida de identificar qual ambiente está sendo executada a API
     */
    private static function setEnvironment(): void {
        require_once './config/environment.php';
        
        if ($_SERVER['HTTP_HOST'] == HOST_PRODUCTION) {
            self::handleEnvironment(PRODUCTION);
        } else if ($_SERVER['HTTP_HOST'] == HOST_HOMOLOGATION) {
            self::handleEnvironment(HOMOLOGATION);
        } else {
            self::handleEnvironment(TESTS, 'localhost:8080/api/', true);
        }
    
        if(DEBUG){
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
    }
    
    /**
     * Função auxiliar para criar as constantes de ambiente
     * @param int         $environment Ambiente que está sendo executado a API
     * @param string|null $baseUrl     URL da API
     * @param bool        $debug       Define se deve funcionar em modo de debug
     */
    private static function handleEnvironment(int $environment, string $baseUrl = null, bool $debug = false): void {
        if (empty($baseUrl)) {
            define('BASE_URL', $_SERVER['HTTP_HOST']);
        } else {
            define('BASE_URL', $baseUrl);
        }
        
        define('ENVIRONMENT', $environment);
        define('DEBUG', $debug);
    }
}