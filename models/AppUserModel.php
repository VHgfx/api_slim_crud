<?php
class AppUser extends Database {
    private const TABLE_NAME = 'app_user'; # A renommer en fonction de la table

    public $id;
    public $email;
    public $password;
    public $firstname;
    public $lastname;
    public $id_role;

    /** Retourne le résultat de la requête SQL paramétrée
     * @param string $query         La requête SQL en elle-même.
     * @param array  $params        Tableau des paramètres de la requêtes au format [['email' => ['value => $this->email, 'type' => PDO::PARAM_STR]]].
     * @param string $errorMessage  Le message d'erreur renvoyé.
     * @param string $operationType INSERT, UPDATE, DELETE, SELECT.
     * 
     * @throws \Exception
     * @return mixed
     */
    private function executeTransaction(string $query, array $params, string $errorMessage, string $operationType): mixed
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value['value'], $value['type']);
            }

            if ($stmt->execute()) {
                switch ($operationType) {
                    case 'INSERT':
                        $this->db->commit();
                        return true;

                    case 'UPDATE':
                    case 'DELETE':
                        if ($stmt->rowCount() > 0) {
                            $this->db->commit();
                            return true;
                        }
                        throw new Exception($errorMessage);

                    case 'SELECT':
                        $this->db->commit(); 
                        return $stmt->fetchAll(PDO::FETCH_ASSOC); 

                    default:
                        throw new Exception("Unsupported operation type: $operationType");
                }
            } else {
                throw new Exception($errorMessage);
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("PDO Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }

    public function existingEmail(): bool {
        $query = "SELECT * FROM " . self::TABLE_NAME . " WHERE email = :email";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":email", $this->email, PDO::PARAM_STR);
            $stmt->execute();

            $count = $stmt->fetchColumn();

            return ($count >= 1);
        } catch (PDOException $e) {
            error_log('PDO Exception : ' . $e);
            return false;
        } 
    }

    public function infos(): mixed{
        $query = 'SELECT ' . self::TABLE_NAME . '.id, ' . self::TABLE_NAME . '.email, ' . self::TABLE_NAME . '.firstname, ' . self::TABLE_NAME . '.lastname, role.name AS role_name
            FROM ' . self::TABLE_NAME . '
            JOIN role ON role.id = ' . self::TABLE_NAME . '.id_role
            WHERE ' . self::TABLE_NAME . '.id = :id';

        $params = [
            'id' => ['value' => $this->id, 'type' => PDO::PARAM_STR]
        ];

        return $this->executeTransaction($query, $params, 'Erreur dans la récupération des informations du compte', 'SELECT');
    }

    public function login(): mixed{
        $query = 'SELECT email, id, password, id_role 
            FROM ' . self::TABLE_NAME . ' WHERE email = :email';


        $params = [
            'email' => ['value' => $this->email, 'type' => PDO::PARAM_STR]
        ];

        return $this->executeTransaction($query, $params, 'Erreur dans la récupération des informations du compte', 'SELECT');
    }

    public function add() {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO `". self::TABLE_NAME  ."` (`email`, `password`, `firstname`, `lastname`, `id_role`) 
                    VALUES (:email, :password, :firstname, :lastname, :id_role)";
        
            $stmt = $this->db->prepare($query);

            $hashed =  password_hash($this->password, PASSWORD_DEFAULT);

            $stmt->bindValue(":email", $this->email, PDO::PARAM_STR);
            $stmt->bindValue(":password", $hashed, PDO::PARAM_STR);
            $stmt->bindValue(":firstname", $this->firstname, PDO::PARAM_STR);
            $stmt->bindValue(":lastname", $this->lastname, PDO::PARAM_STR);
            $stmt->bindValue(":id_role", $this->id_role, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $created_id = $this->db->lastInsertId();
                $this->db->commit();
                
                return $created_id; 
            } else {
                $this->db->rollBack();
                return false;
            }

        } catch (PDOException $e) {
            $this->db->rollBack();
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function delete(): mixed{
        $query = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE id = :id';

        $params = [
            'id' => ['value' => $this->id, 'type' => PDO::PARAM_INT]
        ];

        return $this->executeTransaction($query, $params, 'Erreur dans la suppression du compte', 'DELETE');
    }

    public function updatePassword() {
        $query = "UPDATE ". SELF::TABLE_NAME ." SET password = :newPassword WHERE id = :id";

        $hashed =  password_hash($this->password, PASSWORD_DEFAULT);

        $params = [
            'password' => ['value' => $hashed, 'type' => PDO::PARAM_STR],
            'id' => ['value' => $this->id, 'type' => PDO::PARAM_INT],
        ];

        return $this->executeTransaction($query, $params, 'Modification du mot de passe réussie', 'UPDATE');
    }
}