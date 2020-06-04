<?php

/**
 * Classe utils
 * Possui funções de auxilio ao sistema
 * @author Weslley Andrade
 */
class Utils
{
    /* REGION: Funções para validações */
    
    /**
     * Função que faz tratativa dos campos recebidos no request
     * @param array  $params       Parametros de validação definido no recurso
     * @param array  $dataReceived Dados a serem tratados
     * @param string $method       Método recebido
     * @return array        Retorna array com os valores formatados
     * @throws ApiException Dispara quando há erros nos campos
     */
    public static function handleDataReceived(array $params, string $method, array $dataReceived = []): array {
        $errors    = [];
        $newParams = $params;
        
        foreach ($params as $field => $param) {
            if (!empty($param['methods']) && in_array($method, $param['methods']) === false) {
                continue;
            }
            
            if (!array_key_exists($field, $dataReceived)
                || $dataReceived[$field] === null
                || trim($dataReceived[$field]) === '')
            {
                if (!array_key_exists('default', $param) && $param['required'] && $method !== 'PUT') {
                    $errors[$field][] = 'Campo nulo ou vazio';
                } else if (array_key_exists('default', $param)) {
                    $newParams[$field]['value'] = $param['default'];
                } else {
                    $newParams[$field]['value'] = null;
                }
            } else {
                $data = $dataReceived[$field];
    
                switch ($param['type']){
                    case 'string':
                        if(trim($data) != '') {
                            if (!empty($param['minLength']) && strlen($data) < $param['minLength']) {
                                $errors[$field][] = 'Campo menor que ' . $param['minLength']
                                    . ' caracteres. Valor informado: ' . $data;
                            }
                
                            if (!empty($param['length']) && strlen($data) > $param['length']) {
                                $errors[$field][] = 'Campo maior que ' . $param['length']
                                    . ' caracteres. Valor informado: ' . $data;
                            }
                        }
            
                        break;
                    case 'integer':
                        if(trim($data) != '') {
                            if (!empty($param['minLength']) && strlen($data) < $param['minLength']) {
                                $errors[$field][] = 'Campo menor que ' . $param['minLength']
                                    . ' digitos. Valor informado: ' . $data;
                            }
    
                            if (!empty($param['length']) && strlen($data) > $param['length']) {
                                $errors[$field][] = 'Campo maior que ' . $param['length']
                                    . ' digitos. Valor informado: ' . $data;
                            }
                        }
            
                        if(!is_numeric($data)){
                            $errors[$field][] = 'Campo deve ser do tipo numérico';
                        }
            
                        break;
                    case 'float':
                        // Formata valor para adequar a forma correta de trativa
                        $data = self::FixValueToFloat($data);
                        $lengthParams = explode(',', $param['length']);
            
                        if(strlen($data) > ($lengthParams[0] + $lengthParams[1] + 1)){
                            $errors[$field][] = 'Campo maior que ' . $param['length'];
                        }
                        
                        $data = number_format(
                            (float) $data,
                            $lengthParams[1],
                            '.',
                            ''
                        );
            
                        break;
                    case 'datetime':
                        $data = self::CheckDateTime($data);
            
                        if($data === false){
                            $errors[$field][] = 'Campo deve ser no formato data e hora conforme ISO 8601';
                        }
            
                        break;
                    case 'date':
                        $data = self::CheckDate($data);
            
                        if($data === false){
                            $errors[$field][] = 'Campo deve ser do formato data conforme ISO 8601';
                        }
            
                        break;
                    case 'time':
                        $data = self::CheckTime($data);
            
                        if($data === false){
                            $errors[$field][] = 'Campo deve ser do tipo hora conforme ISO 8601';
                        }
            
                        break;
                    case 'bool':
                        if(in_array(strtolower($data),['true', '1', 's', 'y'])){
                            $data = true;
                
                        } else if(in_array(strtolower($data),['false', '0', 'n'])){
                            $data = false;
                
                        } else {
                            $errors[$field][] = 'Campo deve ser do tipo booleano';
                
                        }
            
                        break;
                    case 'email':
                        if (filter_var($data, FILTER_VALIDATE_EMAIL) === false) {
                            $errors[$field][] = 'Campo deve ser um e-mail válido';
                        }
                        
                        break;
                    default:
                        break;
                }
    
                if (!empty($param['allowedValues'])
                    && in_array($data, $param['allowedValues']) === false)
                {
                    $errors[$field][] = 'Campo com valor inesperado. Os valores esperados são: '
                        . implode(', ', $param['allowedValues']);
                }
                
                $newParams[$field]['value'] = $data;
            }
        }
        
        if (!empty($errors)) {
            ApiResponse::handleErrorsResponse(
                400,
                'InvalidParameters',
                'Há erros nos campos informados',
                ['description' => $errors]
            );
        }
        
        return $newParams;
    }
    
    /**
     * Função que verifica se a data e hora é válida
     * @param string $date Data e hora a ser validada
     * @return bool|string Retorna data e hora formatada em Y-m-d H:i:s se verdadeiro
     */
    public static function CheckDateTime(string $date) {
        $newDate = strtotime($date);
        
        if ($newDate === false) {
            return false;
        } else {
            return date('Y-m-d H:i:s', $newDate);
        }
    }
    
    /**
     * Função que verifica se a data é válida
     * @param string $date Data a ser validada
     * @return bool|string Retorna a data formatada em Y-m-d se verdadeiro
     */
    public static function CheckDate(string $date) {
        $newDate = strtotime($date);
        
        if ($newDate === false) {
            return false;
        } else {
            return date('Y-m-d', $newDate);
        }
    }
    
    /**
     * Função que verifica se a hora é valida
     * @param string $date Hora a ser validada
     * @return bool|string Retorna a hora formatada em H:i:s se verdadeiro
     */
    public static function CheckTime(string $date) {
        $newDate = strtotime($date);
        
        if ($newDate === false) {
            return false;
        } else {
            return date('H:i:s', $newDate);
        }
    }
    
    /**
     * Função que corrige numero para decimal
     * @param string $value Valor a ser corrigido
     * @return string Retorna valor corrigido
     */
    public static function FixValueToFloat(string $value): string {
        if (strlen($value) > 3) {
            $value = preg_replace('/[^0-9.,]/', '', $value);
            
            $pt1 = (string) substr($value, -3);
            $pt2 = (string) substr($value, 0, -3);
            
            $pt1 = str_replace(',', '.', $pt1);
            $pt2 = preg_replace('/[^0-9]/', '', $pt2);
            
            $value = $pt2.$pt1;
        } else {
            $value = str_replace("R|S|\$,", "", $value);
        }
        
        
        if (strpos($value, ".") > 0) {
            if (strlen(substr($value, strpos($value, ".") + 1, 2)) == 1) {
                $value = $value . "0";
            }
        }
        
        if (strpos($value, ".") > 0) {
            if (strlen(substr($value, strpos($value, ".") + 1, 2)) == 0) {
                $value = $value . "00";
            }
        }
        
        if (strpos($value, ".") == 0) {
            $value = $value . ".00";
        }
        
        return $value;
    }
    
    /**
     * Função que encripita uma string em sha256
     * @param string $string
     * @return string
     */
    public static function encript(string $string): string {
        return hash('sha256', $string);
    }
    
    /**
     * Função que extrai os valores de um array de parâmetros do recurso da API
     * @param array $params Parametros com valores a serem extraídos
     * @return array
     */
    public static function extractValues(array $params): array {
        $newParams = [];
        
        foreach ($params as $key => $value) {
            if (isset($value['value'])) $newParams[$key] = $value['value'];
        }
        
        return $newParams;
    }
    
    /* ENDREGION: Funções para validações */
}