<?php

// api/performance_api.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/PerformanceModel.php';

$database = new Database();
$db = $database->getConnection();
$performance = new PerformanceModel($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet($performance);
        break;
    case 'POST':
        handlePost($performance, $input);
        break;
    case 'PUT':
        handlePut($performance, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($performance) {
    $action = $_GET['action'] ?? '';
    $admin_id = $_GET['admin_id'] ?? 1;
    
    switch ($action) {
        case 'dashboard':
            $data = [
                'daily_summary' => $performance->getDailySummary($admin_id),
                'benchmarks' => $performance->getSystemBenchmarks(),
                'real_time' => $performance->getRealTimeMetrics($admin_id),
                'trends' => $performance->getPerformanceTrends($admin_id)
            ];
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'benchmarks':
            $benchmarks = $performance->getSystemBenchmarks();
            echo json_encode($benchmarks, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'trends':
            $trends = $performance->getPerformanceTrends($admin_id);
            echo json_encode($trends, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'realtime':
            $metrics = $performance->getRealTimeMetrics($admin_id);
            echo json_encode($metrics, JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($performance, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'start_task':
            $task_id = $performance->startTask(
                $input['admin_id'],
                $input['task_type'],
                $input['description'] ?? ''
            );
            echo json_encode(['task_id' => $task_id, 'status' => 'started']);
            break;
            
        case 'complete_task':
            $result = $performance->completeTask(
                $input['task_id'],
                $input['status'] ?? 'completed',
                $input['notes'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'record_metric':
            $result = $performance->recordMetric(
                $input['metric_type'],
                $input['metric_value'],
                $input['admin_id'],
                $input['related_entity_id'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'update_daily_summary':
            $result = $performance->updateDailySummary(
                $input['admin_id'],
                $input['date'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($performance, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update_benchmark':
            $result = $performance->updateBenchmark(
                $input['benchmark_name'],
                $input['current_value']
            );
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

// Helper functions for automatic performance tracking
class PerformanceTracker {
    private $performance;
    private $admin_id;
    
    public function __construct($performance_model, $admin_id) {
        $this->performance = $performance_model;
        $this->admin_id = $admin_id;
    }
    
    // Track page load time
    public function trackPageLoad($page_name, $load_time) {
        $this->performance->recordMetric(
            'page_load_time', 
            $load_time, 
            $this->admin_id
        );
        
        // Update system benchmark
        $this->performance->updateBenchmark('Page Load Time', $load_time);
    }
    
    // Track database query time
    public function trackQueryTime($query_time) {
        $this->performance->recordMetric(
            'database_query_time', 
            $query_time, 
            $this->admin_id
        );
        
        // Update system benchmark
        $this->performance->updateBenchmark('Database Query Time', $query_time);
    }
    
    // Auto-update daily summary at end of day
    public function updateDailyMetrics() {
        $this->performance->updateDailySummary($this->admin_id);
    }
}
