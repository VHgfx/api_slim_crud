<?php
class Document extends Database {
    private const TABLE_NAME = 'document'; # A renommer en fonction de la table

    public $id;
    public $created;
    public $signature;
    public $iv;
    public $id_app_user;


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
}   