<?php

/**
 * Classe StartController
 * Responsável pelo controle da configuração da API
 * @author Weslley Andrade
 */
class StartController
{
    private $method;
    private $db;
    
    function __construct() {
        // Carrega classe de banco de dados
        ApiHandle::loadLibrary('Database');
        
        $this->db = new Database();
    }
    
    /**
     * Função responsável por gerenciar a requisição da API RESTFul.
     * @param string $method  Método da requisição
     * @param array  $body    Corpo da requisição
     * @param array  $args    URI da requisição
     * @throws ApiException Handler de erros
     * @return array Retona array com os dados
     */
    public function start(string $method, array $body, ?array $args):array {
        $rawData = false;
        $this->method = $method;
        
        switch ($this->method) {
            case 'POST':
                $rawData = $this->configApi();
                
                break;
            default:
                ApiResponse::returnResponseMethodNotAllowed();
                break;
        }
        
        return $rawData;
    }
    
    /**
     * Função que realiza a configuração da API.
     * @throws ApiException Retorna se houver erros
     * @return array Retorna o Bearer de acesso
     */
    public function configApi(): array {
        return $this->createTables();
    }
    
    /**
     * Função que executa os CREATE TABLE
     * @throws ApiException
     * @return array
     */
    private function createTables(): array {
        $status = [];
        
        $createUser = 'CREATE TABLE `user` (
              `id_user` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(80) NOT NULL,
              `email` VARCHAR(80) NOT NULL,
              `password` VARCHAR(80) NOT NULL,
              `ativo` TINYINT NOT NULL DEFAULT 1,
              PRIMARY KEY (`id_user`))';
        
        $status['user'] = $this->createTable('user', $createUser);
        
        return $status;
    }
    
    /**
     * Função cria tabela caso não exista
     * @param string $table Tabela a ser criada
     * @param string $query Instrução para criação da tabela
     * @throws ApiException
     * @return array
     */
    private function createTable(string $table, string $query): array {
        $this->db->query("SHOW TABLES LIKE '$table'");
    
        if ($this->db->getRowCount() == 0) {
            $createUsers = $this->db->query($query);
        
            if ($createUsers) {
                return ['status' => true, 'message' => 'Created'];
            } else {
                return ['status' => false, 'message' => 'Error to create table'];
            }
        } else {
            return ['status' => true, 'message' => 'Table already exists'];
        }
    }
}