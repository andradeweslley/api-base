# Descrição da API
Objetivo de facilitar o desenvolvimento e validação dos dados para uma API RESTFul.

## Configuração da API
Na pasta `/api/config` existe o arquivo `database.php` nele coloque as informações de conexão ao banco de dados.

## Execução da API
A API foi desenvolvida no modelo RESTFul para execução utilize `http://[host]/api/[recurso]`

## Autenticação
O token de autenticação deve ser obtido no recurso `/login`. Informe o `token` retornado no header `Authorization`

## Paginação
A paginação funciona pelos query params `offset` e `limit`. Quando eles não são informados o sistema assume os valores 
`0` e `100` respectivamente. Para o frontend calcular o total de páginas, a informação de quantos registros estão no 
header `X-Total-Count`.

## Filtros
Para filtragem pode-se usar o LHS Brackets. 
(https://www.moesif.com/blog/technical/api-design/REST-API-Design-Filtering-Sorting-and-Pagination/)

## Recursos da API 
A API possui os seguintes recursos default: 

### POST /start
Responsável por criar tabelas no banco de dados.

### POST /login 
Responsável por realizar o login do usuário.

#### Informações esperadas:
* email (string)
* password (string)

### POST /users
Responsável por criar usuários.

##### Informações esperadas
* email (string)
* name (string)
* password (string)

### GET /users *(requer autenticação)*
Responsável por retornar usuários cadastrados no sistema.

### PUT /users *(requer autenticação)*
Reponsável por atualizar dados do usuário.

#### Informações que podem ser enviadas:
* email (string)
* name (string)
* password (string)

### DELETE /users *(requer autenticação)*
Reponsável por remover o usuário do sistema.

### GET /users/:id *(requer autenticação)*
Responsável por retornar dados do usuário de um ID específico