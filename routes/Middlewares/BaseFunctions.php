<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response;

/** Capte si le token est existant dans le header, sinon, renvoi false
 * @return bool|Response
 * @param mixed $request
 * @param mixed $user
 * @param mixed $auth_key
 */
function processLoginStatus($request, $user, $auth_key) {
    $token = $request->getHeader('token');
    
    if(!isset($token) || empty($token)){
        return false;
    }

    $decoded = JWT::decode($token[0], new Key($auth_key, 'HS256'));

    if(!$decoded){
        throw new Exception("Token incorrect");
    }

    $user->id = $decoded->id;
    $user->id_role = $decoded->id_role;

    return true;
  
}

/** Vérification du status de connexion
 * @param object $user
 * @throws \Exception Si l'utilisateur n'est pas connecté.
 * @return void
 */
function checkLoginStatus($user){
    if(!$user->id){
        throw new Exception("Veuillez vous connecter pour accéder à cette fonctionnalité");
    }
}

/** Check $field dans $data et retourne soit null, soit la valeur
 *  
 * @param mixed $field
 * @param mixed $data
 * @throws \InvalidArgumentException
 * @return void
 */
function checkInput ($field, $is_required, $object, $data) {
    if(!isset($data[$field]) || empty($data[$field])) {
        if($is_required){
            throw new InvalidArgumentException("Champs requis vide : $field");
        }
        $object->$field = null;
        return;
    }

    $input = trim($data[$field]);

    switch ($field){
        case "email":
            if(!filter_var($input, FILTER_VALIDATE_EMAIL)){
                throw new InvalidArgumentException("Veuillez entrer un email valide");
            }
        break;

        case 'firstname':
            if (!preg_match('/^[\p{L}\s\'-]+$/u', $input)) {
                throw new Exception("Veuillez entrer un prénom valide");
            } 
        break;

        case 'lastname':
            if (!preg_match('/^[\p{L}\s\'-]+$/u', $input)) {
                throw new Exception("Veuillez entrer un nom valide");
            } 
        break;

        case 'password':
            if(strlen($input) < 12){
                throw new Exception("Mot de passe invalide");
            }
        break;

        default:
            throw new RuntimeException("Type de input non pris en charge");

    }
    $object->$field = $input;
    return;
}

/** Vérifie les champs obligatoires.
 * @param  array  $required_fields Un tableau de strings comportant le nom des clés à vérifier parmi data
 * @param  array  $data            Tableau des données reçues.
 * @throws \InvalidArgumentException Si un des champs obligatoires n'est pas présent ou est vide.
 * @return void
 */
function checkRequired (array $required_fields, array $data) {
    foreach ($required_fields as $field){
        if(!isset($data[$field]) || empty($data[$field])){
            throw new InvalidArgumentException("Champs obligatoire vide : $field");
        }
    }
}