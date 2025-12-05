<?php
/**
 * Student Controller
 * DCROP System - backend/controllers/studentController.php
 * Handles student-related business logic and request processing
 */

require_once __DIR__ . '/../models/studentModel.php';

class StudentController {
    private $studentModel;
    
    public function __construct() {
        $this->studentModel = new StudentModel();
    }
    
    /**
     * Register new student
     * @param array $data
     * @return array Response
     */
    public function register($data) {
        // Validate required fields
        if(empty($data['full_name']) || empty($data['index_number']) || 
           empty($data['email']) || empty($data['password']) || empty($data['community'])) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }
        
        // Validate email format
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        // Check if index number exists in system (must be pre-registered)
        $existing = $this->studentModel->getByIndexNumber($data['index_number']);
        if(!$existing) {
            return [
                'success' => false,
                'message' => 'Index number not found. Please contact admin for registration.'
            ];
        }
        
        // Check if student already signed up
        if($existing['email'] !== null && $existing['password'] !== null) {
            return [
                'success' => false,
                'message' => 'This index number is already registered'
            ];
        }
        
        // Generate verification token
        $data['verification_token'] = bin2hex(random_bytes(32));
        
        // Create student account
        $student_id = $this->studentModel->create($data);
        
        if($student_id) {
            // TODO: Send verification email
            return [
                'success' => true,
                'message' => 'Registration successful. Please verify your email.',
                'student_id' => $student_id,
                'verification_token' => $data['verification_token']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ];
    }
    
    /**
     * Login student
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
        $student = $this->studentModel->authenticate($email, $password);
        
        if($student) {
            // Check email verification
            if($student['email_verified'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Please verify your email before logging in'
                ];
            }
            
            // TODO: Log activity
            $this->logActivity($student['id'], 'login');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'student' => $student
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    /**
     * Verify email
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
        
        $result = $this->studentModel->verifyEmail($token);
        
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
     * Get student profile
     * @param int $student_id
     * @return array Response
     */
    public function getProfile($student_id) {
        $student = $this->studentModel->read($student_id);
        
        if($student) {
            return [
                'success' => true,
                'student' => $student
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Student not found'
        ];
    }
    
    /**
     * Update student profile
     * @param int $student_id
     * @param array $data
     * @return array Response
     */
    public function updateProfile($student_id, $data) {
        // Validate required fields
        if(empty($data['full_name']) || empty($data['email'])) {
            return [
                'success' => false,
                'message' => 'Full name and email are required'
            ];
        }
        
        $result = $this->studentModel->update($student_id, $data);
        
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
     * Submit attendance
     * @param int $student_id
     * @param array $data (date, latitude, longitude)
     * @return array Response
     */
    public function submitAttendance($student_id, $data) {
        // Validate required fields
        if(empty($data['date']) || empty($data['latitude']) || empty($data['longitude'])) {
            return [
                'success' => false,
                'message' => 'Date and location are required'
            ];
        }
        
        // Get student info
        $student = $this->studentModel->read($student_id);
        if(!$student) {
            return [
                'success' => false,
                'message' => 'Student not found'
            ];
        }
        
        // Verify location (simple radius check - can be enhanced)
        $location_verified = $this->verifyLocation(
            $data['latitude'], 
            $data['longitude'], 
            $student['community']
        );
        
        // Prepare attendance data
        $attendance_data = [
            'student_id' => $student_id,
            'date' => $data['date'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'status' => $location_verified ? 'present' : 'pending',
            'verified' => $location_verified ? 1 : 0
        ];
        
        // Insert attendance record
        $query = "INSERT INTO attendance (student_id, date, latitude, longitude, status, verified) 
                  VALUES (:student_id, :date, :latitude, :longitude, :status, :verified)
                  ON DUPLICATE KEY UPDATE 
                  latitude = :latitude, longitude = :longitude, 
                  status = :status, verified = :verified";
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($query);
            
            $stmt->bindParam(':student_id', $attendance_data['student_id']);
            $stmt->bindParam(':date', $attendance_data['date']);
            $stmt->bindParam(':latitude', $attendance_data['latitude']);
            $stmt->bindParam(':longitude', $attendance_data['longitude']);
            $stmt->bindParam(':status', $attendance_data['status']);
            $stmt->bindParam(':verified', $attendance_data['verified']);
            
            if($stmt->execute()) {
                // Log activity
                $this->logActivity($student_id, 'attendance_submit');
                
                return [
                    'success' => true,
                    'message' => $location_verified ? 
                        'Attendance submitted and verified' : 
                        'Attendance submitted but location verification pending',
                    'verified' => $location_verified
                ];
            }
            
        } catch(PDOException $e) {
            error_log("Submit Attendance Error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to submit attendance'
        ];
    }
    
    /**
     * Get attendance history
     * @param int $student_id
     * @return array Response
     */
    public function getAttendanceHistory($student_id) {
        $history = $this->studentModel->getAttendanceHistory($student_id);
        
        if($history !== false) {
            return [
                'success' => true,
                'attendance' => $history
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to retrieve attendance history'
        ];
    }
    
    /**
     * Send message to coordinator
     * @param int $student_id
     * @param int $coordinator_id
     * @param string $message
     * @return array Response
     */
    public function sendMessage($student_id, $coordinator_id, $message) {
        if(empty($message)) {
            return [
                'success' => false,
                'message' => 'Message cannot be empty'
            ];
        }
        
        $query = "INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, message) 
                  VALUES (:sender_id, :receiver_id, 'student', 'coordinator', :message)";
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($query);
            
            $stmt->bindParam(':sender_id', $student_id);
            $stmt->bindParam(':receiver_id', $coordinator_id);
            $stmt->bindParam(':message', $message);
            
            if($stmt->execute()) {
                // Log activity
                $this->logActivity($student_id, 'message_sent');
                
                return [
                    'success' => true,
                    'message' => 'Message sent successfully'
                ];
            }
            
        } catch(PDOException $e) {
            error_log("Send Message Error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to send message'
        ];
    }
    
    /**
     * Verify location against community coordinates
     * @param float $latitude
     * @param float $longitude
     * @param string $community
     * @return bool
     */
    private function verifyLocation($latitude, $longitude, $community) {
        // TODO: Implement actual location verification logic
        // This is a placeholder - you should define community coordinates
        // and calculate distance using Haversine formula
        
        // For now, return true as placeholder
        return true;
        
        /* Example implementation:
        $community_coords = $this->getCommunityCoordinates($community);
        $distance = $this->calculateDistance(
            $latitude, $longitude,
            $community_coords['lat'], $community_coords['lng']
        );
        
        // Check if within acceptable radius (e.g., 500 meters)
        return $distance <= 0.5;
        */
    }
    
    /**
     * Log student activity
     * @param int $student_id
     * @param string $activity_type
     * @return bool
     */
    private function logActivity($student_id, $activity_type) {
        $query = "INSERT INTO activity_logs (user_id, user_role, activity_type, ip_address, user_agent) 
                  VALUES (:user_id, 'student', :activity_type, :ip_address, :user_agent)";
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($query);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->bindParam(':user_id', $student_id);
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