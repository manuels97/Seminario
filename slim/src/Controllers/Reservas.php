<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../database.php';


class ReservasController {
    private function obtenerValorPropiedadPorNoche($propiedadId) {
        $connection = getConnection();
        $sql = "SELECT valor_noche FROM propiedades WHERE id = :id";
        $query = $connection->prepare($sql);
        $query->execute([':id' => $propiedadId]);
        $result = $query->fetch();
        return $result['valor_noche'];
    }
    private function inquilinoActivo($inquilinoId, &$mensaje) {
        $connection = getConnection();
        $sql = "SELECT * FROM inquilinos WHERE id = :id AND activo = 1";
        $query = $connection->prepare($sql);
        $query->execute([':id' => $inquilinoId]);
        $result = $query->fetch();
         if ($result['activo'] == 0 || !$result) {
            $mensaje="El inquilino especificado no está activo";
            return false;
        } else {
            // Si el inquilino existe y está activo
            return true;
        }
    } 
    private function propiedadDisponible($propiedadId,&$mensaje) {
        $connection = getConnection();
        $sql = "SELECT disponible FROM propiedades WHERE id = :propiedad_id ";
        $query = $connection->prepare($sql);
        $query->execute([':propiedad_id' => $propiedadId]);
        $result = $query->fetch();
        if (!$result) {
            $mensaje="No existe esta propiedad";
            return false;
        } else if ($result['disponible'] == 0) {
            $mensaje="La propiedad especificada no está disponible";
            return false;
        } else {
            return true;
        }
    }

    // POST
    public function agregarReserva(Request $request, Response $response) {
        $connection = getConnection();
        $data = $request->getParsedBody(); // Obtener los datos enviados en el cuerpo de la solicitud
        // Verificar que todos los datos requeridos estén presentes
        $requiredFields = ['propiedad_id', 'inquilino_id', 'fecha_desde', 'cantidad_noches'];
        function faltanDatos($requiredFields, $datos) {
            foreach ($requiredFields as $campo) {
                if (!isset($datos[$campo])) {
                    $status = 'Error';
                    $mensaje = 'Falta el campo ' . $campo; 
                    return codeResponseGeneric($status, $mensaje, 400);
                }
            }
            return null; // No faltan datos
        }
        $payload=faltanDatos($requiredFields,$data);
        if (isset($payload)) {
            return responseWrite($response,$payload);
        }

        // aca compruebo que el inquilino este activo y la propiedad esté disponible

        if (!$this->inquilinoActivo($data['inquilino_id'], $mensaje)) {
            $payload = codeResponseGeneric("Error",$mensaje,400);
            return responseWrite($response, $payload);
        }
        if (!$this->propiedadDisponible($data['propiedad_id'], $mensaje)) {
            $payload = codeResponseGeneric("Error",$mensaje,400);
            return responseWrite($response, $payload);
        }
        $valor_por_noche = $this->obtenerValorPropiedadPorNoche($data['propiedad_id']);

        if(empty($valor_por_noche)) {
            $status='Error';$mensaje="Error en la variable valor por noche.";
            $payload=codeResponseGeneric($status,$mensaje,400);
            return responseWrite($response,$payload);
        }
        $valor_total = $valor_por_noche * $data['cantidad_noches'];
        try {
            $sql = "INSERT INTO reservas (propiedad_id, inquilino_id, fecha_desde, cantidad_noches, valor_total) 
                    VALUES (:propiedad_id, :inquilino_id, :fecha_desde, :cantidad_noches, :valor_total)";
            $values = [
                ':propiedad_id' => $data['propiedad_id'],
                ':inquilino_id' => $data['inquilino_id'],
                ':fecha_desde' => $data['fecha_desde'],
                ':cantidad_noches' => $data['cantidad_noches'],
                ':valor_total' => $valor_total
            ];
            $query = $connection->prepare($sql);
            $query->execute($values);
            $payload = codeResponseGeneric('Reserva creada correctamente.', "Created", 201);
            return responseWrite($response, $payload);
        } catch (\PDOException $e) {
            $payload = codeResponseGeneric('Error al crear la reserva.', "Internal Server Error", 500);
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
    
    // DELETE 
    public function eliminarReserva (Request $request, Response $response, $args){
        $id = $args['id'];
        if (!is_numeric($id)) {
                $status = 'Error';
                $mensaje = 'ID NO VALIDO';
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
        }
        try {
            $connection=getConnection();
            $query= $connection ->query("SELECT id,fecha_desde FROM reservas WHERE id=$id LIMIT 1");
            if($query->rowCount()>0){
                $reserva= $query->fetch(\PDO::FETCH_ASSOC);
                $fecha_incio= $reserva['fecha_desde']; 
                $fecha_actual= date('Y-m-d');
                if($fecha_actual<$fecha_incio){
                    $query=$connection->prepare('DELETE FROM reservas WHERE id=:id');
                    $query->bindValue(':id',$id);   
                    $query->execute();
                    $mensaje='Eliminado correctamente.'; $status="Success"; $payload=codeResponseGeneric($status,$mensaje,200);
                    return responseWrite($response,$payload);
                } else {
                    $mensaje='La reserva ya inicio.'; $status='Error'; 
                    $payload=codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                }
            } else {
                $status='ERROR'; $mensaje='No se encuentra la reserva con el ID proporcionado';
                $payload= codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }

        }
        catch (\PDOException $e){
             $payload= codeRespondeBad();
             return responseWrite($response,$payload);
        }
    }
    //PUT
    public function editarReserva(Request $request, Response $response, $args) {
        $connection = getConnection();
        $data = $request->getParsedBody(); // Obtener los datos enviados en el cuerpo de la solicitud
        $id = $args['id'];
        if (!is_numeric($id)) {
                $status = 'Error';
                $mensaje = 'ID NO VALIDO';
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
        }
        // Verificar que todos los datos requeridos estén presentes
        $requiredFields = ['propiedad_id', 'inquilino_id', 'fecha_desde', 'cantidad_noches'];
        $payload=faltanDatos($requiredFields,$data);
        if (isset($payload)) {
            return responseWrite($response,$payload);
        }
        // Verificar si la fecha de inicio ya pasó
        try {
            $query = $connection->query("SELECT fecha_desde FROM reservas WHERE id=$id LIMIT 1");
            if ($query->rowCount() > 0) {
                $reserva = $query->fetch(\PDO::FETCH_ASSOC);
                $fechaInicio = $reserva['fecha_desde'];
                $fechaActual = date('Y-m-d');
                if ($fechaActual < $fechaInicio) {
                    // La reserva aún no ha comenzado, se permite la edición
                    // Verificar si el inquilino existe
                    $queryInquilino = $connection->prepare("SELECT * FROM inquilinos WHERE id = :inquilino_id");
                    $queryInquilino->execute([':inquilino_id' => $data['inquilino_id']]);
                    if($queryInquilino->rowCount()==0) {
                        $status='Error'; $mensaje='No existe el inquilino con ese ID'; 
                        $payload=codeResponseGeneric($status,$mensaje,400);
                        return responseWrite($response,$payload);
                    } 
                    // Verificar si la propiedad existe
                    $queryPropiedad = $connection->prepare("SELECT * FROM propiedades WHERE id = :propiedad_id");
                    $queryPropiedad->execute([':propiedad_id' => $data['propiedad_id']]);
                    if($queryPropiedad->rowCount()==0) {
                        $status='Error'; $mensaje='No existe la propiedad con ese ID'; 
                        $payload=codeResponseGeneric($status,$mensaje,400);
                        return responseWrite($response,$payload);
                    } 
                    // Actualizar la reserva en la base de datos
                    $sql = "UPDATE reservas 
                            SET propiedad_id = :propiedad_id, 
                                inquilino_id = :inquilino_id, 
                                fecha_desde = :fecha_desde, 
                                cantidad_noches = :cantidad_noches,
                                valor_total = :valor_total
                            WHERE id = :id";

                    $valor_por_noche = $this->obtenerValorPropiedadPorNoche($data['propiedad_id']);
                    $valor_total = $valor_por_noche * $data['cantidad_noches'];

                    $values = [
                        ':id' => $id,
                        ':propiedad_id' => $data['propiedad_id'],
                        ':inquilino_id' => $data['inquilino_id'],
                        ':fecha_desde' => $data['fecha_desde'],
                        ':cantidad_noches' => $data['cantidad_noches'],
                        ':valor_total' => $valor_total,
                    ];
                    $query = $connection->prepare($sql);
                    $query->execute($values);
                    $payload = codeResponseGeneric("Reserva actualizada correctamente.", "OK", 200);
                    return responseWrite($response, $payload);
                    
                } else {
                    // La reserva ya ha comenzado, no se permite la edición
                    $payload = codeResponseGeneric("La reserva ya ha comenzado y no puede ser editada.", "Bad Request", 400);
                    return responseWrite($response, $payload);
                }
            } else {
                // No se encuentra la reserva con el ID proporcionado
                $payload = codeResponseGeneric("No se encuentra la reserva con el ID proporcionado.", "Not Found", 404);
                return responseWrite($response, $payload);
            }
        } catch (\PDOException $e) {
            // Error de base de datos
            $payload = codeResponseGeneric("Error de base de datos al buscar la reserva.", "Internal Server Error", 500);
            return responseWrite($response, $payload);
        }
    }
}