<?php

/**
 * Classe UsersModel
 * Responsável pela modelagem da tabela `users`
 * @author Weslley Andrade
 */
class UsersModel
{
    private $db;
    
    private $fields = [
        'email' => 'user.email',
        'name'  => 'user.name',
        'ativo' => 'user.ativo',
    ];
    
    /**
     * UsersModel constructor.
     */
    function __construct() {
        // Carrega classe de banco de dados
        ApiHandle::loadLibrary('Database');
        
        $this->db = new Database();
    }
    
    /**
     * @param array $email Email a ser buscado se existe
     * @throws ApiException Retorna caso de erro ao executar a query
     * @return array Lista com os dados do usuário e contagem de vezes que bebeu água
     */
    public function getUsersLogin(string $email): array {
        $this->db->clearsql();
        
        $this->db->select(
            'user.id_user as iduser, user.email, user.name, user.password'
        );
        
        $this->db->where(['user.email' => $email]);
        
        return $this->getUsersDefault();
    }
    
    /**
     * @param array $where Condições para consulta
     * @throws ApiException Retorna caso de erro ao executar a query
     * @return array Lista com os dados do usuário e contagem de vezes que bebeu água
     */
    public function getUsersCount(array $where = []): array {
        $filter = $this->db->makeFilter($_GET, $this->fields);
        
        $this->db->clearsql();
        
        $this->db->select(
            'count(distinct user.id_user) as total_records'
        );
        
        if (!empty($where))  $this->db->where($where);
        if (!empty($filter)) $this->db->where($filter);
        
        return $this->getUsersDefault();
    }
    
    /**
     * @param array $where Condições para consulta
     * @throws ApiException Retorna caso de erro ao executar a query
     * @return array Lista com os dados do usuário e contagem de vezes que bebeu água
     */
    public function getUsers(array $where = []): array {
        $filter = $this->db->makeFilter($_GET, $this->fields);
        $order  = isset($_GET['order']) ? $this->db->makeOrder($_GET['order'], $this->fields) : null;
    
        $this->db->clearsql();
    
        $this->db->select(
            'user.id_user as iduser, user.email, user.name'
        );
        
        if (!empty($where))  $this->db->where($where);
        if (!empty($filter)) $this->db->where($filter);
        if (!empty($order))  $this->db->orderBy($order);
    
        $this->db->groupby('user.id_user');
        
        $this->db->limit($_GET['offset'] ?? 0, $_GET['limit'] ?? 100);
        
        return $this->getUsersDefault();
    }
    
    /**
     * Faz a consulta padrão
     * @throws ApiException
     * @return array
     */
    private function getUsersDefault() {
        $this->db->from('user');
    
        $this->db->get();
    
        return $this->db->getResult();
    }
    
    /**
     * Retorna se o usuário está ativo
     * @param array $where Condições para buscar o usuário
     * @throws ApiException
     * @return bool
     */
    public function userIsActive(array $where): bool {
        $user = $this->getUsers($where);
        
        if (empty($user)) {
           return false;
        }
        
        return $user[0]['ativo'];
    }
    
    /**
     * Função que insere o usuário
     * @param string $email    Email do usuário
     * @param string $name     Nome do usuário
     * @param string $password Senha do usuário
     * @throws ApiException
     * @return int Id cadastrado no banco
     */
    public function insertUser(string $email, string $name, string $password): int {
        $this->db->clearsql();
        
        $this->db->set([
            'email'    => $email,
            'name'     => $name,
            'password' => Utils::encript($password),
        ]);
        
        $this->db->from('user');
        
        return $this->db->insert();
    }
    
    /**
     * Função que atualiza o usuário
     * @param int    $id       Id do usuário a ser atualizado
     * @param string $email    Novo email
     * @param string $name     Novo nome
     * @param string $password Nova senha
     * @throws ApiException
     */
    public function updateUser(int $id, string $email = null, string $name = null, string $password = null): void {
        $this->db->clearsql();
        
        // Verifica se o novo e-mail já está cadastrado
        if (!empty($email))    $this->db->set(['email'    => $email]);
        if (!empty($name))     $this->db->set(['name'     => $name]);
        if (!empty($password)) $this->db->set(['password' => Utils::encript($password)]);
        
        $this->db->set(['ativo' => true]);
        
        $this->db->from('user');
        
        $this->db->where([
            'id_user' => $id
        ]);
        
        $this->db->update();
    }
    
    /**
     * Função que desativa o usuário
     * @param int $id Id do usuário a ser desativado
     * @throws ApiException
     */
    public function deactivateUser(int $id): void {
        $this->db->clearsql();
        
        $this->db->set(['ativo' => false]);
        
        $this->db->from('user');
        
        $this->db->where([
            'id_user' => $id
        ]);
        
        $this->db->update();
    }
}