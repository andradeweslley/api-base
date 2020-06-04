<?php

/**
 * Classe ApiResponse
 * Possui funções para auxiliar respostas da API
 * @author Weslley Andrade
 */
class ApiResponse {
    /**
     * Função que realizará o retorno da API
     * @param array $responseData     Dados a serem retornados
     * @param int   $httpCodeResponse Código de resposta HTTP
     */
    public static function handleResponse(array $responseData = null, int $httpCodeResponse = 200): void {
        http_response_code($httpCodeResponse);
        
        if (!empty($responseData)) {
            // @todo: Devolver retorno como XML ou JSON de acordo com header accept
            if (ApiHandle::$headers['accept']) {
                // ...
            }
            
            echo json_encode($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        
        exit;
    }
    
    /* REGION: Tratativas de sucesso */
    
    /**
     * Função que vai retornar organizadamente mensagem de sucesso para o frontend
     * @param string $type             Identificação do erro
     * @param string $message          Mensagem com detalhes do erro
     * @return array
     */
    public static function handleSuccessResponse(
        string $type,
        string $message): array
    {
        $message = [
            'status'  => true,
            'type'    => $type,
            'message' => $message,
        ];
        
        return $message;
    }
    
    /* ENDREGION: Tratativas de sucesso */
    
    /* REGION: Tratativas de erros */
    
    /**
     * Função que vai retornar organizadamente os erros para o frontend
     * @param int    $httpCodeResponse Código de resposta HTTP
     * @param string $type             Identificação do erro
     * @param string $message          Mensagem com detalhes do erro
     * @param array  $additionalData   Informações adicionais do erro
     * @throws ApiException
     */
    public static function handleErrorsResponse(
        int $httpCodeResponse,
        string $type,
        string $message,
        array $additionalData = []): void
    {
        $message = [
            'status'  => false,
            'type'    => $type,
            'message' => $message,
        ];
        
        if (!empty($additionalData)) {
            $message = array_merge($message, $additionalData);
        };
    
        throw new ApiException($message, $httpCodeResponse);
    }
    
    /**
     * Função default que retorna erro de recurso não encontrado
     * @throws ApiException
     */
    public static function returnResponseResourceNotFound(): void {
        self::handleErrorsResponse(
            404,
            'ResourceNotFound',
            'Recurso não encontrado'
        );
    }
    
    /**
     * Função default que retorna erro de método não permitido
     * @throws ApiException
     */
    public static function returnResponseMethodNotAllowed(): void {
        self::handleErrorsResponse(
            405,
            'MethodNotAllowed',
            'Método não permitido'
        );
    }
    
    /**
     * Função default que retorna erro de método não permitido
     * @throws ApiException
     */
    public static function returnResponseResourceIdNotSent(): void {
        self::handleErrorsResponse(
            400,
            'ResourceIdNotSent',
            'Id do recurso não foi enviado'
        );
    }
    
    /**
     * Função default para tratamento de erros de execução no banco
     * @param string $function     Função que disparou o erro
     * @param string $sql          SQL executado
     * @param array  $errorDetails Dados de erro
     * @throws ApiException
     */
    public static function returnResponseQueryError(
        string $function,
        string $sql,
        array $errorDetails): void
    {
        $details = ['details' => ['type' => $function]];
        if (DEBUG) {
            $details['details'] = [
                'error' => $errorDetails,
                'sql' => $sql
            ];
        }
        
        self::handleErrorsResponse(
            500,
            'QueryError',
            'Houve um erro na comunicação com o banco de dados',
            $details
        );
    }
    
    /* ENDREGION: Tratativas de erros */
}