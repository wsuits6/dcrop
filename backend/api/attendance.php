<?php
/**
 * Attendance API Endpoint
 * DCROP System - backend/api/attendance.php
 * Handles attendance-related API requests
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../controllers/studentController.php';
require_once __DIR__ . '/../controllers/coordinatorController.php';
require_once __DIR__ . '/../controllers/adminController.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight request
if($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get action from query parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    switch($method) {
        case 'POST':
            handlePost($action);
            break;
            
        case 'GET':
            handleGet($action);
            break;
            
        case 'PUT':
            handlePut($action);
            break;
            
        case 'DELETE':
            handleDelete($action);
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            echo json_encode($response);
            break;
    }
    
} catch(Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode($response);
}

/**
 * Handle POST requests
 */
function handlePost($action) {
    global $response;
    
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!$data) {
        $response['message'] = 'Invalid JSON data';
        http_response_code(400);
        echo json_encode($response);
        return;
    }
    
    switch($action) {
        case 'submit':
            // Submit attendance
            if(!isset($data['student_id']) || !isset($data['date']) || 
               !isset($data['latitude']) || !isset($data['longitude'])) {
                $response['message'] = 'Missing required fields: student_id, date, latitude, longitude';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new StudentController();
            $result = $controller->submitAttendance($data['student_id'], [
                'date' => $data['date'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude']
            ]);
            
            http_response_code($result['success'] ? 201 : 400);
            echo json_encode($result);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            break;
    }
}

/**
 * Handle GET requests
 */
function handleGet($action) {
    global $response;
    
    switch($action) {
        case 'history':
            // Get student attendance history
            if(!isset($_GET['student_id'])) {
                $response['message'] = 'Missing required parameter: student_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new StudentController();
            $result = $controller->getAttendanceHistory($_GET['student_id']);
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'student_attendance':
            // Coordinator viewing student attendance
            if(!isset($_GET['coordinator_id'])) {
                $response['message'] = 'Missing required parameter: coordinator_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
            $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
            
            $controller = new CoordinatorController();
            $result = $controller->viewStudentAttendance($_GET['coordinator_id'], $date_from, $date_to);
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'stats':
            // Get attendance statistics for coordinator
            if(!isset($_GET['coordinator_id'])) {
                $response['message'] = 'Missing required parameter: coordinator_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new CoordinatorController();
            $result = $controller->getAttendanceStats($_GET['coordinator_id']);
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'overview':
            // Admin attendance overview
            $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
            $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
            
            $controller = new AdminController();
            $result = $controller->getAttendanceOverview($date_from, $date_to);
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'verify':
            // Get attendance record for verification
            if(!isset($_GET['attendance_id'])) {
                $response['message'] = 'Missing required parameter: attendance_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            require_once __DIR__ . '/../config/db.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT a.*, s.full_name, s.index_number, s.email, s.community 
                      FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      WHERE a.id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_GET['attendance_id']);
            $stmt->execute();
            
            $record = $stmt->fetch();
            
            if($record) {
                $response = [
                    'success' => true,
                    'attendance' => $record
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Attendance record not found';
                http_response_code(404);
            }
            
            echo json_encode($response);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            break;
    }
}

/**
 * Handle PUT requests
 */
function handlePut($action) {
    global $response;
    
    // Get PUT data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!$data) {
        $response['message'] = 'Invalid JSON data';
        http_response_code(400);
        echo json_encode($response);
        return;
    }
    
    switch($action) {
        case 'verify':
            // Verify attendance record
            if(!isset($data['attendance_id'])) {
                $response['message'] = 'Missing required field: attendance_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            require_once __DIR__ . '/../config/db.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "UPDATE attendance 
                      SET verified = 1, status = 'present' 
                      WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $data['attendance_id']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Attendance verified successfully'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to verify attendance';
                http_response_code(400);
            }
            
            echo json_encode($response);
            break;
            
        case 'update_status':
            // Update attendance status
            if(!isset($data['attendance_id']) || !isset($data['status'])) {
                $response['message'] = 'Missing required fields: attendance_id, status';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $valid_statuses = ['present', 'absent', 'pending'];
            if(!in_array($data['status'], $valid_statuses)) {
                $response['message'] = 'Invalid status value';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            require_once __DIR__ . '/../config/db.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "UPDATE attendance SET status = :status WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':id', $data['attendance_id']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Attendance status updated successfully'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to update attendance status';
                http_response_code(400);
            }
            
            echo json_encode($response);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            break;
    }
}

/**
 * Handle DELETE requests
 */
function handleDelete($action) {
    global $response;
    
    switch($action) {
        case 'delete':
            // Delete attendance record
            if(!isset($_GET['attendance_id'])) {
                $response['message'] = 'Missing required parameter: attendance_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            require_once __DIR__ . '/../config/db.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "DELETE FROM attendance WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_GET['attendance_id']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Attendance record deleted successfully'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to delete attendance record';
                http_response_code(400);
            }
            
            echo json_encode($response);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            break;
    }
}
?>