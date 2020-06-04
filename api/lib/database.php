<?php

/**
 * Classe Database
 * Responsável pelo gerenciamento das funções de banco de dados
 * @author Weslley Andrade
 */
class Database
{
    /* Comandos mysql básicos. */
    private $mysqlComands = ['now()', 'null', 'true', 'false', 'CURDATE()'];
    /* Instancia da conexão. */
    private $dbh;
    /* Resultado das queries. */
    private $result;
    /* Sentença FROM. */
    private $from;
    /* Sentença WHERE. */
    private $where;
    /* Sentença SELECT. */
    private $select;
    /* Sentença UPDATE. */
    private $set;
    /* Sentença ORDER BY. */
    private $order;
    /* Sentença GROUP BY. */
    private $group;
    /* Sentença LIMIT. */
    private $limit;
    /* Sentença JOIN. */
    private $join;
    /* Indica se existe transação ativa. */
    private $transaction = false;
    
    /**
     * Função Construct.
     * @throws ApiException
     */
    public function __construct() {
        if ($this->dbh === null) {
            require_once './config/database.php';
    
            $this->dbh = new mysqli(DB_HOST
                , DB_USER
                , DB_PASSWORD
                , DB_NAME
                , DB_PORT);
    
            if (mysqli_connect_errno() || $this->dbh === false) {
                ApiResponse::handleErrorsResponse(
                    500,
                    'DatabaseFailedToConnect',
                    mysqli_connect_errno() . ': ' . mysqli_connect_error()
                );
            }
    
            mysqli_set_charset($this->dbh, "utf8");
        }
    }
    
    /**
     * Função Destruct.
     */
    public function __destruct() {
        if ($this->result != null && !is_bool($this->result)) {
            $this->result->close();
        }
        
        if ($this->dbh != null) {
            $this->dbh->close();
        }
    }
    
    /**
     * Função responsável por aplicar caracteres de escape na query sql.
     * @param string $string Valor a ser validado.
     * @return string
     */
    public function escape(string $string) {
        return mysqli_escape_string($this->dbh, $string);
    }
    
    /**
     * Função responsável por criar uma nova transação.
     */
    public function start_transaction() {
        mysqli_begin_transaction($this->dbh);
        $this->transaction = true;
    }
    
    /**
     * Função responsável por aplicar um commit na transação corrente.
     */
    public function commit() {
        mysqli_commit($this->dbh);
        $this->transaction = false;
    }
    
    /**
     * Função responsável por aplicar um rollback na transação corrente.
     */
    public function rollback() {
        mysqli_rollback($this->dbh);
        $this->transaction = false;
    }
    
    /**
     * Função responsável por definir a sentença de SELECT em uma variável interna.
     * @param array|string $obj - tabela que será incluída.
     */
    public function select($obj): void {
        if (is_array($obj)) {
            foreach ($obj as $val) {
                $this->select[] = $val;
            }
        } else {
            $this->select[] = $obj;
        }
    }
    
    /**
     * Função responsável por incluir as clausulas UPDATE.
     * @param array $array - array com  as clausulas.
     */
    public function set(array $array): void {
        foreach ($array as $key => $val) {
            if (in_array(trim($val), $this->mysqlComands)) {
                $this->set[] = $key . " = " . $val;
            } else {
                if (gettype($val) == 'string') {
                    $this->set[] = $key . " = '" . $this->escape($val) . "'";
                } else if (is_bool($val)) {
                    if ($val) {
                        $this->set[] = $key . " = true";
                    } else {
                        $this->set[] = $key . " = false";
                    }
                } else if (is_null($val)) {
                    $this->set[] = $key . " = NULL";
                } else {
                    $this->set[] = $key . " = " . $this->escape($val);
                }
            }
        }
    }
    
    /**
     * Função responsável por definir a sentença de FROM em uma variável interna.
     * @param string $string - tabela que será incluída.
     */
    public function from(string $string): void {
        $this->from = $string;
    }
    
    /**
     * Função responsável por definir a sentença de JOIN em uma variável interna.
     * @param string $table Tabela que sofrerá o join.
     * @param string $on    Tabela que deverá ser referenciada.
     * @param bool   $left  Indica se será um LEFT JOIN.
     */
    public function join(string $table, string $on, bool $left = false): void {
        if ($left) {
            $this->join[] = "LEFT JOIN " . $table . " ON " . $on;
        } else {
            $this->join[] = "INNER JOIN " . $table . " ON " . $on;
        }
    }
    
    /**
     * Função responsável por contar o filtro customizado do padrão LHS Brackets
     * @param string $key Campo que deve ser filtrado
     * @param array  $val Array com os valores e tipo do filtro
     * @return string
     * @throws ApiException
     */
    private function makeCustomWhere(string $key, array $val): string {
        $vKey = array_keys($val)[0];
        
        switch($vKey){
            case 'eq':
                return $key . " = '" . $this->escape($val[$vKey]) .  "'";
            case 'lk':
                return $key . " like '%" . $this->escape($val[$vKey]) .  "%'";
            case 'lks':
                return $key . " like '" . $this->escape($val[$vKey]) .  "%'";
            case 'lke':
                return $key . " like '%" . $this->escape($val[$vKey]) .  "'";
            case 'gt':
                return $key . " > '" . $this->escape($val[$vKey]) . "'";
            case 'gte':
                return $key . " >= '" . $this->escape($val[$vKey]) . "'";
            case 'lt':
                return $key . " < '" . $this->escape($val[$vKey]) . "'";
            case 'lte':
                return $key . " <= '" . $this->escape($val[$vKey]) . "'";
            case 'dif':
                return $key . " != '" . $this->escape($val[$vKey]) . "'";
            default :
                ApiResponse::handleErrorsResponse(
                    400,
                    'InvalidBracketLHS',
                    'Filtro não reconhecido pelo sistema. Verifique a lista de LHS Brackets permitidos'
                );
        }
    }
    
    /**
     * Função responsável por definir a sentença de WHERE em uma variável interna.
     * @param array|string $obj Sentença que deve ser incluída.
     * @throws ApiException
     */
    public function where($obj): void {
        if (!empty($obj)) {
            if (is_array($obj)) {
                foreach ($obj as $key => $val) {
                    if (is_array($val)) {
                        $this->where[] = $this->makeCustomWhere($key, $val);
                    } else if (in_array(trim($val), $this->mysqlComands)) {
                        $this->where[] = $key . " = " . $val;
                    } else {
                        if (substr($key, -2) == '!=') {
                            $this->where[] = $key . " '" . $this->escape($val) . "'";
                        } else {
                            $this->where[] = $key . " = '" . $this->escape($val) . "'";
                        }
                    }
                }
            } else {
                $this->where[] = $obj;
            }
        }
    }
    
    /**
     * Função responsável por definir a sentença de LIMIT/OFFSET em uma variável interna.
     * @param int $offset Sentença OFFSET que deve ser incluída.
     * @param int $limit  Sentença LIMIT que deve ser incluidá
     */
    public function limit(int $offset, int $limit): void {
        if(isset($offset) && isset($limit)) {
            $this->limit = $offset . ',' . $limit;
        }
    }
    
    /**
     * Função responsável por definir a sentença de ORDER BY em uma variável interna.
     * @param array|string $obj Sentença que deve ser incluída.
     */
    public function orderBy($obj): void {
        if (is_array($obj)) {
            foreach ($obj as $key => $val) {
                $this->order[$key] = $val;
            }
        } else {
            $this->order[] = $obj;
        }
    }
    
    /**
     * Função responsável por definir a sentença de GROUP BY em uma variável interna.
     * @param array|string $obj Sentença que deve ser incluída.
     */
    public function groupBy($obj): void {
        if (is_array($obj)) {
            foreach ($obj as $val) {
                $this->group[] = $val;
            }
        } else {
            $this->group[] = $obj;
        }
    }
    
    /**
     * Função responsável por limpar a sentença do sql.
     */
    public function clearsql(): void {
        unset($this->from);
        unset($this->where);
        unset($this->select);
        unset($this->set);
        unset($this->order);
        unset($this->order);
        unset($this->group);
        unset($this->limit);
        unset($this->join);
    }
    
    /**
     * Função responsável por executar a query SELECT das variáveis internas.
     * @throws ApiException
     */
    public function get(): void {
        $fields  = $this->mountFields();
        $join    = $this->mountJoin();
        $where   = $this->mountWhere();
        $orderBy = $this->moundOrderBy();
        $groupBy = $this->mountGroupBy();
        
        $select = "select " . $fields
            . " from " . $this->from . " "
            . $join . " "
            . $where . " "
            . $groupBy . " "
            . $orderBy;
        
        if (!empty($this->limit))
            $select .= " LIMIT " . $this->limit;
        
        $this->result = mysqli_query($this->dbh, $select);
        
        $this->clearsql();
        
        if ($this->result === false) {
            ApiResponse::returnResponseQueryError(
                __FUNCTION__,
                $select,
                $this->getLastError()
            );
        }
    }
    
    /**
     * Função responsável por executar a query INSERT das variáveis internas.
     * @throws ApiException
     * @return int Retorna Id cadastrado
     */
    public function insert(): int {
        $set = $this->mountSet();
        
        $insert = "insert into " . $this->from . " set " . $set;
        
        $stmt = mysqli_query($this->dbh, $insert);
    
        $this->clearsql();
        
        if ($stmt === false) {
            ApiResponse::returnResponseQueryError(
                __FUNCTION__,
                $insert,
                $this->getLastError()
            );
        }
        
        return $this->lastInsertId();
    }
    
    /**
     * Função responsável por executar a query UPDATE das variáveis internas.
     * @throws ApiException
     */
    public function update(): void {
        $set   = $this->mountSet();
        $where = $this->mountWhere();
        
        $update = "update " . $this->from . " set " . $set . " " . $where;
        
        $stmt = mysqli_query($this->dbh, $update);
        
        $this->clearsql();
        
        if ($stmt === false) {
            ApiResponse::returnResponseQueryError(
                __FUNCTION__,
                $update,
                $this->getLastError()
            );
        }
    }
    
    /**
     * Função responsável por executar um raw sql.
     * @param string $query Raw sql a ser executado.
     * @param bool   $dump  Indica se o texto da query deverá ser retornada.
     * @throws ApiException
     */
    public function query($query, $dump = false): void {
        $this->result = mysqli_query($this->dbh, $query);
    
        $this->clearsql();
        
        if ($this->result === false) {
            ApiResponse::returnResponseQueryError(
                __FUNCTION__,
                $query,
                $this->getLastError()
            );
        }
    }
    
    /**
     * Função responsável por trazer o último erro que ocorreu no banco
     * @return array
     */
    public function getLastError(): array {
        return [mysqli_errno($this->dbh) => mysqli_error($this->dbh)];
    }
    
    /**
     * Função responsável o número de linhas do resultado da query.
     * @return int
     */
    public function getRowCount(): int {
        return mysqli_num_rows($this->result);
    }
    
    /**
     * Função responsável o total de linhas afetadas pela query.
     * @return int
     */
    public function getAffectedRows(): int {
        return mysqli_affected_rows($this->dbh);
    }
    
    /**
     * Função responsável por retornar todas as linhas resultantes da query.
     * @return array
     */
    public function getResult(): array {
        if ($this->getRowCount() === 0) {
            return [];
        }
        
        $results = mysqli_fetch_all($this->result, MYSQLI_ASSOC);
        
        $rowsValues = [];
        $fieldsTypes = [];
        
        while ($column_info = mysqli_fetch_field($this->result)) {
            $fieldsTypes[] = [
                'name' => $column_info->name,
                'type' => $column_info->type
            ];
        }
        
        foreach ($results as $result) {
            $fieldsValues = [];
            
            // Trata campos para fazer cast automático ao retornar do banco
            foreach ($fieldsTypes as $column_info) {
                // https://www.php.net/manual/en/mysqli-result.fetch-field.php
                switch ($column_info['type']) {
                    case 1: // tinyint, bool
                        if (strlen($result[$column_info['name']]) === 1 || $result[$column_info['name']] < 2) {
                            $fieldsValues[$column_info['name']] = (bool) $result[$column_info['name']];
                        } else {
                            $fieldsValues[$column_info['name']] = (int) $result[$column_info['name']];
                        }
                        
                        break;
                    case 2: // smallint
                    case 3: // integer
                    case 8: // bigint
                    case 9: // mediumint
                        $fieldsValues[$column_info['name']] = (int) $result[$column_info['name']];
                        
                        break;
                    case 4: // float
                    case 5: // double
                    case 246: // decimal, numeric, fixed
                        $result[$column_info['name']] = $result[$column_info['name']] == 0 ? 0.000 : $result[$column_info['name']];
                        $fieldsValues[$column_info['name']] = (float) $result[$column_info['name']];
                        
                        break;
                    case 7: // timestamp
                    case 12: // datetime
                        $fieldsValues[$column_info['name']] = date('Y-m-d\TH:i:s', strtotime($result[$column_info['name']]));
                        
                        break;
                    default:
                        $fieldsValues[$column_info['name']] = $result[$column_info['name']];
                }
            }
            
            $rowsValues[] = $fieldsValues;
        }
        
        return $rowsValues;
    }
    
    /**
     * Função responsável por retornar o último ID inserido.
     * @return string
     */
    public function lastInsertId(): string {
        return mysqli_insert_id($this->dbh);
    }
    
    /**
     * Função responsável por preparar o Array utilizado para filtro das queries.
     * @param array $filtro Array com os campos requeridos para filtro
     * @param array $param  Array com os campos disponíveis para filtro
     * @return array
     */
    public function makeFilter(array $filtro, array $param): array {
        $ret = [];
        
        if (is_array($filtro) && !empty($filtro)) {
            foreach ($filtro as $key => $val) {
                if (isset($param[$key])) {
                    $ret[$param[$key]] = $val;
                }
            }
        }
        
        return $ret;
    }
    
    /**
     * Função responsável por preparar o Array utilizado para ordenação das queries.
     * @param array|string $order Array com os campos requeridos para ordenação
     * @param array        $param Array com os campos disponíveis para ordenação
     * @return array
     */
    public function makeOrder($order, array $param): array {
        $ret = array();
        
        if (!is_null($order)) {
            if (is_array($order)) {
                if (isset($order['asc'])) {
                    if (isset($param[$order['asc']])) {
                        $ret[$param[$order['asc']]] = 'asc';
                    }
                } else if (isset($order['desc'])) {
                    if (isset($param[$order['desc']])) {
                        $ret[$param[$order['desc']]] = 'desc';
                    }
                }
            } else if (isset($param[$order])) {
                $ret[$param[$order]] = 'asc';
            }
        }
        
        return $ret;
    }
    
    /**
     * Função que monta os campos para execução
     * @return string
     */
    private function mountFields(): string {
        $fields = '';
    
        if (!empty($this->select) && count($this->select) > 0) {
            foreach ($this->select as $val) {
                $fields .= $val . ',';
            }
        } else {
            $fields = '* ';
        }
    
        $fields = substr($fields, 0, -1);
        
        return $fields;
    }
    
    /**
     * Função que monta os join para execução
     * @return string
     */
    private function mountJoin(): string {
        $join = '';
    
        if (!empty($this->join)) {
            foreach ($this->join as $val) {
                $join .= $val . ' ';
            }
        }
        
        return $join;
    }
    
    /**
     * Função que monta o where para execução
     * @return string
     */
    private function mountWhere(): string {
        $i     = 0;
        $where = '';
        
        if (!empty($this->where)) {
            foreach ($this->where as $val) {
                if ($i != 0) {
                    $where .= ' and ';
                }
                
                $where .= $val;
                $i++;
            }
            
            $where = " where " . $where;
        }
        
        return $where;
    }
    
    /**
     * Função que monta os order by para execução
     * @return string
     */
    private function moundOrderBy(): string {
        $i = 0;
        $orderBy = '';
        
        if (!empty($this->order)) {
            foreach ($this->order as $key => $val) {
                if ($i != 0) {
                    $orderBy .= ', ';
                }
            
                if (is_numeric($key)) {
                    $orderBy .= $val;
                } else {
                    $orderBy .= $key . ' ' . $val;
                }
                
                $i++;
            }
            $orderBy = " order by " . $orderBy;
        }
        
        return $orderBy;
    }
    
    /**
     * Função que monta os group by para execução
     * @return string
     */
    private function mountGroupBy(): string {
        $i       = 0;
        $groupBy = '';
        
        if (!empty($this->group)) {
            foreach ($this->group as $val) {
                if ($i != 0) {
                    $groupBy .= ', ';
                }
                
                $groupBy .= $val;
                $i++;
            }
            
            $groupBy = " group by " . $groupBy;
        }
        
        return $groupBy;
    }
    
    /**
     * Função que monta os set para execução
     * @return string
     */
    private function mountSet(): string {
        $set = '';
    
        foreach ($this->set as $val) {
            $set .= $val . ',';
        }
    
        $set = substr($set, 0, -1);
        
        return $set;
    }
}
