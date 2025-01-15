<?php 
/** Utilise la bonne fonction en fonction du nom de route capté
 * Summary of processModifier
 * @param mixed $route
 * @param mixed $user
 * @param mixed $data
 * @throws \Exception
 * @throws \RuntimeException
 * @return array result = 
 *               "success" => boolean,
 *               "message" => string, 
 *               "data" => array
 * 
 * 
 */
function processModifier($modifier, $user, $data, $request_type, $arg_id): array{
    $checkRouteModifier = function ($modifier) {
        if(!isset($modifier) || empty($modifier)) {
            throw new InvalidArgumentException("URI incomplète");
        }
    
        $input = trim($modifier);
    
        if (!is_string($input)) {
            throw new InvalidArgumentException("Format de la route incorrect");
        }
    };

    $processPostRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {
            case 'signup':
                $result = postSignUp($user, $data);
            break;
    
            default:
                throw new RuntimeException("processRoute POST : Route non configurée");
        }

        return $result;
    };

    $processGetRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {
            case 'profile':
                $result = getProfile($user, $arg_id);
            break;
    
            default:
                throw new RuntimeException("processRoute GET : Route non configurée");
        }

        return $result;
    };

    $processDeleteRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {
            case 'account':
                $result = deleteAccount($user, $arg_id);
            break;
    
            default:
                throw new RuntimeException("processRoute DELETE : Route non configurée");
        }

        return $result;
    };

    $processPutRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {
            case 'edit_password':
                $result = putUpdatePassword($user, $data, $arg_id);
            break;
    
            default:
                throw new RuntimeException("processRoute PUT : Route non configurée");
        }

        return $result;
    };

    $checkRouteModifier($modifier);

    switch ($request_type){
        case "POST":
            $result = $processPostRoutes($modifier, $arg_id, $user, $data);

        break;

        case "GET":
            $result = $processGetRoutes($modifier, $arg_id, $user, $data);

        break;

        case "DELETE":
            $result = $processDeleteRoutes($modifier, $arg_id, $user, $data);
        
        break;

        case "PUT":
            $result = $processPutRoutes($modifier, $arg_id, $user, $data);

        break;

        default:
            throw new RuntimeException("Type de requête non prise en charge");
    }

    if($result['success']){
        return $result;
    } else {
        throw new RuntimeException("processRoute échec dans le cas : " . $modifier);
    }
}

/** POST : /user/signup : Ajoute un utilisateur membre
 * Summary of signUp
 * @param mixed $user
 * @param mixed $data
 * @throws \Exception
 * @throws \RuntimeException
 * @return array
 */
function postSignUp($user, $data): array {
    /** Chargement des données utilisées dans les objets concernés
     * 
     */
    $processInputs = function($user, $data): void {
        $all_fields = [
            'email' => ['is_required' => true, 'object' => $user, 'data' => $data],
            'firstname' => ['is_required' => true, 'object' => $user, 'data' => $data], 
            'lastname' => ['is_required' => true, 'object' => $user, 'data' => $data]
        ];
    
        foreach ($all_fields as $field_key => $field_value) {
            checkInput($field_key, $field_value['is_required'], $field_value['object'], $field_value['data']);
        }
    };

    $processAccountCreation = function ($user): bool {
        $generatedPassword = function (): string {
             // Définition des jeux de caractères
            $lowercase = 'abcdefghijklmnopqrstuvwxyz';
            $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $numbers = '0123456789';
            $specialChars = '@#$!()';
            $length = 20;

            // Combinaison de tous les jeux de caractères
            $allChars = $lowercase . $uppercase . $numbers . $specialChars;

            // S'assurer que le mot de passe contiendra au moins un de chaque type requis
            $password = [];
            $password[] = $uppercase[random_int(0, strlen($uppercase) - 1)];
            $password[] = $numbers[random_int(0, strlen($numbers) - 1)];
            $password[] = $specialChars[random_int(0, strlen($specialChars) - 1)];

            // Remplir le reste du mot de passe
            for ($i = 3; $i < $length; $i++) {
                $password[] = $allChars[random_int(0, strlen($allChars) - 1)];
            }

            // Mélanger le tableau de caractères pour plus de sécurité
            shuffle($password);

            // Retourner le mot de passe en tant que chaîne de caractères
            return implode('', $password);
        };

        if($user->existingEmail()){
            throw new Exception("L'email entré est déjà utilisé");
        }
    
        $user->password = $generatedPassword();
        $user->id_role = 3;
    
        $user->id = $user->add();
        if(!$user->id){
            return false;
        }
        return true;
    };

    /** Gestion de la partie mailing après création du compte
     * 
     */
    $processMailing = function($user): bool {
        $user_infos = $user->infos();

        $receiver = [
            "firstname" => $user->firstname,
            "lastname" => $user->lastname,
            "role" => $user_infos[0]['name'],
            "email" => $user->email
        ];

        $mail_datas = [
            "password" => $user->password
        ];

        if(Email::sendSignUpNotification($receiver,$mail_datas)){
            return true;
        } else {
            $user->delete();
            return false;        
        }
    };

    if($user->id){
        throw new Exception("Vous êtes déjà connecté à un compte");
    }

    $processInputs($user, $data);

    $result_account_creation = $processAccountCreation($user);
    if(!$result_account_creation){
        throw new RuntimeException("Une erreur s'est produite dans la création du compte");
    }

    // Configurer serveur SMTP en premier
    // $processMailing($user);

    $result = [
        'success' => true,
        'message' => 'Création du compte réussie',
        'data' => [
            'password' => $user->password // Renvoi du mot de passe généré
        ]
    ];

    return $result;
}

/** GET : /user/profile : Récupère les informations de l'utilisateur ou du profil visé
 * @param object $user
 * @param int $arg_id
 * @throws \Exception
 * @throws \RuntimeException
 * 
 * @return array
 */
function getProfile($user, $arg_id = null): array {
    checkLoginStatus($user);

    $searched_user = new AppUser();

    $processInputs = function($user, $arg_id): int {
        if(in_array($user->id_role, [1,2])){
            if(isset($arg_id) && !empty($arg_id) && filter_var($arg_id, FILTER_VALIDATE_INT)){
                return $arg_id;
            }
        }

        return $user->id;
    };

    $searched_user->id = $processInputs($user, $arg_id);
    $searched_infos = $searched_user->infos();

    if(!$searched_infos){
        throw new RuntimeException("Impossible de récupérer le profil de l'utilisateur recherché");
    }

    $searched_infos = $searched_infos[0];
    
    $result = [
        'success' => true,
        'message' => 'Récupération du profil réussie',
        'data' => $searched_infos
    ];

    return $result;
}

/** DELETE : /user/account : Supprime son compte ou celui d'un utilisateur
 * @param mixed $user
 * @param int $arg_id
 * @throws \RuntimeException
 * @return array
 */
function deleteAccount($user, $arg_id = null): array {
    checkLoginStatus($user);

    $searched_user = new AppUser();

    $processInputs = function($user, $arg_id): int {
        if(in_array($user->id_role, [1,2])){
            if(isset($arg_id) && !empty($arg_id) && filter_var($arg_id, FILTER_VALIDATE_INT)){
                return $arg_id;
            }
        }

        return $user->id;
    };

    $searched_user->id = $processInputs($user, $arg_id);
    $searched_infos = $searched_user->infos();

    if(!$searched_infos){
        throw new RuntimeException("Impossible de récupérer le profil de l'utilisateur recherché");
    }

    $searched_user->delete();

    $result = [
        'success' => true,
        'message' => "Suppression de l'utilisateur réussie",
        'data' => []
    ];

    return $result;
}

/** UPDATE : /user/edit-password : Permet de màj son mot de passe ou celui d'un utilisateur
 * @param array  $data   Champs obligatoires : 'old_password' (facultatif pour un administrateur qui veut édit le password d'un autre user), 'new_password'
 * @param object $user
 * @param int    $arg_id (Facultatif) : Permet de cibler un utilisateur si rôle permis.
 */
function putUpdatePassword($user, $data, $arg_id = null): array {
    checkLoginStatus($user);
    $searched_user = new AppUser();

    $processArg = function($user, $arg_id): int {
        if(in_array($user->id_role, [1])){
            if(isset($arg_id) && !empty($arg_id) && filter_var($arg_id, FILTER_VALIDATE_INT)){
                return $arg_id;
            }
        }

        return $user->id;
    };

    $processInputs = function($searched_user, $user, $data) {
        $checkOldPassword = function ($input, $searched_user) {
            $input = trim($input);
            $login_infos = $searched_user->login();

            if(!password_verify($searched_user->password, $login_infos['password'])){
                throw new Exception("Ancien mot de passe incorrect");
            }
        };

        $checkNewPassword = function ($input) {
            $input = trim($input);

            if (strlen($input) < 12 ||
            !preg_match('/[A-Z]/', $input) ||
            !preg_match('/[0-9]/', $input) ||
            !preg_match('/[\W_]/', $input)) {
                throw new Exception("Le mot de passe doit contenir au moins 12 caractères, une majuscule, un chiffre et un caractère spécial");
            }

            return $input;
        };

        #===============================================================
        if($user->id_role === 1 && ($user->id !== $searched_user->id)){
            // Pour un administrateur qui souhaite éditer le mot de passe d'un autre user, seul le nouveau mot de passe est obligatoire
            $required_fields = ['new_password'];
            checkRequired($required_fields, $data);
        } else {
            $required_fields = ['old_password', 'new_password'];
            checkRequired($required_fields, $data);
            $checkOldPassword($data['old_password']);
        }


        $checkOldPassword($data['old_password']);

        return $checkNewPassword($data['new_password']);
    };

    #################################################################

    $searched_user->id = $processArg($user, $arg_id);
    $searched_user->password = $processInputs($searched_user, $user, $data);
    $searched_user->updatePassword();

    $result = [
        'success' => true,
        'message' => "Modification du mot de passe réussie",
        'data' => []
    ];

    return $result;
}