<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../../database.php';

class PropiedadesController{
        
    //POST
    public function crearPropiedad(Request $request, Response $response) {
        $connection = getConnection();
        $data = $request->getParsedBody();
    
        // Validar campos requeridos
        $requiredFields = ['domicilio', 'localidad_id', 'cantidad_habitaciones', 'cantidad_banios', 'cochera', 'cantidad_huespedes', 'fecha_inicio_disponibilidad', 'cantidad_dias', 'disponible', 'valor_noche', 'moneda_id', 'tipo_propiedad_id', 'imagen', 'tipo_imagen'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $status = 'Error'; 
                $mensaje = "El campo $field es requerido"; 
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
            }
        }
    
        try {
            // Insertar la nueva propiedad en la base de datos
            $query = $connection->prepare("INSERT INTO propiedades (domicilio, localidad_id, cantidad_habitaciones, cantidad_banios, cochera, cantidad_huespedes, fecha_inicio_disponibilidad, cantidad_dias, disponible, valor_noche, moneda_id, tipo_propiedad_id, imagen, tipo_imagen) VALUES (:domicilio, :localidad_id, :cantidad_habitaciones, :cantidad_banios, :cochera, :cantidad_huespedes, :fecha_inicio_disponibilidad, :cantidad_dias, :disponible, :valor_noche, :moneda_id, :tipo_propiedad_id, :imagen, :tipo_imagen)");
            $query->bindParam(':domicilio', $data['domicilio'], \PDO::PARAM_STR);
            $query->bindParam(':localidad_id', $data['localidad_id'], \PDO::PARAM_INT);
            $query->bindParam(':cantidad_habitaciones', $data['cantidad_habitaciones'], \PDO::PARAM_INT);
            $query->bindParam(':cantidad_banios', $data['cantidad_banios'], \PDO::PARAM_INT);
            $query->bindParam(':cochera', $data['cochera'], \PDO::PARAM_BOOL);
            $query->bindParam(':cantidad_huespedes', $data['cantidad_huespedes'], \PDO::PARAM_INT);
            $query->bindParam(':fecha_inicio_disponibilidad', $data['fecha_inicio_disponibilidad'], \PDO::PARAM_STR);
            $query->bindParam(':cantidad_dias', $data['cantidad_dias'], \PDO::PARAM_INT);
            $query->bindParam(':disponible', $data['disponible'], \PDO::PARAM_BOOL);
            $query->bindParam(':valor_noche', $data['valor_noche'], \PDO::PARAM_INT);
            $query->bindParam(':moneda_id', $data['moneda_id'], \PDO::PARAM_INT);
            $query->bindParam(':tipo_propiedad_id', $data['tipo_propiedad_id'], \PDO::PARAM_INT);
            $query->bindParam(':imagen', $data['imagen'], \PDO::PARAM_STR);
            $query->bindParam(':tipo_imagen', $data['tipo_imagen'], \PDO::PARAM_STR);
            $query->execute();
    
            // Obtener el ID de la nueva propiedad insertada
            $id = $connection->lastInsertId();
    
            // Respuesta de éxito
            $status = 'Success';
            $mensaje = 'Propiedad creada correctamente';
            $payload = ['id' => $id];
            $payload = codeResponseGeneric($status, $mensaje, 201, $payload);
            return responseWrite($response, $payload);
        } catch (\PDOException $e) {
            // Manejo de excepciones PDO
            $payload = codeResponseBad();
            return responseWrite($response, $payload);
        }
    }
    
    




    public function listar(Request $request, Response $response) {
        $connection = getConnection();
        $params = $request->getQueryParams(); //obtenemos los parámetros de consulta proporcionados en la URL
    
        // Construir la consulta SQL base sin ningun filtro
        $sql = "SELECT * FROM propiedades";
    
        // Aplicar filtros si se proporcionan
        $conditions = [];
        $values = []; //aca guardamos los parametros si hay
        if (isset($params['disponible'])) {
            $conditions[] = "disponible = :disponible";
            $values[':disponible'] = filter_var($params['disponible'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($params['localidad_id'])) {
            $conditions[] = "localidad_id = :localidad_id";
            $values[':localidad_id'] = $params['localidad_id'];
        }
        if (isset($params['fecha_inicio_disponibilidad'])) {
            $conditions[] = "fecha_inicio_disponibilidad = :fecha_inicio_disponibilidad";
            $values[':fecha_inicio_disponibilidad'] = $params['fecha_inicio_disponibilidad'];
        }
        if (isset($params['cantidad_huespedes'])) {
            $conditions[] = "cantidad_huespedes = :cantidad_huespedes";
            $values[':cantidad_huespedes'] = $params['cantidad_huespedes'];
        }
    
        // Combinar las condiciones
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
    
        try {
            // Ejecutar la consulta
            $query = $connection->prepare($sql);
            $query->execute($values);
            $propiedades = $query->fetchAll(\PDO::FETCH_ASSOC);
    
            // Respuesta exitosa con el listado de propiedades
            $status = 'Success';
            $mensaje = 'Listado de propiedades';
            $payload = ['propiedades' => $propiedades];
            $payload = codeResponseGeneric($mensaje, 200, $payload);
            return responseWrite($response, $payload);
        } catch (\PDOException $e) {
            // Manejo de excepciones PDO
            $payload = codeResponseBad();
            return responseWrite($response, $payload);
        }
    }


    //GET POR ID
    public function listarPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->query("SELECT * FROM propiedades WHERE id=$id");
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             if($tipos) {
                 $payload = codeResponseOk($tipos);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response, $payload);
            } else {
                $status='Error'; $mensaje='No se encontró ninguna propiead con el ID proporcionado.';
                $payload = codeResponseGeneric($status,$mensaje,400);
                // Devolver y mostrar la respuesta con el error
                return responseWrite($response, $payload);
            }
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    
    //PUT

    public function editarPropiedad(Request $request, Response $response, $args) {
        $connection = getConnection();
        $id_url = $args['id']; 
    
        // Validar ID numérico
        if(!is_numeric($id_url)) {
            $status = 'Error'; 
            $mensaje = 'ID no válido'; 
            $payload = codeResponseGeneric($status, $mensaje, 400);
            return responseWrite($response, $payload);
        }
    
        try {
            // Verificar si la propiedad existe
            $query = $connection->prepare("SELECT id FROM propiedades WHERE id=:id LIMIT 1");
            $query->bindParam(':id', $id_url, \PDO::PARAM_INT);
            $query->execute();
            if($query->rowCount() == 0) {
                $status = 'ERROR'; 
                $mensaje = 'No se encuentra el ID de la propiedad'; 
                $payload = codeResponseGeneric($status, $mensaje, 404);
                return responseWrite($response, $payload);
            }
    
            // Obtener datos de la solicitud
            $data = $request->getParsedBody();
    
            // Validar que los campos requeridos no esten vacios.
            $requiredFields = ['domicilio', 'localidad_id',  'cantidad_huespedes', 'fecha_inicio_disponibilidad', 'cantidad_dias', 'disponible', 'valor_noche', 'moneda_id', 'tipo_propiedad_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $status = 'Error'; 
                    $mensaje = "El campo $field es requerido"; 
                    $payload = codeResponseGeneric($status, $mensaje, 400);
                    return responseWrite($response, $payload);
                }
            }
    
            // Verificar si la propiedad ya existe en la base de datos. ¿Validio con domicilio?
            $propiedad = $data['domicilio'];
            $query = $connection->prepare("SELECT domicilio FROM propiedades WHERE domicilio = :propiedad");
            $query->bindParam(':propiedad', $propiedad, \PDO::PARAM_STR);
            $query->execute();
            if($query->rowCount() > 0) {
                $status = 'Error'; 
                $mensaje = 'La propiedad ya se encuentra en la base de datos'; 
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
            }
    
            // Actualizar la propiedad
            $query = $connection->prepare("UPDATE propiedades SET domicilio=:domicilio WHERE id=:id");
            $query->bindParam(':domicilio', $data['domicilio'], \PDO::PARAM_STR);
            $query->bindParam(':id', $id_url, \PDO::PARAM_INT);
            $query->execute();
    
            // Respuesta de éxito
            $status = 'Success';
            $mensaje = 'Propiedad editada correctamente';
            $payload = codeResponseGeneric($status, $mensaje, 200);
            return responseWrite($response, $payload);
        } catch (\PDOException $e) {
            // Manejo de excepciones PDO
            $payload = codeResponseBad();
            return responseWrite($response, $payload);
        }
    }
    
    //DELETE
    public function eliminarPropiedad(Request $request, Response $response, $args) {
        $connection = getConnection();
        $id_url = $args['id']; 
    
        // Validar ID numérico
        if(!is_numeric($id_url)) {
            $status = 'Error'; 
            $mensaje = 'ID no válido'; 
            $payload = codeResponseGeneric($status, $mensaje, 400);
            return responseWrite($response, $payload);
        }
    
        try {
            // Verificar si la propiedad existe
            $query = $connection->prepare("SELECT id FROM propiedades WHERE id=:id LIMIT 1");
            $query->bindParam(':id', $id_url, \PDO::PARAM_INT);
            $query->execute();
            if($query->rowCount() == 0) {
                $status = 'ERROR'; 
                $mensaje = 'No se encuentra el ID de la propiedad'; 
                $payload = codeResponseGeneric($status, $mensaje, 404);
                return responseWrite($response, $payload);
            }
    
            // Eliminar la propiedad
            $query = $connection->prepare("DELETE FROM propiedades WHERE id=:id");
            $query->bindParam(':id', $id_url, \PDO::PARAM_INT);
            $query->execute();
    
            // Respuesta de éxito
            $status = 'Success';
            $mensaje = 'Propiedad eliminada correctamente';
            $payload = codeResponseGeneric($status, $mensaje, 200);
            return responseWrite($response, $payload);
        } catch (\PDOException $e) {
            // Manejo de excepciones PDO
            $payload = codeResponseBad();
            return responseWrite($response, $payload);
        }
    }
    
}