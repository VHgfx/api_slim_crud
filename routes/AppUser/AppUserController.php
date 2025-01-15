<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/AppUserFunctions.php';

$app->any('/user/{modifier}[/{id:\d+}]', function (Request $request, Response $response, $args) use ($auth_key) {
    $user = new AppUser();
    $data = $request->getParsedBody();
    $request_type = $request->getMethod();
    $arg_id = isset($args['id']) ? (int) $args['id'] : null;

    try {
        processLoginStatus($request, $user, $auth_key);
        $result = processModifier($args['modifier'], $user, $data, $request_type, $arg_id);

        $statusCode = match(true) {
            $result['success'] === true && $request_type === 'POST' => 201, 
            $result['success'] === true && $request_type === 'PUT' => 200,
            $result['success'] === true && $request_type === 'GET' => 200,
            $result['success'] === true && $request_type === 'DELETE' => 200, 
            $result['success'] === false => 400,  
            default => 500,  
        };

        $response->getBody()->write(json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data']
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    } catch (InvalidArgumentException $e) {
        $errorMessage = "InvalidArgumentException : " . $e->getMessage();
    } catch (RuntimeException $e) {
        $errorMessage = "RuntimeException : " . $e->getMessage();
    } catch (Exception $e) {
        $errorMessage = "Erreur : " . $e->getMessage();
    }
    error_log($errorMessage);
    $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
});