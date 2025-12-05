<?php
/**
 * Messages API Endpoint
 * DCROP System - backend/api/messages.php
 * Handles messaging-related API requests
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../controllers/studentController.php';
require_once __DIR__ . '/../controllers/coordinatorController.php';
require_once __DIR__ . '/../controllers/adminController.php';
require_once __DIR__ . '/../config/db.php';

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
        case 'send':
            // Send message
            if(!isset($data['sender_id']) || !isset($data['receiver_id']) || 
               !isset($data['sender_role']) || !isset($data['receiver_role']) || 
               !isset($data['message'])) {
                $response['message'] = 'Missing required fields';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            // Validate roles
            $valid_roles = ['student', 'coordinator', 'admin', 'super_user'];
            if(!in_array($data['sender_role'], $valid_roles) || 
               !in_array($data['receiver_role'], $valid_roles)) {
                $response['message'] = 'Invalid role specified';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, message) 
                      VALUES (:sender_id, :receiver_id, :sender_role, :receiver_role, :message)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':sender_id', $data['sender_id']);
            $stmt->bindParam(':receiver_id', $data['receiver_id']);
            $stmt->bindParam(':sender_role', $data['sender_role']);
            $stmt->bindParam(':receiver_role', $data['receiver_role']);
            $stmt->bindParam(':message', $data['message']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'message_id' => $conn->lastInsertId()
                ];
                http_response_code(201);
            } else {
                $response['message'] = 'Failed to send message';
                http_response_code(400);
            }
            
            echo json_encode($response);
            break;
            
        case 'send_to_coordinator':
            // Student sending message to coordinator
            if(!isset($data['student_id']) || !isset($data['coordinator_id']) || 
               !isset($data['message'])) {
                $response['message'] = 'Missing required fields: student_id, coordinator_id, message';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new StudentController();
            $result = $controller->sendMessage(
                $data['student_id'], 
                $data['coordinator_id'], 
                $data['message']
            );
            
            http_response_code($result['success'] ? 201 : 400);
            echo json_encode($result);
            break;
            
        case 'escalate':
            // Coordinator escalating to admin
            if(!isset($data['coordinator_id']) || !isset($data['admin_id']) || 
               !isset($data['message'])) {
                $response['message'] = 'Missing required fields: coordinator_id, admin_id, message';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new CoordinatorController();
            $result = $controller->escalateToAdmin(
                $data['coordinator_id'], 
                $data['admin_id'], 
                $data['message']
            );
            
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
        case 'received':
            // Get received messages
            if(!isset($_GET['user_id']) || !isset($_GET['user_role'])) {
                $response['message'] = 'Missing required parameters: user_id, user_role';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT m.*, 
                      CASE 
                        WHEN m.sender_role = 'student' THEN (SELECT full_name FROM students WHERE id = m.sender_id)
                        WHEN m.sender_role = 'coordinator' THEN (SELECT full_name FROM coordinators WHERE id = m.sender_id)
                        WHEN m.sender_role = 'admin' THEN (SELECT full_name FROM admins WHERE id = m.sender_id)
                        WHEN m.sender_role = 'super_user' THEN (SELECT full_name FROM super_users WHERE id = m.sender_id)
                      END as sender_name,
                      CASE 
                        WHEN m.sender_role = 'student' THEN (SELECT email FROM students WHERE id = m.sender_id)
                        WHEN m.sender_role = 'coordinator' THEN (SELECT email FROM coordinators WHERE id = m.sender_id)
                        WHEN m.sender_role = 'admin' THEN (SELECT email FROM admins WHERE id = m.sender_id)
                        WHEN m.sender_role = 'super_user' THEN (SELECT email FROM super_users WHERE id = m.sender_id)
                      END as sender_email
                      FROM messages m
                      WHERE m.receiver_id = :user_id AND m.receiver_role = :user_role
                      ORDER BY m.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_GET['user_id']);
            $stmt->bindParam(':user_role', $_GET['user_role']);
            $stmt->execute();
            
            $messages = $stmt->fetchAll();
            
            $response = [
                'success' => true,
                'messages' => $messages,
                'count' => count($messages)
            ];
            
            http_response_code(200);
            echo json_encode($response);
            break;
            
        case 'sent':
            // Get sent messages
            if(!isset($_GET['user_id']) || !isset($_GET['user_role'])) {
                $response['message'] = 'Missing required parameters: user_id, user_role';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT m.*, 
                      CASE 
                        WHEN m.receiver_role = 'student' THEN (SELECT full_name FROM students WHERE id = m.receiver_id)
                        WHEN m.receiver_role = 'coordinator' THEN (SELECT full_name FROM coordinators WHERE id = m.receiver_id)
                        WHEN m.receiver_role = 'admin' THEN (SELECT full_name FROM admins WHERE id = m.receiver_id)
                        WHEN m.receiver_role = 'super_user' THEN (SELECT full_name FROM super_users WHERE id = m.receiver_id)
                      END as receiver_name,
                      CASE 
                        WHEN m.receiver_role = 'student' THEN (SELECT email FROM students WHERE id = m.receiver_id)
                        WHEN m.receiver_role = 'coordinator' THEN (SELECT email FROM coordinators WHERE id = m.receiver_id)
                        WHEN m.receiver_role = 'admin' THEN (SELECT email FROM admins WHERE id = m.receiver_id)
                        WHEN m.receiver_role = 'super_user' THEN (SELECT email FROM super_users WHERE id = m.receiver_id)
                      END as receiver_email
                      FROM messages m
                      WHERE m.sender_id = :user_id AND m.sender_role = :user_role
                      ORDER BY m.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_GET['user_id']);
            $stmt->bindParam(':user_role', $_GET['user_role']);
            $stmt->execute();
            
            $messages = $stmt->fetchAll();
            
            $response = [
                'success' => true,
                'messages' => $messages,
                'count' => count($messages)
            ];
            
            http_response_code(200);
            echo json_encode($response);
            break;
            
        case 'coordinator_messages':
            // Get messages for coordinator
            if(!isset($_GET['coordinator_id'])) {
                $response['message'] = 'Missing required parameter: coordinator_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new CoordinatorController();
            $result = $controller->getReceivedMessages($_GET['coordinator_id']);
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'all':
            // Admin view all messages
            $controller = new AdminController();
            $result = $controller->getAllMessages();
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'unread_count':
            // Get unread message count
            if(!isset($_GET['user_id']) || !isset($_GET['user_role'])) {
                $response['message'] = 'Missing required parameters: user_id, user_role';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT COUNT(*) as count FROM messages 
                      WHERE receiver_id = :user_id 
                      AND receiver_role = :user_role 
                      AND is_read = 0";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_GET['user_id']);
            $stmt->bindParam(':user_role', $_GET['user_role']);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            $response = [
                'success' => true,
                'unread_count' => $result['count']
            ];
            
            http_response_code(200);
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
        case 'mark_read':
            // Mark message as read
            if(!isset($data['message_id'])) {
                $response['message'] = 'Missing required field: message_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new CoordinatorController();
            $result = $controller->markMessageAsRead($data['message_id']);
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        case 'mark_all_read':
            // Mark all messages as read for a user
            if(!isset($data['user_id']) || !isset($data['user_role'])) {
                $response['message'] = 'Missing required fields: user_id, user_role';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "UPDATE messages SET is_read = 1 
                      WHERE receiver_id = :user_id AND receiver_role = :user_role";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':user_role', $data['user_role']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'All messages marked as read'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to mark messages as read';
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
            // Delete message
            if(!isset($_GET['message_id'])) {
                $response['message'] = 'Missing required parameter: message_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "DELETE FROM messages WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_GET['message_id']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Message deleted successfully'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to delete message';
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