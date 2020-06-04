<?php

/**
 * Definas os recursos liberados para acessar o sistema sem
 * precisar de autenticação.
 * Pode ser informado parametros também. Ex: users/:id/drink
 */
$allowedResources = [
    [
        'path'    => '/login/',
        'methods' => ['POST'],
    ],
    [
        'path'    => '/users',
        'methods' => ['POST'],
    ],
];