<?php
class LoginHistory extends Database {
    private const TABLE_NAME = 'login_history'; # A renommer en fonction de la table

    public $id;
    public $login_time;
    public $ip_adress;
    public $success;
    public $user_agent;

    public $id_app_user;

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

    public function add() {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO `". self::TABLE_NAME  ."` (`ip_adress`, `success`, `user_agent`, `id_app_user`) 
                    VALUES (:ip_adress, :success, :user_agent, :id_app_user)";
        
            $stmt = $this->db->prepare($query);


            $stmt->bindValue(":ip_adress", $this->ip_adress, PDO::PARAM_STR);
            $stmt->bindValue(":success", $this->success, PDO::PARAM_STR);
            $stmt->bindValue(":user_agent", $this->user_agent, PDO::PARAM_STR);
            $stmt->bindValue(":id_app_user", $this->id_app_user, PDO::PARAM_INT);
            
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
}