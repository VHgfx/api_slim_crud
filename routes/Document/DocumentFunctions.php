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
function processDocumentModifier($modifier, $user, $data, $request_type, $arg_id): array{
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
            default:
                throw new RuntimeException("processRoute POST : Route non configurée");
        }

        return $result;
    };

    $processGetRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {
            case 'login-history':
                $result = getLoginHistory($user, $arg_id);
            break;
    
            default:
                throw new RuntimeException("processRoute GET : Route non configurée");
        }

        return $result;
    };

    $processDeleteRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {
            default:
                throw new RuntimeException("processRoute DELETE : Route non configurée");
        }

        return $result;
    };

    $processPutRoutes = function ($modifier, $arg_id, $user, $data) {
        switch ($modifier) {    
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

/** GET : /document/login_history : Récupère les informations de l'utilisateur ou du profil visé
 * @param object $user
 * @param int $arg_id
 * @throws \Exception
 * @throws \RuntimeException
 * 
 * @return array
 */
function getLoginHistory($user, $arg_id = null): array {
    checkLoginStatus($user);

    $searched_user = new AppUser();
    $document = new Document();
    $document_type = new DocumentType();
    $login_history = new LoginHistory();

    $processTarget = function($user, $arg_id): int {
        if(in_array($user->id_role, [1,2])){
            if(isset($arg_id) && !empty($arg_id) && filter_var($arg_id, FILTER_VALIDATE_INT)){
                return $arg_id;
            }
        }

        return $user->id;
    };

    $processLoginData = function($login_history): array {
        $raw_data = $login_history->getLoginDetails();
        if(!is_array($raw_data)){
            throw new RuntimeException("Résultat de la requête SQL incorrect");
        }

        if(empty($raw_data)){
            return [];
        }
    
        foreach($raw_data as $row){
            $year = $row['year'];
            $month_year = $row['month_year'];
    
            if (!isset($sorted_data[$year])) {
                $sorted_data[$year] = [];
            }
    
            if (!isset($sorted_data[$year][$month_year])) {
                $sorted_data[$year][$month_year] = [
                    'total_logins' => 0,
                    'successful_logins' => 0,
                    'failed_logins' => 0,
                    'logins' => []
                ];
            }
    
            $sorted_data[$year][$month_year]['total_logins'] = $row['total_logins'];
            $sorted_data[$year][$month_year]['successful_logins'] = $row['successful_logins'];
            $sorted_data[$year][$month_year]['failed_logins'] = $row['failed_logins'];
    
            $sorted_data[$year][$month_year]['logins'][] = [
                'login_time' => $row['login_time'],
                'ip_adress' => $row['ip_adress'],
                'success' => $row['success'],
                'user_agent' => $row['user_agent']
            ];
    
        }
    
        return $sorted_data;
    };

    $searched_user->id = $processTarget($user, $arg_id);
    $searched_infos = $searched_user->infos();

    if(!$searched_infos){
        throw new RuntimeException("Impossible de récupérer le profil de l'utilisateur recherché");
    }

    $login_history->id_app_user = $searched_user->id;

    $sorted_data = $processLoginData($login_history);

    $document->id_app_user = $login_history->id_app_user;
    $document->id_document_type = 1;
    $document_type->id = $document->id_document_type;

    $file_type = $document_type->getTypeName();

    $pdf_salt = getenv('PDF_SALT');
    $method = 'aes-256-cbc';

    foreach ($sorted_data as $year => $months) {
        foreach ($months as $month_year => $data) {
            $raw_file_name = $file_type . "_". $month_year;

            $document->name_iv = random_bytes(16);
            $document->name = openssl_encrypt($raw_file_name, $method, $pdf_salt, 0, $document->name_iv);
            $document->add();
        }
    }

    $result = [
        'success' => true,
        'message' => "Récupération de l'historique de connexion réussie",
        'data' => $sorted_data
    ];

    return $result;
}

