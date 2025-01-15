<?php
/*
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');
*/

use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/loadEnv.php';
require_once __DIR__ . '/config/db.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$auth_key = getenv('AUTH_KEY');

require_once __DIR__ . '/routes/Middlewares/BaseFunctions.php';
require_once __DIR__ . '/routes/Middlewares/MiddlewaresFunctions.php';

$modelsPath = __DIR__ . '/models/';
foreach (glob($modelsPath . '*.php') as $filename) {
    require_once $filename;
}

require_once __DIR__ .'/routes/AppUser/AppUserController.php';

require_once __DIR__ .'/routes/Authentification/AuthentificationController.php';

$app->run();