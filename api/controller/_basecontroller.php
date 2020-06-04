<?php

/**
 * Classe de exemplo como montar as funções de um recurso na API
 * @author Weslley Andrade
 */
class Example
{
    /*
     * A classe precisa dos tipos de dados
     * que vai receber os parametros de URI
     * Exemplo:
     *  URI: /usuarios/1
     * Sendo o recurso na posição 1 (usuarios) e o
     *  id na posição 2 (1)
    */
    const RESOUCE = 1;
    const ID_OR_ACTION = 2;
    const SUBACTION = 3;
    
    /*
     * Variável que vai salvar qual o método HTTP
     * está sendo executado
     */
    private $method;
    
    /*
     * A classe vai precisar tratar as informações que são enviados pelo corpo da requisição usando
     * os parâmetros abaixo. A raiz do array deve ser o nome do campo recebido na requisição.
     * Campos com (*) são obrigatórios.
     *
     * ________________________________________________________________________________________________________________
     * | CAMPO         | INFORMAR COMO    | DESCRIÇÃO
     * | required(*)   | Booleano         | Se a informação é obrigatória.
     * | type(*)       | Texto            | Tipo da informação que é esperado, sendo os tipos: string, integer,
     * |               |                  | float, datetime, date, time, bool, email.
     * | methods(*)    | Array de texto   | Métodos permitidos para a tratativa da informação. Possíveis métodos:
     * |               |                  | GET, POST, PUT, PATCH
     * | dbTable       | Texto            | Tabela do banco que será salvo/atualizado a informação.
     * | dbField       | Texto            | Campo no banco que será salvo/atualizado a informação.
     * | minLength     | Inteiro          | Tamanho mínimo de caracteres que a informação deve possuir. Aplicavél
     * |               |                  | apenas nos campos do tipo string e integer.
     * | length        | Inteiro ou texto | Tamanho máximo de caracteres que a informação pode ter. Aplicavél
     * |               |                  | apenas nas informação do tipo string, integer e float. Quando for float
     * |               |                  | informar as casas decimais separado por vírgula. Exemplo: '10,2'.
     * | default       | Usar pelo type   | Valor padrão que a informação deve assumir quando não informado.
     * | allowedValues | Array de texto   | Valores que são esperados para aquela informação.
     * | value         | Nulo             | Nesse campo será armazenado a informação da forma que deverá ser
     * |               |                  | utilizada pelo sistema.
     *
    */
    private $resourceFields = [
        'fieldname' => [
            'required' => true,
            'type' => 'string',
            'methods' => ['GET', 'POST'],
            'dbTable' => 'exemplo_table',
            'dbField' => 'exemplo_field',
            'minLength' => 1,
            'length' => 50,
            'default' => 'teste',
            'allowedValues' => ['teste', 'exemplo'],
            'value' => null,
        ],
    ];
    
    /**
     * Função responsável por gerenciar a requisição da API RESTFul.
     * @param string $method  Método da requisição
     * @param array  $body    Corpo da requisição
     * @param array  $args    URI da requisição
     * @throws ApiException Handler de erros
     * @return array Retona array com os dados
     */
    public function start(string $method, array $body, array $args): array {
        $this->method = $method;
        $idOrAction   = $args[self::ID_OR_ACTION];
        $rawData      = false;
        
        switch ($method) {
            case 'GET':
                $rawData = $this->get();
                
                break;
            case 'POST':
                if (empty($idOrAction)) {
                    $rawData = $this->post($body);
                } else if ($idOrAction == 'teste') {
                    $rawData = $this->teste($body);
                }
                
                break;
            case 'PUT':
                if (empty($idOrAction)) {
                    ApiResponse::returnResponseResourceIdNotSent();
                } else {
                    $rawData = $this->put($body, $idOrAction);
                }
                
                break;
            case 'PATCH':
                if (empty($idOrAction)) {
                    ApiResponse::returnResponseResourceIdNotSent();
                } else {
                    $rawData = $this->patch($body, $idOrAction);
                }
                
                break;
            case 'DELETE':
                if (empty($idOrAction)) {
                    ApiResponse::returnResponseResourceIdNotSent();
                } else {
                    $rawData = $this->delete($body, $idOrAction);
                }
    
                break;
            default:
                ApiResponse::returnResponseMethodNotAllowed();
                break;
        }
        
        return $rawData;
    }
}
