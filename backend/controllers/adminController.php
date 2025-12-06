<?php
/**
 * Admin Controller
 * DCROP System - backend/controllers/adminController.php
 * Handles admin-related business logic and request processing
 */

require_once __DIR__ . '/../models/adminModel.php';
// require_once __DIR__ . '/../vendor/autoload.php'; // For PhpSpreadsheet (Excel handling)

class AdminController {
    private $adminModel;
    
    public function __construct() {
        $this->adminModel = new AdminModel();
    }
    
    /**
     * Login admin
     * @param string $email
     * @param string $password
     * @return array Response
     */
    public function login($email, $password) {
        // Validate inputs
        if(empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }
        
        // Authenticate
        $admin = $this->adminModel->authenticate($email, $password);
        
        if($admin) {
            // Log activity
            $this->logActivity($admin['id'], 'login');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'admin' => $admin
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    /**
     * Get admin profile
     * @param int $admin_id
     * @return array Response
     */
    public function getProfile($admin_id) {
        $admin = $this->adminModel->read($admin_id);
        
        if($admin) {
            return [
                'success' => true,
                'admin' => $admin
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Admin not found'
        ];
    }
    
    /**
     * Update admin profile
     * @param int $admin_id
     * @param array $data
     * @return array Response
     */
    public function updateProfile($admin_id, $data) {
        // Validate required fields
        if(empty($data['full_name']) || empty($data['email'])) {
            return [
                'success' => false,
                'message' => 'Full name and email are required'
            ];
        }
        
        $result = $this->adminModel->update($admin_id, $data);
        
        if($result) {
            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to update profile'
        ];
    }
    
    /**
     * Upload student data from Excel
     * @param string $file_path
     * @return array Response
     */
    public function uploadStudentData($file_path) {
        if(!file_exists($file_path)) {
            return [
                'success' => false,
                'message' => 'File not found'
            ];
        }
        
        try {
            // Parse Excel file
            $students = $this->parseExcelFile($file_path, 'students');
            
            if(empty($students)) {
                return [
                    'success' => false,
                    'message' => 'No valid student data found in file'
                ];
            }
            
            // Upload to database
            $result = $this->adminModel->uploadStudentData($students);
            
            return $result;
            
        } catch(Exception $e) {
            error_log("Upload Student Data Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process Excel file: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload coordinator data from Excel
     * @param string $file_path
     * @return array Response
     */
    public function uploadCoordinatorData($file_path) {
        if(!file_exists($file_path)) {
            return [
                'success' => false,
                'message' => 'File not found'
            ];
        }
        
        try {
            // Parse Excel file
            $coordinators = $this->parseExcelFile($file_path, 'coordinators');
            
            if(empty($coordinators)) {
                return [
                    'success' => false,
                    'message' => 'No valid coordinator data found in file'
                ];
            }
            
            // Upload to database
            $result = $this->adminModel->uploadCoordinatorData($coordinators);
            
            return $result;
            
        } catch(Exception $e) {
            error_log("Upload Coordinator Data Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process Excel file: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Parse Excel file
     * @param string $file_path
     * @param string $type ('students' or 'coordinators')
     * @return array
     */
    private function parseExcelFile($file_path, $type) {
        // Placeholder for Excel parsing
        // TODO: Implement PhpSpreadsheet library for Excel parsing
        
        // Expected format for students Excel:
        // Column A: Full Name
        // Column B: Index Number
        // Column C: Email
        // Column D: Community
        
        // Expected format for coordinators Excel:
        // Column A: Full Name
        // Column B: Email
        // Column C: Assigned Community
        
        // For now, return empty array
        // Implement actual Excel parsing using PhpSpreadsheet library
        
        return [];
    }
    
    /**
     * Get all students with statistics
     * @return array Response
     */
    public function getAllStudents() {
        $students = $this->adminModel->getAllStudentsWithStats();
        
        if($students !== false) {
            return [
                'success' => true,
                'students' => $students,
                'count' => count($students)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve students'
        ];
    }
    
    /**
     * Get all coordinators with statistics
     * @return array Response
     */
    public function getAllCoordinators() {
        $coordinators = $this->adminModel->getAllCoordinatorsWithStats();
        
        if($coordinators !== false) {
            return [
                'success' => true,
                'coordinators' => $coordinators,
                'count' => count($coordinators)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve coordinators'
        ];
    }
    
    /**
     * Get system statistics
     * @return array Response
     */
    public function getSystemStatistics() {
        $stats = $this->adminModel->getSystemStatistics();
        
        if($stats !== false) {
            return [
                'success' => true,
                'statistics' => $stats
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve statistics'
        ];
    }
    
    /**
     * Get activity logs
     * @param int $limit
     * @return array Response
     */
    public function getActivityLogs($limit = 100) {
        $logs = $this->adminModel->getActivityLogs($limit);
        
        if($logs !== false) {
            return [
                'success' => true,
                'logs' => $logs,
                'count' => count($logs)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve activity logs'
        ];
    }
    
    /**
     * Get all messages
     * @return array Response
     */
    public function getAllMessages() {
        $messages = $this->adminModel->getAllMessages();
        
        if($messages !== false) {
            return [
                'success' => true,
                'messages' => $messages,
                'count' => count($messages)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve messages'
        ];
    }
    
    /**
     * Get all reports
     * @return array Response
     */
    public function getAllReports() {
        $reports = $this->adminModel->getAllReports();
        
        if($reports !== false) {
            return [
                'success' => true,
                'reports' => $reports,
                'count' => count($reports)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve reports'
        ];
    }
    
    /**
     * Delete student
     * @param int $student_id
     * @return array Response
     */
    public function deleteStudent($student_id) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "DELETE FROM students WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $student_id);
            
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Student deleted successfully'
                ];
            }
            
        } catch(PDOException $e) {
            error_log("Delete Student Error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to delete student'
        ];
    }
    
    /**
     * Delete coordinator
     * @param int $coordinator_id
     * @return array Response
     */
    public function deleteCoordinator($coordinator_id) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "DELETE FROM coordinators WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $coordinator_id);
            
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Coordinator deleted successfully'
                ];
            }
            
        } catch(PDOException $e) {
            error_log("Delete Coordinator Error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to delete coordinator'
        ];
    }
    
    /**
     * Get attendance overview
     * @param string $date_from
     * @param string $date_to
     * @return array Response
     */
    public function getAttendanceOverview($date_from = null, $date_to = null) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT 
                        DATE(a.date) as attendance_date,
                        COUNT(DISTINCT a.student_id) as students_marked,
                        COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.student_id END) as present_count,
                        COUNT(DISTINCT CASE WHEN a.status = 'absent' THEN a.student_id END) as absent_count,
                        COUNT(DISTINCT CASE WHEN a.verified = 1 THEN a.student_id END) as verified_count
                      FROM attendance a";
            
            $conditions = [];
            if($date_from) {
                $conditions[] = "a.date >= :date_from";
            }
            if($date_to) {
                $conditions[] = "a.date <= :date_to";
            }
            
            if(!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " GROUP BY DATE(a.date) ORDER BY a.date DESC";
            
            $stmt = $conn->prepare($query);
            
            if($date_from) {
                $stmt->bindParam(':date_from', $date_from);
            }
            if($date_to) {
                $stmt->bindParam(':date_to', $date_to);
            }
            
            $stmt->execute();
            $overview = $stmt->fetchAll();
            
            return [
                'success' => true,
                'overview' => $overview,
                'count' => count($overview)
            ];
            
        } catch(PDOException $e) {
            error_log("Get Attendance Overview Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve attendance overview'
            ];
        }
    }
    
    /**
     * Generate analytics report
     * @return array Response
     */
    public function generateAnalyticsReport() {
        $stats = $this->adminModel->getSystemStatistics();
        
        if($stats === false) {
            return [
                'success' => false,
                'message' => 'Failed to generate report'
            ];
        }
        
        // Calculate additional metrics
        $attendance_rate = $stats['total_students'] > 0 ? 
            ($stats['today_attendance'] / $stats['total_students']) * 100 : 0;
        
        $analytics = [
            'system_stats' => $stats,
            'attendance_rate' => round($attendance_rate, 2) . '%',
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        return [
            'success' => true,
            'analytics' => $analytics
        ];
    }
    
    /**
     * Log admin activity
     * @param int $admin_id
     * @param string $activity_type
     * @return bool
     */
    private function logActivity($admin_id, $activity_type) {
        $query = "INSERT INTO activity_logs (user_id, user_role, activity_type, ip_address, user_agent) 
                  VALUES (:user_id, 'admin', :activity_type, :ip_address, :user_agent)";
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($query);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->bindParam(':user_id', $admin_id);
            $stmt->bindParam(':activity_type', $activity_type);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Log Activity Error: " . $e->getMessage());
            return false;
        }
    }
}
?>