<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../../vendor/autoload.php';

/** Récupération du token de connexion
 * @return string data     Data qui contient le JSON Web Token.
 * 
 * @param  string email    L'email de l'utilisateur.
 * @param  string password Le mot de passe de l'utilisateur.
 */
$app->post('/authgettoken', function (Request $request, Response $response) use ($auth_key) {
    $data = $request->getParsedBody();
    $user = new AppUser;
    $user->email = $data['email'];

    $login_history = new LoginHistory;
    $login_history->ip_adress = $_SERVER['REMOTE_ADDR'];
    $login_history->user_agent = $_SERVER['HTTP_USER_AGENT'];

    try {
        $fields_to_check = ['email', 'password'];
        foreach ($fields_to_check as $field) {
            checkInput($field, true, $user, $data);
        }

        $login_history->id_app_user = $user->existingAccount();
        if(empty($login_history->id_app_user)){
            throw new Exception("L'email ne correspond à aucun compte enregistré");
        }

        $result = $user->login();
        if(!$result){
            throw new Exception("L'email ne correspond à aucun compte enregistré");
        }

        $result = $result[0];

        if(!password_verify($user->password, $result['password'])){
            throw new Exception("Mot de passe invalide");
        }

        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'id' => $result['id'], 
            'id_role' => $result['id_role'],
        ];

        $jwt = JWT::encode($payload, $auth_key, 'HS256');

        $login_history->success = true;
        $login_history->add();

        $response->getBody()->write(json_encode([
            'success' => true, 
            'message' => 'Connexion réussie',
            'data' => $jwt
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (InvalidArgumentException $e) {
        $errorMessage = "InvalidArgumentException : " . $e->getMessage();
    } catch (RuntimeException $e) {
        $errorMessage = "RuntimeException : " . $e->getMessage();
    } catch (Exception $e) {
        $errorMessage = "Erreur : " . $e->getMessage();
    }
    $login_history->success = false;
    $login_history->id_app_user = $user->existingAccount();
    if(!empty($login_history->id_app_user)){
        $login_history->add();
    }
    error_log($errorMessage);
    $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
})->add($check_required(['email', 'password']));