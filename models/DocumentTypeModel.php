<?php
class DocumentType extends Database {
    public $id;
    public $type_name;
    private const TABLE_NAME = 'document_type'; # A renommer en fonction de la table

    public function getTypeName(): string {
        $query = "SELECT type_name FROM " . self::TABLE_NAME . " 
            WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":id", $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['type_name'])) {
                return $result['type_name'];
            }
    
            return null;        
        } catch (PDOException $e) {
            error_log('PDO Exception : ' . $e);
            return null;
        } 
    }

}