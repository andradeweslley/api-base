<?php

/**
 * Não modificar
 */
define('PRODUCTION', 1);
define('HOMOLOGATION', 2);
define('TESTS', 3);

/**
 * Informe a URL de cada ambiente
 */
define('HOST_PRODUCTION', 'URL_PRODUCAO');
define('HOST_HOMOLOGATION', 'URL_HOMOLOGACAO');
define('HOST_TESTS', 'localhost:8080');

/**
 * Informe a chave secreta de validação do JWT
 */
define('JWT_SECRET', '3n4iCl5eRgOjaL9m6UQ7o1v0BJszc8bT2GfpudkrxYXqhPyEtWwKIMDNVHZSFA');