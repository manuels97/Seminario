<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../database.php';

class ReservasController {
    public function agregarReserva(Request $request, Response $response) {
        $connection = getConnection();
        $data = $request->getParsedBody(); // Obtener los datos enviados en el cuerpo de la solicitud
    
        // Verificar que todos los datos requeridos estén presentes
        $requiredFields = ['propiedad_id', 'inquilino_id', 'fecha_desde', 'cantidad_noches'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $payload = codeResponseGeneric("El campo '$field' es requerido.", 400);
                return responseWrite($response, $payload);
            }
        }
    
       // Verificar si el inquilino está inquilinoActivo
    if (!inquilinoActivo($data['inquilino_id'])) {
        $payload = codeResponseGeneric("El inquilino especificado no está activo.", 400);
        return responseWrite($response, $payload);
    }

    // Verificar si la propiedad está disponible
    if (!propiedadDisponible($data['propiedad_id'])) {
        $payload = codeResponseGeneric("La propiedad especificada no está disponible.", 400);
        return responseWrite($response, $payload);
    }

    // Verificar si la fecha de inicio es válida (fecha_desde es menor a la fecha actual)
    $fecha_desde = strtotime($data['fecha_desde']);
    if ($fecha_desde === false || $fecha_desde < time()) {
        $payload = codeResponseGeneric("La fecha de inicio de la reserva debe ser una fecha futura válida.", 400);
        return responseWrite($response, $payload);
    }
        // Calcular el valor total de la reserva
        $valor_por_noche = obtenerValorPropiedadPorNoche($data['propiedad_id']);
        if ($valor_por_noche === false) {
            $payload = codeResponseGeneric("No se pudo obtener el valor por noche de la propiedad.", 500);
            return responseWrite($response, $payload);
        }
        $valor_total = $valor_por_noche * $data['cantidad_noches'];
    
        // Insertar la reserva en la base de datos
        $sql = "INSERT INTO reservas (propiedad_id, inquilino_id, fecha_desde, cantidad_noches, valor_total) 
                VALUES (:propiedad_id, :inquilino_id, :fecha_desde, :cantidad_noches, :valor_total)";
        $values = [
            ':propiedad_id' => $data['propiedad_id'],
            ':inquilino_id' => $data['inquilino_id'],
            ':fecha_desde' => $data['fecha_desde'],
            ':cantidad_noches' => $data['cantidad_noches'],
            ':valor_total' => $valor_total
        ];
    
        try {
            $query = $connection->prepare($sql);
            $query->execute($values);
            $payload = codeResponseGeneric('Reserva creada correctamente.', 201);
            return responseWrite($response, $payload);
        } catch (\PDOException $e) {
            $payload = codeResponseGeneric('Error al crear la reserva.', 500);
            return responseWrite($response, $payload);
        }
    }
    

    //GET

    public function listar (Request $request, Response $response) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             // Realiza la consulta SQL
             $query = $connection->query('SELECT * FROM reservas');
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json    
             $payload = codeResponseOk($tipos);
             // funcion que devulve y muestra la respuesta 
             return responseWrite($response, $payload);
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }

}