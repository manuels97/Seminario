<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../database.php';

class InquilinosController {

    // POST /inquilinos    
    public function crearInquilino (Request $request, Response $response) {
        try{ 
            $connection=getConnection();
            $datos= $request -> getParsedBody(); 
            $requiredFields= ['apellido','nombre','documento','email','activo'];
            $payload=faltanDatos($requiredFields,$datos);
            if (isset($payload)) {
                return responseWrite($response,$payload);
            }  
            $documento = $datos['documento'];
            $query=$connection->query("SELECT documento FROM inquilinos WHERE documento=$documento LIMIT 1");
            if($query->rowCount()>0){
                        $status='Error';$mensaje='Ya hay un documento registrado con ese valor'; 
                        $payload=codeResponseGeneric($status,$mensaje,400);
                        return responseWrite($response,$payload);
            } 
            $query=$connection->prepare('INSERT INTO inquilinos (apellido,nombre,documento,email,activo) VALUES (:apellido,:nombre,:documento,:email,:activo)');
            $elementos=[
                        ':apellido'=> $datos['apellido'],
                        ':nombre'=>$datos['nombre'],
                        ':documento'=>$datos['documento'],
                        ':email'=>$datos['email'],
                        ':activo'=>$datos['activo']
            ];
            $query->execute($elementos);
            $status='Success'; $mensaje='Inquilino agregado exitosamente'; $payload=codeResponseGeneric($status,$mensaje,200);
            return responseWrite($response,$payload);    
        } catch (\PDOException $e) {
            $payload=codeResponseBad();
            return responseWrite($response,$payload);

        }
    }
    // PUT /inquilinos/{id}   
    public function editarInquilino (Request $request, Response $response, $args){
        $id_url= $args['id']; 
        if (!is_numeric($id_url)) {
            $status='Error'; $mensaje='ID invalido'; $payload=codeResponseGeneric($status,$mensaje,400);
            return responseWrite($response,$payload);
        }
        try {
            $connection= getConnection();
            $query= $connection->query("SELECT id FROM inquilinos WHERE id=$id_url LIMIT 1");
            if($query->rowCount()==0) {
                $status='Error';$mensaje='No se encuntra el ID'; $payload=codeResponseGeneric($status,$mensaje,404);
                return responseWrite($response,$payload);
            }
            $datos= $request->getParsedBody();
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
            
            // comprobar que no falten datos
            $requiredFields=['apellido','nombre','documento','email','activo'];
            $payload=faltanDatos($requiredFields,$datos);
            if (isset($payload)) {
                return responseWrite($response,$payload);
            }  
            $documento=$datos['documento'];
            $query=$connection->query("SELECT id FROM inquilinos where documento=$documento");
            // Verifico que el documento encontrado no sea el del id que quiero editar 
          
            if($query->rowCount()>0){
                $consulta= $query-> fetch(\PDO::FETCH_ASSOC);
                $idConsulta=$consulta['id'];
                if($idConsulta!=$id_url){
                    $status='Error'; $mensaje='El documento proporcionado ya se encuentra en uso'; $payload=codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                }
            } 
            $query=$connection->prepare("UPDATE inquilinos SET
                      apellido= :apellido,
                      nombre= :nombre,
                      documento= :documento,
                      email= :email,
                      activo= :activo
                WHERE id=:id 
            ");
            $elementos=[
                ':apellido'=> $datos['apellido'],
                ':nombre'=>$datos['nombre'],
                ':documento'=>$datos['documento'],
                ':email'=>$datos['email'],
                ':activo'=>$datos['activo'],
                ':id'=>$id_url
            ];
            $query->execute($elementos);
            $status='SUCCESS'; $mensaje='Inquilino editado exitosamente'; 
            $payload=codeResponseGeneric($status,$mensaje,200);
            return responseWrite($response,$payload);
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
        $id_url = $args['id']; 
        // Validar ID numérico
        if(!is_numeric($id_url)) {
            $status = 'Error'; 
            $mensaje = 'ID no válido'; 
            $payload = codeResponseGeneric($status, $mensaje, 400);
            return responseWrite($response, $payload);
        }    
        
        try {  
            $connection = getConnection();
             // Realiza la consulta SQL
             $query = $connection->query("SELECT * FROM inquilinos WHERE id=$id_url");
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
       
       
        $id_url = $args['id']; 
        // Validar ID numérico
        if(!is_numeric($id_url)) {
            $status = 'Error'; 
            $mensaje = 'ID no válido'; 
            $payload = codeResponseGeneric($status, $mensaje, 400);
            return responseWrite($response, $payload);
        }    
        
        try {  
            $connection = getConnection();
             // Realiza la consulta SQL
             $query = $connection->prepare('
             SELECT inquilinos.nombre,inquilinos.apellido, reservas.*
             FROM inquilinos
             INNER JOIN reservas ON inquilinos.id = reservas.inquilino_id
             WHERE inquilinos.id = :id
             ');
             $query->execute(['id' => $id_url]);
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
                $payload= codeResponseBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    // DELETE inquilinos/{id}
    public function eliminarPorId (Request $request, Response $response, $args) {
        $id_url = $args['id']; 
        // Validar ID numérico
        if(!is_numeric($id_url)) {
            $status = 'Error'; 
            $mensaje = 'ID no válido'; 
            $payload = codeResponseGeneric($status, $mensaje, 400);
            return responseWrite($response, $payload);
        }
        try {    
             $connection = getConnection(); 
             // Realiza la consulta SQL
             $query = $connection->prepare('DELETE FROM inquilinos WHERE id=:id');
             $query -> bindValue(':id', $id_url);
             $query-> execute();
             // Preparamos la respuesta json 
             if($query->rowCount()>0) {
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