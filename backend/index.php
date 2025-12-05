<?php
/**
 * Backend Entry Point
 * DCROP System - backend/index.php
 * Main entry point for backend API routes and general requests
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/studentController.php';
require_once __DIR__ . '/controllers/coordinatorController.php';
require_once __DIR__ . '/controllers/adminController.php';

// Handle preflight OPTIONS request
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get endpoint from query parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Route requests
    switch($endpoint) {
        case 'test':
            // Test database connection
            testConnection();
            break;
            
        case 'login':
            // Handle login
            handleLogin();
            break;
            
        case 'register':
            // Handle student registration
            handleRegister();
            break;
            
        case 'verify_email':
            // Handle email verification
            handleEmailVerification();
            break;
            
        case 'profile':
            // Handle profile operations
            handleProfile();
            break;
            
        case 'logout':
            // Handle logout
            handleLogout();
            break;
            
        case 'system_stats':
            // Get system statistics
            handleSystemStats();
            break;
            
        case '':
            // Default welcome message
            $response = [
                'success' => true,
                'message' => 'Welcome to DCROP API',
                'version' => '1.0.0',
                'endpoints' => [
                    'test' => 'Test database connection',
                    'login' => 'User login',
                    'register' => 'Student registration',
                    'verify_email' => 'Email verification',
                    'profile' => 'User profile management',
                    'attendance' => 'api/attendance.php',
                    'messages' => 'api/messages.php',
                    'reports' => 'api/reports.php'
                ]
            ];
            http_response_code(200);
            echo json_encode($response);
            break;
            
        default:
            $response['message'] = 'Endpoint not found';
            http_response_code(404);
            echo json_encode($response);
            break;
    }
    
} catch(Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode($response);
}

/**
 * Test database connection
 */
function testConnection() {
    global $response;
    
    $database = new Database();
    
    if($database->testConnection()) {
        $response = [
            'success' => true,
            'message' => 'Database connection successful',
            'database' => 'dcrop_db'
        ];
        http_response_code(200);
    } else {
        $response = [
            'success' => false,
            'message' => 'Database connection failed'
        ];
        http_response_code(500);
    }
    
    echo json_encode($response);
}

/**
 * Handle login requests
 */
function handleLogin() {
    global $response, $method;
    
    if($method !== 'POST') {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!$data || !isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
        $response['message'] = 'Missing required fields: email, password, role';
        http_response_code(400);
        echo json_encode($response);
        return;
    }
    
    // Determine which controller to use based on role
    $result = null;
    
    switch($data['role']) {
        case 'student':
            $controller = new StudentController();
            $result = $controller->login($data['email'], $data['password']);
            break;
            
        case 'coordinator':
            $controller = new CoordinatorController();
            $result = $controller->login($data['email'], $data['password']);
            break;
            
        case 'admin':
        case 'super_user':
            $controller = new AdminController();
            $result = $controller->login($data['email'], $data['password']);
            break;
            
        default:
            $response['message'] = 'Invalid role';
            http_response_code(400);
            echo json_encode($response);
            return;
    }
    
    http_response_code($result['success'] ? 200 : 401);
    echo json_encode($result);
}

/**
 * Handle student registration
 */
function handleRegister() {
    global $response, $method;
    
    if($method !== 'POST') {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!$data) {
        $response['message'] = 'Invalid JSON data';
        http_response_code(400);
        echo json_encode($response);
        return;
    }
    
    $controller = new StudentController();
    $result = $controller->register($data);
    
    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);
}

/**
 * Handle email verification
 */
function handleEmailVerification() {
    global $response, $method;
    
    if($method !== 'GET') {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
        return;
    }
    
    if(!isset($_GET['token']) || !isset($_GET['role'])) {
        $response['message'] = 'Missing required parameters: token, role';
        http_response_code(400);
        echo json_encode($response);
        return;
    }
    
    $result = null;
    
    switch($_GET['role']) {
        case 'student':
            $controller = new StudentController();
            $result = $controller->verifyEmail($_GET['token']);
            break;
            
        case 'coordinator':
            $controller = new CoordinatorController();
            $result = $controller->verifyEmail($_GET['token']);
            break;
            
        default:
            $response['message'] = 'Invalid role';
            http_response_code(400);
            echo json_encode($response);
            return;
    }
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
}

/**
 * Handle profile operations
 */
function handleProfile() {
    global $response, $method;
    
    switch($method) {
        case 'GET':
            // Get profile
            if(!isset($_GET['user_id']) || !isset($_GET['role'])) {
                $response['message'] = 'Missing required parameters: user_id, role';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $result = null;
            
            switch($_GET['role']) {
                case 'student':
                    $controller = new StudentController();
                    $result = $controller->getProfile($_GET['user_id']);
                    break;
                    
                case 'coordinator':
                    $controller = new CoordinatorController();
                    $result = $controller->getProfile($_GET['user_id']);
                    break;
                    
                case 'admin':
                case 'super_user':
                    $controller = new AdminController();
                    $result = $controller->getProfile($_GET['user_id']);
                    break;
                    
                default:
                    $response['message'] = 'Invalid role';
                    http_response_code(400);
                    echo json_encode($response);
                    return;
            }
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'PUT':
            // Update profile
            $data = json_decode(file_get_contents("php://input"), true);
            
            if(!$data || !isset($data['user_id']) || !isset($data['role'])) {
                $response['message'] = 'Missing required fields: user_id, role';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $result = null;
            
            switch($data['role']) {
                case 'student':
                    $controller = new StudentController();
                    $result = $controller->updateProfile($data['user_id'], $data);
                    break;
                    
                case 'coordinator':
                    $controller = new CoordinatorController();
                    $result = $controller->updateProfile($data['user_id'], $data);
                    break;
                    
                case 'admin':
                case 'super_user':
                    $controller = new AdminController();
                    $result = $controller->updateProfile($data['user_id'], $data);
                    break;
                    
                default:
                    $response['message'] = 'Invalid role';
                    http_response_code(400);
                    echo json_encode($response);
                    return;
            }
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            echo json_encode($response);
            break;
    }
}

/**
 * Handle logout
 */
function handleLogout() {
    global $response, $method;
    
    if($method !== 'POST') {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!$data || !isset($data['user_id']) || !isset($data['role'])) {
        $response['message'] = 'Missing required fields: user_id, role';
        http_response_code(400);
        echo json_encode($response);
        return;
    }
    
    // Log logout activity
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, user_role, activity_type, ip_address, user_agent) 
              VALUES (:user_id, :user_role, 'logout', :ip_address, :user_agent)";
    
    $stmt = $conn->prepare($query);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt->bindParam(':user_id', $data['user_id']);
    $stmt->bindParam(':user_role', $data['role']);
    $stmt->bindParam(':ip_address', $ip_address);
    $stmt->bindParam(':user_agent', $user_agent);
    
    $stmt->execute();
    
    $response = [
        'success' => true,
        'message' => 'Logout successful'
    ];
    
    http_response_code(200);
    echo json_encode($response);
}

/**
 * Handle system statistics
 */
function handleSystemStats() {
    global $response, $method;
    
    if($method !== 'GET') {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
        return;
    }
    
    $controller = new AdminController();
    $result = $controller->getSystemStatistics();
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);
}
?>