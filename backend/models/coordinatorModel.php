<?php
/**
 * Coordinator Model
 * DCROP System - backend/models/coordinatorModel.php
 * Handles all coordinator-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class CoordinatorModel {
    private $conn;
    private $table = 'coordinators';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create new coordinator
     * @param array $data
     * @return int|false Coordinator ID or false
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (full_name, email, password, assigned_community, verification_token) 
                  VALUES (:full_name, :email, :password, :assigned_community, :verification_token)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':assigned_community', $data['assigned_community']);
            $stmt->bindParam(':verification_token', $data['verification_token']);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Coordinator Create Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read coordinator by ID
     * @param int $id
     * @return array|false
     */
    public function read($id) {
        $query = "SELECT id, full_name, email, assigned_community, email_verified, created_at, updated_at 
                  FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch(PDOException $e) {
            error_log("Coordinator Read Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read all coordinators
     * @return array|false
     */
    public function readAll() {
        $query = "SELECT id, full_name, email, assigned_community, email_verified, created_at 
                  FROM " . $this->table . " ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Coordinator Read All Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update coordinator
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET full_name = :full_name, 
                      email = :email, 
                      assigned_community = :assigned_community 
                  WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':assigned_community', $data['assigned_community']);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Coordinator Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete coordinator
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Coordinator Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get coordinator by email
     * @param string $email
     * @return array|false
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch(PDOException $e) {
            error_log("Get By Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify email with token
     * @param string $token
     * @return bool
     */
    public function verifyEmail($token) {
        $query = "UPDATE " . $this->table . " 
                  SET email_verified = 1, verification_token = NULL 
                  WHERE verification_token = :token";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            
            return $stmt->execute() && $stmt->rowCount() > 0;
            
        } catch(PDOException $e) {
            error_log("Email Verification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Authenticate coordinator
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function authenticate($email, $password) {
        $coordinator = $this->getByEmail($email);
        
        if($coordinator && password_verify($password, $coordinator['password'])) {
            // Remove password from returned data
            unset($coordinator['password']);
            return $coordinator;
        }
        
        return false;
    }
    
    /**
     * Get students assigned to coordinator's community
     * @param int $coordinator_id
     * @return array|false
     */
    public function getAssignedStudents($coordinator_id) {
        $query = "SELECT s.id, s.full_name, s.index_number, s.email, s.community, s.created_at 
                  FROM students s
                  INNER JOIN " . $this->table . " c ON s.community = c.assigned_community
                  WHERE c.id = :coordinator_id
                  ORDER BY s.full_name ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':coordinator_id', $coordinator_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get Assigned Students Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get attendance records for assigned students
     * @param int $coordinator_id
     * @param string $date_from (optional)
     * @param string $date_to (optional)
     * @return array|false
     */
    public function getStudentAttendance($coordinator_id, $date_from = null, $date_to = null) {
        $query = "SELECT a.*, s.full_name, s.index_number, s.email 
                  FROM attendance a
                  INNER JOIN students s ON a.student_id = s.id
                  INNER JOIN " . $this->table . " c ON s.community = c.assigned_community
                  WHERE c.id = :coordinator_id";
        
        if($date_from) {
            $query .= " AND a.date >= :date_from";
        }
        if($date_to) {
            $query .= " AND a.date <= :date_to";
        }
        
        $query .= " ORDER BY a.date DESC, s.full_name ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':coordinator_id', $coordinator_id);
            
            if($date_from) {
                $stmt->bindParam(':date_from', $date_from);
            }
            if($date_to) {
                $stmt->bindParam(':date_to', $date_to);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get Student Attendance Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get messages received by coordinator
     * @param int $coordinator_id
     * @return array|false
     */
    public function getReceivedMessages($coordinator_id) {
        $query = "SELECT m.*, s.full_name as sender_name, s.email as sender_email 
                  FROM messages m
                  INNER JOIN students s ON m.sender_id = s.id
                  WHERE m.receiver_id = :coordinator_id 
                  AND m.receiver_role = 'coordinator'
                  ORDER BY m.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':coordinator_id', $coordinator_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get Received Messages Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send message to admin (escalation)
     * @param int $coordinator_id
     * @param int $admin_id
     * @param string $message
     * @return bool
     */
    public function sendMessageToAdmin($coordinator_id, $admin_id, $message) {
        $query = "INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, message) 
                  VALUES (:sender_id, :receiver_id, 'coordinator', 'admin', :message)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sender_id', $coordinator_id);
            $stmt->bindParam(':receiver_id', $admin_id);
            $stmt->bindParam(':message', $message);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Send Message To Admin Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create report
     * @param int $coordinator_id
     * @param string $report_type
     * @param string $content
     * @return int|false Report ID or false
     */
    public function createReport($coordinator_id, $report_type, $content) {
        $query = "INSERT INTO reports (coordinator_id, report_type, content) 
                  VALUES (:coordinator_id, :report_type, :content)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':coordinator_id', $coordinator_id);
            $stmt->bindParam(':report_type', $report_type);
            $stmt->bindParam(':content', $content);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Create Report Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get reports by coordinator
     * @param int $coordinator_id
     * @return array|false
     */
    public function getReports($coordinator_id) {
        $query = "SELECT * FROM reports 
                  WHERE coordinator_id = :coordinator_id 
                  ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':coordinator_id', $coordinator_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get Reports Error: " . $e->getMessage());
            return false;
        }
    }
}
?>