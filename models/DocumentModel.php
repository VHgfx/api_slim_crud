<?php
class Document extends Database {
    private const TABLE_NAME = 'document'; # A renommer en fonction de la table

    public $id;
    public $name;
    public $created;
    public $signature;
    public $name_iv;
    public $signature_iv;

    public $id_app_user;
    public $id_document_type;


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

    public function existing(): bool {
        $query = "SELECT * FROM " . self::TABLE_NAME . " 
            WHERE id = :id
            AND id_app_user = :id_app_user";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":id", $this->id, PDO::PARAM_INT);
            $stmt->bindValue(":id_app_user", $this->id_app_user, PDO::PARAM_INT);
            $stmt->execute();

            $count = $stmt->fetchColumn();

            return ($count >= 1);
        } catch (PDOException $e) {
            error_log('PDO Exception : ' . $e);
            return false;
        } 
    }

    public function existingFile(): bool {
        $query = "SELECT * FROM " . self::TABLE_NAME . " 
            WHERE name = :name
            AND id_app_user = :id_app_user";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":name", $this->name, PDO::PARAM_STR);
            $stmt->bindValue(":id_app_user", $this->id_app_user, PDO::PARAM_INT);
            $stmt->execute();

            $count = $stmt->fetchColumn();

            return ($count >= 1);
        } catch (PDOException $e) {
            error_log('PDO Exception : ' . $e);
            return false;
        } 
    }


    public function add() {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO `". self::TABLE_NAME  ."` (`name`, `name_iv`, `id_app_user`, `id_document_type`) 
                    VALUES (:name, :name_iv, :id_app_user, :id_document_type)";
        
            $stmt = $this->db->prepare($query);

            $stmt->bindValue(":name", $this->name, PDO::PARAM_STR);
            $stmt->bindValue(":name_iv", $this->name, PDO::PARAM_STR);
            $stmt->bindValue(":id_app_user", $this->id_app_user, PDO::PARAM_INT);
            $stmt->bindValue(":id_document_type", $this->id_document_type, PDO::PARAM_INT);
            
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