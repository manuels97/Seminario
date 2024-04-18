<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
 // conexion a la base de datos. 
 function getConnection() {
    $dbhost = "db";
    $dbname = "seminariophp";
    $dbuser = "seminariophp";
    $dbpass = "seminariophp";
    $connection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $connection;
}
// function para enviar respuesta OK
function codeResponseOk ($tipos) {
    return  json_encode([
        'status' => 'success',
        'code' => 200 ,
        'datos:' => $tipos
    ]);
}
// funcion generica
function codeResponseGeneric ($status,$mensaje, $code) {
    return  json_encode([
        'Status: '=> $status,
        'mensaje: ' => $mensaje,
        'code: ' => $code
    ]);
}
// function para enviar respuesta Error
function codeResponseBad() {
    return  json_encode([
        'status' => 'Error', 
        'code' => 404
    ]);
    
}
// function para mostrar los errores.
function responseWrite(Response $response , $payload) {
    $response -> getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
}
?>