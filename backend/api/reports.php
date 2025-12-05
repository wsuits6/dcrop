<?php
/**
 * Reports API Endpoint
 * DCROP System - backend/api/reports.php
 * Handles report-related API requests
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
        case 'create':
            // Create report
            if(!isset($data['coordinator_id']) || !isset($data['report_type']) || 
               !isset($data['content'])) {
                $response['message'] = 'Missing required fields: coordinator_id, report_type, content';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new CoordinatorController();
            $result = $controller->createReport(
                $data['coordinator_id'], 
                $data['report_type'], 
                $data['content']
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
        case 'coordinator_reports':
            // Get reports by coordinator
            if(!isset($_GET['coordinator_id'])) {
                $response['message'] = 'Missing required parameter: coordinator_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $controller = new CoordinatorController();
            $result = $controller->getReports($_GET['coordinator_id']);
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'all':
            // Admin view all reports
            $controller = new AdminController();
            $result = $controller->getAllReports();
            
            http_response_code($result['success'] ? 200 : 404);
            echo json_encode($result);
            break;
            
        case 'by_type':
            // Get reports by type
            if(!isset($_GET['report_type'])) {
                $response['message'] = 'Missing required parameter: report_type';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $valid_types = ['daily', 'weekly', 'monthly', 'incident'];
            if(!in_array($_GET['report_type'], $valid_types)) {
                $response['message'] = 'Invalid report type';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT r.*, c.full_name as coordinator_name, c.email as coordinator_email, 
                      c.assigned_community
                      FROM reports r
                      INNER JOIN coordinators c ON r.coordinator_id = c.id
                      WHERE r.report_type = :report_type
                      ORDER BY r.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':report_type', $_GET['report_type']);
            $stmt->execute();
            
            $reports = $stmt->fetchAll();
            
            $response = [
                'success' => true,
                'reports' => $reports,
                'count' => count($reports)
            ];
            
            http_response_code(200);
            echo json_encode($response);
            break;
            
        case 'by_date_range':
            // Get reports by date range
            if(!isset($_GET['date_from']) || !isset($_GET['date_to'])) {
                $response['message'] = 'Missing required parameters: date_from, date_to';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT r.*, c.full_name as coordinator_name, c.email as coordinator_email, 
                      c.assigned_community
                      FROM reports r
                      INNER JOIN coordinators c ON r.coordinator_id = c.id
                      WHERE DATE(r.created_at) BETWEEN :date_from AND :date_to
                      ORDER BY r.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':date_from', $_GET['date_from']);
            $stmt->bindParam(':date_to', $_GET['date_to']);
            $stmt->execute();
            
            $reports = $stmt->fetchAll();
            
            $response = [
                'success' => true,
                'reports' => $reports,
                'count' => count($reports)
            ];
            
            http_response_code(200);
            echo json_encode($response);
            break;
            
        case 'single':
            // Get single report details
            if(!isset($_GET['report_id'])) {
                $response['message'] = 'Missing required parameter: report_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT r.*, c.full_name as coordinator_name, c.email as coordinator_email, 
                      c.assigned_community
                      FROM reports r
                      INNER JOIN coordinators c ON r.coordinator_id = c.id
                      WHERE r.id = :report_id
                      LIMIT 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':report_id', $_GET['report_id']);
            $stmt->execute();
            
            $report = $stmt->fetch();
            
            if($report) {
                $response = [
                    'success' => true,
                    'report' => $report
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Report not found';
                http_response_code(404);
            }
            
            echo json_encode($response);
            break;
            
        case 'statistics':
            // Get report statistics
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT 
                        COUNT(*) as total_reports,
                        COUNT(DISTINCT coordinator_id) as coordinators_reporting,
                        COUNT(CASE WHEN report_type = 'daily' THEN 1 END) as daily_reports,
                        COUNT(CASE WHEN report_type = 'weekly' THEN 1 END) as weekly_reports,
                        COUNT(CASE WHEN report_type = 'monthly' THEN 1 END) as monthly_reports,
                        COUNT(CASE WHEN report_type = 'incident' THEN 1 END) as incident_reports,
                        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_reports,
                        COUNT(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) THEN 1 END) as this_week_reports
                      FROM reports";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $stats = $stmt->fetch();
            
            $response = [
                'success' => true,
                'statistics' => $stats
            ];
            
            http_response_code(200);
            echo json_encode($response);
            break;
            
        case 'export':
            // Export reports (placeholder for CSV/PDF export)
            $response['message'] = 'Export functionality coming soon';
            http_response_code(501);
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
        case 'update':
            // Update report
            if(!isset($data['report_id']) || !isset($data['content'])) {
                $response['message'] = 'Missing required fields: report_id, content';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "UPDATE reports SET content = :content WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':id', $data['report_id']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Report updated successfully'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to update report';
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
            // Delete report
            if(!isset($_GET['report_id'])) {
                $response['message'] = 'Missing required parameter: report_id';
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "DELETE FROM reports WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_GET['report_id']);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Report deleted successfully'
                ];
                http_response_code(200);
            } else {
                $response['message'] = 'Failed to delete report';
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