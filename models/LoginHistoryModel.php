<?php
class LoginHistory extends Database {
    private const TABLE_NAME = 'login_history'; # A renommer en fonction de la table

    public $id;
    public $login_time;
    public $ip_adress;
    public $success;
    public $user_agent;

    public $id_app_user;

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

    public function getLoginSummary(){
        try {
            $query = "
                SELECT
                    DATE_FORMAT(login_time, '%Y-%m') AS month_year,
                    COUNT(*) AS total_logins,
                    SUM(success) AS successful_logins,
                    COUNT(*) - SUM(success) AS failed_logins
                FROM login_history
                WHERE id_app_user = :id_app_user
                GROUP BY month_year
                ORDER BY month_year DESC;
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":id_app_user", $this->id_app_user, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    public function getLoginDetails() {
        try {
            $query = "
                SELECT 
                    lh.id,
                    lh.login_time,
                    lh.ip_adress,
                    lh.success,
                    lh.user_agent,
                    DATE_FORMAT(lh.login_time, '%Y') AS year,
                    DATE_FORMAT(lh.login_time, '%Y-%m') AS month_year,
                    login_summary.total_logins,
                    login_summary.successful_logins,
                    login_summary.failed_logins
                FROM login_history lh
                JOIN (
                    SELECT 
                        DATE_FORMAT(login_time, '%Y-%m') AS month_year,
                        COUNT(*) AS total_logins,
                        SUM(success) AS successful_logins,
                        COUNT(*) - SUM(success) AS failed_logins
                    FROM login_history
                    WHERE id_app_user = :id_app_user
                    GROUP BY month_year
                ) AS login_summary
                ON DATE_FORMAT(lh.login_time, '%Y-%m') = login_summary.month_year
                WHERE lh.id_app_user = :id_app_user
                ORDER BY lh.login_time DESC;
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":id_app_user", $this->id_app_user, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
}