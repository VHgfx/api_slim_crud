<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response;

$auth_key = getenv('AUTH_KEY');

$auth = function ($request, $handler) use ($auth_key) {

    $token = $request->getHeader('token');
    
    try{
        $decoded = JWT::decode($token[0], new Key($auth_key, 'HS256'));

        $userDatas = [
            'id' => $decoded->id,
            'role' => $decoded->role,
            'exp'=>$decoded->exp
        ];

        $request = $request->withAttribute('user', $userDatas);

        return $handler->handle($request);

    } catch (Exception $e){
        $response = new Response();
        error_log("Error : " . $e->getMessage());
        $response->getBody()->write(json_encode(['erreur' => 'Token invalide']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
};

$check_required = function (array $requiredFields) {
    return function ($request, $handler) use ($requiredFields) {
        $response = new Response();
        $data = $request->getParsedBody();

        try {
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Tous les champs obligatoires ne sont pas remplis: $field manquant.");
                }
            }

            return $handler->handle($request);
        } catch (Exception $e) {
            error_log("Error : " . $e->getMessage());
            $response->getBody()->write(json_encode(['erreur' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    };
};

$check_role = function ($allowedRoles) use ($auth_key) {
    return function ($request, $handler) use ($allowedRoles, $auth_key) {
        $token = $request->getHeader('token');
        $response = new Response();

        if (empty($token)) {
            $response->getBody()->write(json_encode(['erreur' => 'Token manquant']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Decode the token using the key
            $decoded = JWT::decode($token[0], new Key($auth_key, 'HS256'));
            $role = $decoded->role;

            // Check if the role is in the allowed roles
            if (in_array($role, $allowedRoles)) {
                // If the role is allowed, proceed to the next middleware/handler
                return $handler->handle($request);
            } else {
                // If the role is not allowed, return a 403 response
                $response->getBody()->write(json_encode(['erreur' => "Vous n'avez pas les droits suffisants"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        } catch (Exception $e) {
            error_log("Error : " . $e->getMessage());
            $response->getBody()->write(json_encode(['erreur' => 'Token invalide', 'détails' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    };
};
