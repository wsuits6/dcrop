<?php
/**
 * Coordinator Controller
 * DCROP System - backend/controllers/coordinatorController.php
 * Handles coordinator-related business logic and request processing
 */

require_once __DIR__ . '/../models/coordinatorModel.php';

class CoordinatorController {
    private $coordinatorModel;
    
    public function __construct() {
        $this->coordinatorModel = new CoordinatorModel();
    }
    
    /**
     * Login coordinator
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
        $coordinator = $this->coordinatorModel->authenticate($email, $password);
        
        if($coordinator) {
            // TEMPORARILY DISABLED EMAIL VERIFICATION CHECK
            // Uncomment when email system is implemented
            /*
            if($coordinator['email_verified'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Please verify your email before logging in'
                ];
            }
            */
            
            // Log activity
            $this->logActivity($coordinator['id'], 'login');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'coordinator' => $coordinator
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    /**
     * Get coordinator profile
     * @param int $coordinator_id
     * @return array Response
     */
    public function getProfile($coordinator_id) {
        $coordinator = $this->coordinatorModel->read($coordinator_id);
        
        if($coordinator) {
            return [
                'success' => true,
                'coordinator' => $coordinator
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Coordinator not found'
        ];
    }
    
    /**
     * Update coordinator profile
     * @param int $coordinator_id
     * @param array $data
     * @return array Response
     */
    public function updateProfile($coordinator_id, $data) {
        // Validate required fields
        if(empty($data['full_name']) || empty($data['email'])) {
            return [
                'success' => false,
                'message' => 'Full name and email are required'
            ];
        }
        
        $result = $this->coordinatorModel->update($coordinator_id, $data);
        
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
     * Get assigned students
     * @param int $coordinator_id
     * @return array Response
     */
    public function getAssignedStudents($coordinator_id) {
        $students = $this->coordinatorModel->getAssignedStudents($coordinator_id);
        
        if($students !== false) {
            return [
                'success' => true,
                'students' => $students,
                'count' => count($students)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve assigned students'
        ];
    }
    
    /**
     * View student attendance
     * @param int $coordinator_id
     * @param string $date_from (optional)
     * @param string $date_to (optional)
     * @return array Response
     */
    public function viewStudentAttendance($coordinator_id, $date_from = null, $date_to = null) {
        $attendance = $this->coordinatorModel->getStudentAttendance($coordinator_id, $date_from, $date_to);
        
        if($attendance !== false) {
            return [
                'success' => true,
                'attendance' => $attendance,
                'count' => count($attendance)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve attendance records'
        ];
    }
    
    /**
     * Get attendance summary/statistics
     * @param int $coordinator_id
     * @return array Response
     */
    public function getAttendanceStats($coordinator_id) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Get coordinator's community
            $coordinator = $this->coordinatorModel->read($coordinator_id);
            if(!$coordinator) {
                return [
                    'success' => false,
                    'message' => 'Coordinator not found'
                ];
            }
            
            $query = "SELECT 
                        COUNT(DISTINCT s.id) as total_students,
                        COUNT(DISTINCT a.id) as total_attendance_records,
                        COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as present_count,
                        COUNT(DISTINCT CASE WHEN a.status = 'absent' THEN a.id END) as absent_count,
                        COUNT(DISTINCT CASE WHEN a.date = CURDATE() THEN a.id END) as today_attendance
                      FROM students s
                      LEFT JOIN attendance a ON s.id = a.student_id
                      WHERE s.community = :community";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':community', $coordinator['assigned_community']);
            $stmt->execute();
            
            $stats = $stmt->fetch();
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch(PDOException $e) {
            error_log("Get Attendance Stats Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ];
        }
    }
    
    /**
     * Get received messages
     * @param int $coordinator_id
     * @return array Response
     */
    public function getReceivedMessages($coordinator_id) {
        $messages = $this->coordinatorModel->getReceivedMessages($coordinator_id);
        
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
     * Mark message as read
     * @param int $message_id
     * @return array Response
     */
    public function markMessageAsRead($message_id) {
        $query = "UPDATE messages SET is_read = 1 WHERE id = :message_id";
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':message_id', $message_id);
            
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Message marked as read'
                ];
            }
            
        } catch(PDOException $e) {
            error_log("Mark Message As Read Error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to mark message as read'
        ];
    }
    
    /**
     * Escalate issue to admin
     * @param int $coordinator_id
     * @param int $admin_id
     * @param string $message
     * @return array Response
     */
    public function escalateToAdmin($coordinator_id, $admin_id, $message) {
        if(empty($message)) {
            return [
                'success' => false,
                'message' => 'Message cannot be empty'
            ];
        }
        
        $result = $this->coordinatorModel->sendMessageToAdmin($coordinator_id, $admin_id, $message);
        
        if($result) {
            return [
                'success' => true,
                'message' => 'Issue escalated to admin successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to escalate issue'
        ];
    }
    
    /**
     * Create report
     * @param int $coordinator_id
     * @param string $report_type
     * @param string $content
     * @return array Response
     */
    public function createReport($coordinator_id, $report_type, $content) {
        // Validate inputs
        if(empty($report_type) || empty($content)) {
            return [
                'success' => false,
                'message' => 'Report type and content are required'
            ];
        }
        
        // Validate report type
        $valid_types = ['daily', 'weekly', 'monthly', 'incident'];
        if(!in_array($report_type, $valid_types)) {
            return [
                'success' => false,
                'message' => 'Invalid report type'
            ];
        }
        
        $report_id = $this->coordinatorModel->createReport($coordinator_id, $report_type, $content);
        
        if($report_id) {
            // Log activity
            $this->logActivity($coordinator_id, 'report_generated');
            
            return [
                'success' => true,
                'message' => 'Report created successfully',
                'report_id' => $report_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to create report'
        ];
    }
    
    /**
     * Get reports
     * @param int $coordinator_id
     * @return array Response
     */
    public function getReports($coordinator_id) {
        $reports = $this->coordinatorModel->getReports($coordinator_id);
        
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
     * Get student login/logout activity
     * @param int $coordinator_id
     * @return array Response
     */
    public function getStudentActivity($coordinator_id) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Get coordinator's community
            $coordinator = $this->coordinatorModel->read($coordinator_id);
            if(!$coordinator) {
                return [
                    'success' => false,
                    'message' => 'Coordinator not found'
                ];
            }
            
            $query = "SELECT al.*, s.full_name, s.index_number, s.email
                      FROM activity_logs al
                      INNER JOIN students s ON al.user_id = s.id
                      WHERE al.user_role = 'student' 
                      AND s.community = :community
                      AND al.activity_type IN ('login', 'logout')
                      ORDER BY al.created_at DESC
                      LIMIT 100";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':community', $coordinator['assigned_community']);
            $stmt->execute();
            
            $activity = $stmt->fetchAll();
            
            return [
                'success' => true,
                'activity' => $activity,
                'count' => count($activity)
            ];
            
        } catch(PDOException $e) {
            error_log("Get Student Activity Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve student activity'
            ];
        }
    }
    
    /**
     * Verify email with token
     * @param string $token
     * @return array Response
     */
    public function verifyEmail($token) {
        if(empty($token)) {
            return [
                'success' => false,
                'message' => 'Verification token is required'
            ];
        }
        
        $result = $this->coordinatorModel->verifyEmail($token);
        
        if($result) {
            return [
                'success' => true,
                'message' => 'Email verified successfully. You can now login.'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid or expired verification token'
        ];
    }
    
    /**
     * Log coordinator activity
     * @param int $coordinator_id
     * @param string $activity_type
     * @return bool
     */
    private function logActivity($coordinator_id, $activity_type) {
        $query = "INSERT INTO activity_logs (user_id, user_role, activity_type, ip_address, user_agent) 
                  VALUES (:user_id, 'coordinator', :activity_type, :ip_address, :user_agent)";
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($query);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->bindParam(':user_id', $coordinator_id);
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