<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../database.php';

class InquilinosController {

    // POST /inquilinos    
    public function crearInquilino (Request $request, Response $response) {
        $connection=getConnection();
        try{
            $datos= $request -> getParsedBody();
            if(isset($datos['id'],$datos['apellido'],$datos['nombre'],$datos['documento'],$datos['email'],$datos['activo'])) {
                    $id= $datos['id']; 
                    $query= $connection->query("SELECT id from inquilinos WHERE id=$id LIMIT 1");
                    if($query->rowCount()>0) {
                        $status='Error'; $mensaje='Ya existe un inquilino con ese ID';
                        $payload=codeResponseGeneric($status,$mensaje,400);
                        return responseWrite($response,$payload);
                    } else { 
                        $documento = $datos['documento'];
                        $query=$connection->query("SELECT documento FROM inquilinos WHERE documento=$documento LIMIT 1");
                        if($query->rowCount()>0){
                            $status='Error';$mensaje='Ya hay un documento registrado con ese valor'; 
                            $payload=codeResponseGeneric($status,$mensaje,400);
                            return responseWrite($response,$payload);
                        } else {
                            $query=$connection->prepare('INSERT INTO inquilinos (id,apellido,nombre,documento,email,activo) VALUES (:id,:apellido,:nombre,:documento,:email,:activo)');
                            $query->bindValue(':id',$datos['id']); $query->bindValue(':apellido',$datos['apellido']); $query->bindValue(':nombre',$datos['nombre']); $query->bindValue(':documento',$datos['documento']); $query->bindValue(':email',$datos['email']); $query->bindValue(':activo',$datos['activo']);
                            $query->execute();
                            $status='Success'; $mensaje='Inquilino agregado exitosamente'; $payload=codeResponseGeneric($status,$mensaje,200);
                            return responseWrite($response,$payload);
                        }
                    }
            } else {
                $status='Error'; $mensaje='Todos los campos son requeridos';
                $payload=codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
        } catch (\PDOException $e) {
            $payload=codeResponseBad();
            return responseWrite($response,$payload);

        }
    }
    // PUT /inquilinos/{id}   
    public function editarInquilino (Request $request, Response $response, $args){
        $connection= getConnection();
        $id_url= $args['id']; 
        if (!is_numeric($id_url)) {
            $status='Error'; $mensaje='ID invalido'; $payload=codeResponseGeneric($status,$mensaje,400);
            return responseWrite($response,$payload);
        }
        try {
            $query= $connection->query("SELECT id FROM inquilinos WHERE id=$id_url LIMIT 1");
            if($query->rowCount()==0) {
                $status='Error';$mensaje='No se encuntra el ID'; $payload=codeResponseGeneric($status,$mensaje,404);
                return responseWrite($response,$payload);
            }
            $datos= $request->getParsedBody();
            if (isset($datos['id'],$datos['apellido'],$datos['nombre'],$datos['documento'],$datos['email'],$datos['activo'])) {
                $idBody=$datos['id']; 
                $query=$connection->query("SELECT id FROM inquilinos WHERE id=$idBody  LIMIT 1");
                if($query->rowCount()>0 ){ 
                    // VERIFICAMOS QUE LA ID DE LA URL SEA DISTINTO AL DEL REGISTRO. YA QUE SI SON IGUALES ENTONCES QUEREMOS EDITAR EL MISMO REGISTRO 
                    // LA ID DE LA URL ES STR, LA DEL REGISTRO ENTERA, CONVERTIMOS Y COMPARAMOS
                    $idInt = (int)$args['id'];
                     var_dump($idBody);
                    if ($idInt !== $idBody) {
                        $status='Error'; $mensaje='El ID proporcionado ya se encuentra en uso'; $payload=codeResponseGeneric($status,$mensaje,400);
                        return responseWrite($response,$payload);
                    }
                }
                $documento=$datos['documento'];
                $query=$connection->query("SELECT documento FROM inquilinos where documento=$documento");
                if($query->rowCount()>0){
                    $status='Error'; $mensaje='El documento proporcionado ya se encuentra en uso'; $payload=codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                }
                
                $query=$connection->prepare("UPDATE inquilinos SET
                      id= :id,
                      apellido= :apellido,
                      nombre= :nombre,
                      documento= :documento,
                      email= :email,
                      activo= :activo
                WHERE id=:id 
                ");
                 $query->bindValue(':id',$datos['id']); $query->bindValue(':apellido',$datos['apellido']); $query->bindValue(':nombre',$datos['nombre']); $query->bindValue(':documento',$datos['documento']); $query->bindValue(':email',$datos['email']); $query->bindValue(':activo',$datos['activo']);
                 $query->execute();
                 $status='SUCCESS'; $mensaje='Inquilino editado exitosamente'; 
                 $payload=codeResponseGeneric($status,$mensaje,200);
                 return responseWrite($response,$payload);
            } else {
                $status='Error'; $mensaje='Todos los campos son requeridos.'; $payload=codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
        } catch (\PDOException $e) {
            $payload=codeResponseBad();
            return responseWrite($response,$payload);
        }
    }
    
    // GET /inquilinos
    public function listar (Request $request, Response $response) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             // Realiza la consulta SQL
             $query = $connection->query('SELECT * FROM inquilinos');
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
    // GET INQUILINOS/{ID}
    public function listarPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->query("SELECT * FROM inquilinos WHERE id=$id");
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             if($tipos) {
                 $payload = codeResponseOk($tipos);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response, $payload);
            } else {
                $status='Error'; $mensaje='No se encontró ningún inquilino con el ID proporcionado.';
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
     // GET inquilinos/{idInquilino}/reservas
    public function reservaPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->query("SELECT * FROM reservas WHERE inquilino_id=$id");
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             if($tipos) {
                 $payload = codeResponseOk($tipos);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response,$payload);
            } else {
                $status='Error'; $mensaje='No se encontró ninguna reserva con el ID proporcionado.';
                $payload = codeResponseGeneric($status,$mensaje,400);
                // Devolver y mostrar la respuesta con el error
                return responseWrite($response,$payload);
            }
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    // DELETE inquilinos/{id}
    public function eliminarPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
        $connection = getConnection(); 
       
        try {    
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->prepare("DELETE FROM inquilinos WHERE id = :id");
             $query -> bindParam (':id', $id, \PDO::PARAM_INT);
             $query-> execute();
             $filas_delete= $query->rowCount();
             // Preparamos la respuesta json 
             if($filas_delete>0) {
                $status='Success'; $mensaje='INQUILINO BORRADO EXITOSAMENTE';
                 $payload = codeResponseGeneric($status,$mensaje,200);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response, $payload);
            } else {
                $status='Error'; $mensaje='No se encontró ninguna inquilino con el ID proporcionado.';
                $payload = codeResponseGeneric($status,$mensaje,400);
                // Devolver y mostrar la respuesta con el error
                return responseWrite($response, $payload);
            }
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeResponseBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
}
?>