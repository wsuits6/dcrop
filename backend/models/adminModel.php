<?php
/**
 * Admin Model
 * DCROP System - backend/models/adminModel.php
 * Handles all admin-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class AdminModel {
    private $conn;
    private $table = 'admins';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create new admin
     * @param array $data
     * @return int|false Admin ID or false
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (full_name, email, password) 
                  VALUES (:full_name, :email, :password)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed_password);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Admin Create Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read admin by ID
     * @param int $id
     * @return array|false
     */
    public function read($id) {
        $query = "SELECT id, full_name, email, created_at, updated_at 
                  FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch(PDOException $e) {
            error_log("Admin Read Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read all admins
     * @return array|false
     */
    public function readAll() {
        $query = "SELECT id, full_name, email, created_at 
                  FROM " . $this->table . " ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Admin Read All Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update admin
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET full_name = :full_name, 
                      email = :email 
                  WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Admin Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete admin
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
            error_log("Admin Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get admin by email
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
     * Authenticate admin
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function authenticate($email, $password) {
        $admin = $this->getByEmail($email);
        
        if($admin && password_verify($password, $admin['password'])) {
            // Remove password from returned data
            unset($admin['password']);
            return $admin;
        }
        
        return false;
    }
    
    /**
     * Upload student data (bulk insert from Excel)
     * @param array $students
     * @return array Result with success count and errors
     */
    public function uploadStudentData($students) {
        $success_count = 0;
        $errors = [];
        
        $query = "INSERT INTO students (full_name, index_number, email, password, community) 
                  VALUES (:full_name, :index_number, :email, :password, :community)";
        
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare($query);
            
            foreach($students as $index => $student) {
                try {
                    // Generate default password (student can change later)
                    $default_password = password_hash('Student@123', PASSWORD_DEFAULT);
                    
                    $stmt->bindParam(':full_name', $student['full_name']);
                    $stmt->bindParam(':index_number', $student['index_number']);
                    $stmt->bindParam(':email', $student['email']);
                    $stmt->bindParam(':password', $default_password);
                    $stmt->bindParam(':community', $student['community']);
                    
                    if($stmt->execute()) {
                        $success_count++;
                    }
                } catch(PDOException $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            $this->conn->commit();
            
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Upload Student Data Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
        return [
            'success' => true,
            'success_count' => $success_count,
            'errors' => $errors
        ];
    }
    
    /**
     * Upload coordinator data (bulk insert from Excel)
     * @param array $coordinators
     * @return array Result with success count and errors
     */
    public function uploadCoordinatorData($coordinators) {
        $success_count = 0;
        $errors = [];
        
        $query = "INSERT INTO coordinators (full_name, email, password, assigned_community) 
                  VALUES (:full_name, :email, :password, :assigned_community)";
        
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare($query);
            
            foreach($coordinators as $index => $coordinator) {
                try {
                    // Generate default password
                    $default_password = password_hash('Coordinator@123', PASSWORD_DEFAULT);
                    
                    $stmt->bindParam(':full_name', $coordinator['full_name']);
                    $stmt->bindParam(':email', $coordinator['email']);
                    $stmt->bindParam(':password', $default_password);
                    $stmt->bindParam(':assigned_community', $coordinator['assigned_community']);
                    
                    if($stmt->execute()) {
                        $success_count++;
                    }
                } catch(PDOException $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            $this->conn->commit();
            
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Upload Coordinator Data Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
        return [
            'success' => true,
            'success_count' => $success_count,
            'errors' => $errors
        ];
    }
    
    /**
     * Get all students with statistics
     * @return array|false
     */
    public function getAllStudentsWithStats() {
        $query = "SELECT s.*, 
                  COUNT(DISTINCT a.id) as total_attendance,
                  COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as present_count
                  FROM students s
                  LEFT JOIN attendance a ON s.id = a.student_id
                  GROUP BY s.id
                  ORDER BY s.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get All Students With Stats Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all coordinators with statistics
     * @return array|false
     */
    public function getAllCoordinatorsWithStats() {
        $query = "SELECT c.*, 
                  COUNT(DISTINCT s.id) as assigned_students_count
                  FROM coordinators c
                  LEFT JOIN students s ON c.assigned_community = s.community
                  GROUP BY c.id
                  ORDER BY c.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get All Coordinators With Stats Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get activity logs
     * @param int $limit
     * @return array|false
     */
    public function getActivityLogs($limit = 100) {
        $query = "SELECT * FROM activity_logs 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get Activity Logs Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get system statistics
     * @return array|false
     */
    public function getSystemStatistics() {
        try {
            $stats = [];
            
            // Total students
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM students");
            $stats['total_students'] = $stmt->fetch()['count'];
            
            // Total coordinators
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM coordinators");
            $stats['total_coordinators'] = $stmt->fetch()['count'];
            
            // Total attendance records
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM attendance");
            $stats['total_attendance'] = $stmt->fetch()['count'];
            
            // Today's attendance
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE()");
            $stats['today_attendance'] = $stmt->fetch()['count'];
            
            // Total messages
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM messages");
            $stats['total_messages'] = $stmt->fetch()['count'];
            
            // Total reports
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM reports");
            $stats['total_reports'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch(PDOException $e) {
            error_log("Get System Statistics Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all messages (for monitoring)
     * @return array|false
     */
    public function getAllMessages() {
        $query = "SELECT m.*, 
                  CASE 
                    WHEN m.sender_role = 'student' THEN (SELECT full_name FROM students WHERE id = m.sender_id)
                    WHEN m.sender_role = 'coordinator' THEN (SELECT full_name FROM coordinators WHERE id = m.sender_id)
                  END as sender_name,
                  CASE 
                    WHEN m.receiver_role = 'coordinator' THEN (SELECT full_name FROM coordinators WHERE id = m.receiver_id)
                    WHEN m.receiver_role = 'admin' THEN (SELECT full_name FROM admins WHERE id = m.receiver_id)
                  END as receiver_name
                  FROM messages m
                  ORDER BY m.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get All Messages Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all reports
     * @return array|false
     */
    public function getAllReports() {
        $query = "SELECT r.*, c.full_name as coordinator_name, c.email as coordinator_email
                  FROM reports r
                  INNER JOIN coordinators c ON r.coordinator_id = c.id
                  ORDER BY r.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("Get All Reports Error: " . $e->getMessage());
            return false;
        }
    }
}
?>