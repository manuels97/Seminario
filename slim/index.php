<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\TipoPropiedadesController;
use App\Controllers\InquilinosController;
use App\Controllers\LocalidadesController;
use App\Controllers\PropiedadesController;
use App\Controllers\ReservasController;

require_once __DIR__ . '/src/Controllers/Localidades.php';
require_once __DIR__ . '/src/Controllers/TipoPropiedades.php';
require_once __DIR__ . '/src/Controllers/Inquilinos.php';
require_once __DIR__ . '/src/Controllers/Propiedad.php';
require_once __DIR__ . '/src/Controllers/Reservas.php';
require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add( function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

// ACÁ VAN LOS ENDPOINTS 


$app->get('/',function(Request $request,Response $response,$args){
    $response->getBody()->write('Hola mundo!!');
    return $response->withHeader('Content-Type', 'application/json');
});


// TIPOS PROPIEDAD

$app->get('/tipos_propiedad', TipoPropiedadesController::class. ':listar');
$app->post('/tipos_propiedad',TipoPropiedadesController::class. ':crearTipoPropiedad');
$app->put ('/tipos_propiedad/{id}',TipoPropiedadesController::class. ':editarTipoPropiedad');
$app->delete('/tipos_propiedad/{id}',TipoPropiedadesController::class . ':eliminarTipoPropiedad');


// Inquilinos
$app->post('/inquilinos', InquilinosController::class . ':crearInquilino');
$app->put ('/inquilinos/{id}', InquilinosController::class. ':editarInquilino');
$app->get('/inquilinos/listar', InquilinosController::class. ':listar');
$app->get('/inquilinos/listar/{id}', InquilinosController::class .':listarPorId');
$app->get('/inquilinos/{id}/reservas', InquilinosController::class. ':reservaPorId');
$app->delete('/inquilinos/eliminar/{id}', InquilinosController::class. ':eliminarPorId');


// Localidades
$app->get ('/localidades', LocalidadesController::class . ':listar');
$app->put ('/localidades/{id}', LocalidadesController::class . ':editarLocalidad');
$app->delete ('/localidades/{id}', LocalidadesController::class . ':eliminarLocalidad');
$app->post ('/localidades', LocalidadesController::class . ':agregarLocalidad');

//Propiedad
$app->get ('/propiedad', PropiedadesController::class . ':listar');
$app->put ('/propiedad/{id}', PropiedadesController::class . ':editarPropiedad');
$app->delete ('/propiedad/{id}', PropiedadesController::class . ':eliminarPropiedad');
$app->post ('/propiedad', PropiedadesController::class . ':crearPropiedad');
$app->get('/propiedad/listar/{id}', PropiedadesController::class .':listarPorId');

// Reservas
$app->get ('/reservas', ReservasController::class . ':listar');
$app->put ('/reservas/{id}', ReservasController::class . ':editarReserva');
$app->delete ('/reservas/{id}', ReservasController::class . ':eliminarReserva');
$app->post ('/reservas', ReservasController::class . ':agregarReserva');









 

$app->run(); 
