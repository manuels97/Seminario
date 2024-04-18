<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../../database.php';

class TipoPropiedadesController {
    
    // GET /tipos_propiedad
    public function listar (Request $request, Response $response) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {
             // Realiza la consulta SQL
             $query = $connection->query('SELECT nombre FROM tipo_propiedades');
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             $payload = codeResponseOk($tipos);
             // funcion que devulve y muestra la respuesta 
             return responseWrite($response,$payload);
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    // POST /tipos_propiedad
    public function crearTipoPropiedad (Request $request, Response $response ) {
        $connection= getConnection();
        try {
            $data= $request-> getParsedBody();
            var_dump($data);
            if (isset($data['nombre']) && strlen($data['nombre'])){
                $nombre= $data['nombre'];
                $query = $connection->prepare("SELECT nombre FROM tipo_propiedades WHERE nombre = :nombre LIMIT 1");
                $query->bindValue(':nombre', $nombre, \PDO::PARAM_STR);
                $query->execute();
                if($query->rowCount()>0){
                    $status='Error'; $mensaje='Ya se encuentra dentro de la tabla el tipo de propiedad'; 
                    $payload=codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                } 
            } 
            else {
                $status='Error'; $mensaje='El campo nombre es requerido'; $payload= codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
            $query= $connection->prepare('INSERT INTO tipo_propiedades (nombre) VALUES (:nombre)');
            $query-> bindValue(':nombre',$nombre);  $query->execute();
            $status='Success'; $mensaje='Tipo propiedad agregado exitosamente'; $payload= codeResponseGeneric($status,$mensaje,200);
            return responseWrite($response,$payload);

        } catch (\PDOException $e) {
            $payload=codeResponseBad();
            return responseWrite($response,$payload);
        }
    }
 

    // PUT /tipos_propiedad/{id}
    public function editarTipoPropiedad(Request $request, Response $response, $args) {
        $connection = getConnection();
        try {
            $id = $args['id']; // Obtener el ID del tipo de propiedad de los argumentos de la URL
            // Verificar si el ID es numérico
            if (!is_numeric($id)) {
                $status = 'Error';
                $mensaje = 'ID NO VALIDO';
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
            } else {
                $data = $request->getParsedBody();  var_dump($data);
                // Verificar si el campo 'nombre' está presente y no está vacío
                if (isset($data['nombre']) && strlen($data['nombre']) ) {
                    $nombre = $data['nombre'];            
                    // Verificar si ya existe un tipo de propiedad con el nuevo nombre
                    $query = $connection->prepare("SELECT nombre FROM tipo_propiedades WHERE nombre = :nombre LIMIT 1");
                    $query->bindParam(':nombre', $nombre, \PDO::PARAM_STR);
                    $query->execute();

                    if ($query->rowCount() > 0) {
                        $status = 'Error';
                        $mensaje = 'Ya existe un tipo de propiedad con el nuevo nombre';
                        $payload = codeResponseGeneric($status, $mensaje, 400);
                        return responseWrite($response, $payload);
                    } else {
                        // Actualizar el nombre del tipo de propiedad en la base de datos
                        $query = $connection->prepare('UPDATE tipo_propiedades SET nombre = :nombre WHERE id = :id');
                        $query->bindValue(':nombre', $nombre, \PDO::PARAM_STR);
                        $query->bindValue(':id', $id, \PDO::PARAM_INT);
                        $query->execute();

                        $status = 'Success';
                        $mensaje = 'Nombre del tipo de propiedad actualizado exitosamente';
                        $payload = codeResponseGeneric($status, $mensaje, 200);
                        return responseWrite($response, $payload);
                    }
                } else {
                    // Si el campo 'nombre' no está presente o está vacío
                    $status = 'Error';
                    $mensaje = 'El campo nombre es requerido';
                    $payload = codeResponseGeneric($status, $mensaje, 400);
                    return responseWrite($response, $payload);
                }
            }
        } catch (\PDOException $e) {
            // Manejar excepciones de base de datos
            $payload = codeResponseBad();
            return responseWrite($response, $payload);
        }
    }   
    // DELETE /tipos_propiedad/{id}
    public function eliminarTipoPropiedad (Request $request, Response $response, $args) {
        $connection= getConnection();
        try { 
            $id= $args['id'];
            if (!is_numeric($id)) {
                $status='Error'; $mensaje='ID NO VALIDO';
                $payload=codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
            $query= $connection ->query("SELECT id FROM tipo_propiedades WHERE id=$id");
            if($query->rowCount() > 0) {
                $query= $connection -> query("SELECT tipo_propiedad_id FROM propiedades WHERE tipo_propiedad_id=$id LIMIT 1");
                if($query -> rowCount()>0) {
                    $status='error';   $mensaje='Esta localidad está siendo usada.';
                    $payload= codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                } else {
                    $query= $connection ->prepare("DELETE from tipo_propiedades WHERE id=:id ");
                    $query -> bindParam(':id', $id, \PDO::PARAM_INT);
                    $query->execute();
                    $status='Success';   $mensaje='Eliminado exitosamente';
                    $payload= codeResponseGeneric($status,$mensaje,200);
                    return responseWrite($response,$payload);
                }

            } else {
                $status='ERROR'; $mensaje='No se encuentra el ID';
                $payload= codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
        } 
        catch(\PDOException $e) {
            $payload= codeResponseBad();
            // devolvemos y mostramos la respuesta con el error.
            return responseWrite($response,$payload);
        }
    }
}
?>