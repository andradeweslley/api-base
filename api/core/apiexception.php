<?php

/**
 * Classe ApiException
 * Classe de exceção personalizada
 * @author Weslley Andrade
 */
class ApiException extends Exception
{
    /**
     * ApiException constructor.
     * @param array $data Dados do erro a ser retornado
     * @param int   $code Código do erro
     */
    public function __construct(array $data, int $code = 0) {
        parent::__construct(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            $code,
            null
        );
    }
    
    /**
     * Personaliza a apresentação do objeto como string
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}