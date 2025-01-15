<?php 
class Database{
    public $db;

    public function __construct() {
        $db_host = getenv('DB_HOST');
        $db_name = getenv('DB_NAME');
        $db_username = getenv('DB_USERNAME');
        $db_password = ''; # Mot de passe à éditer hors local

        try {
            $this->db = new PDO ("mysql:host=$db_host;dbname=$db_name;charset=utf8",$db_username, $db_password);
        } catch (Exception $e) {
            die ('Erreur :'. $e->getMessage());
            
        }

    }
    public function getConnection() {
        return $this->db;
    }
    
}
