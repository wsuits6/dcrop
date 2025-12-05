<?php
/**
 * Student Model
 * DCROP System - backend/models/studentModel.php
 * Handles all student-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class StudentModel {
    private $conn;
    private $table = 'students';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create new student
     * @param array $data
     * @return int|false Student ID or false
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (full_name, index_number, email, password, community, verification_token) 
                  VALUES (:full_name, :index_number, :email, :password, :community, :verification_token)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':index_number', $data['index_number']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':community', $data['community']);
            $stmt->bindParam(':verification_token', $data['verification_token']);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Student Create Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read student by ID
     * @param int $id
     * @return array|false
     */
    public function read($id) {
        $query = "SELECT id, full_name, index_number, email, community, email_verified, created_at, updated_at 
                  FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch(PDOException $e) {
            error_log("Student Read Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read all students
     * @return array|false
     */
    public function readAll() {
        $query = "SELECT id, full_name, index_number, email, community, email_verified, created_at 
                  FROM " . $this->table . " ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Student Read All Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read students by community
     * @param string $community
     * @return array|false
     */
    public function readByCommunity($community) {
        $query = "SELECT id, full_name, index_number, email, community, email_verified, created_at 
                  FROM " . $this->table . " WHERE community = :community ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':community', $community);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Student Read By Community Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update student
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET full_name = :full_name, 
                      email = :email, 
                      community = :community 
                  WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':community', $data['community']);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Student Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete student
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
            error_log("Student Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify student by index number (for registration)
     * @param string $index_number
     * @return bool
     */
    public function verifyIndexNumber($index_number) {
        $query = "SELECT id FROM " . $this->table . " WHERE index_number = :index_number LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':index_number', $index_number);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
            
        } catch(PDOException $e) {
            error_log("Index Number Verification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get student by email
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
     * Get student by index number
     * @param string $index_number
     * @return array|false
     */
    public function getByIndexNumber($index_number) {
        $query = "SELECT * FROM " . $this->table . " WHERE index_number = :index_number LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':index_number', $index_number);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch(PDOException $e) {
            error_log("Get By Index Number Error: " . $e->getMessage());
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
     * Authenticate student
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function authenticate($email, $password) {
        $student = $this->getByEmail($email);
        
        if($student && password_verify($password, $student['password'])) {
            // Remove password from returned data
            unset($student['password']);
            return $student;
        }
        
        return false;
    }
    
    /**
     * Get student attendance history
     * @param int $student_id
     * @return array|false
     */
    public function getAttendanceHistory($student_id) {
        $query = "SELECT * FROM attendance 
                  WHERE student_id = :student_id 
                  ORDER BY date DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get Attendance History Error: " . $e->getMessage());
            return false;
        }
    }
}
?>