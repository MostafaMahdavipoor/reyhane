<?php
// api/course_api.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/CourseModel.php';
require_once '../models/PerformanceModel.php';

$database = new Database();
$db = $database->getConnection();
$course = new CourseModel($db);
$performance = new PerformanceModel($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleCourseGet($course);
        break;
    case 'POST':
        handleCoursePost($course, $performance, $input);
        break;
    case 'PUT':
        handleCoursePut($course, $performance, $input);
        break;
    case 'DELETE':
        handleCourseDelete($course, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleCourseGet($course) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $status = $_GET['status'] ?? null;
            $courses = $course->getAllCourses($page, $limit, $status);
            echo json_encode($courses, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? 0;
            $courseData = $course->getCourseById($id);
            echo json_encode($courseData, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'lessons':
            $course_id = $_GET['course_id'] ?? 0;
            $lessons = $course->getCourseLessons($course_id);
            echo json_encode($lessons, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'enrollments':
            $course_id = $_GET['course_id'] ?? 0;
            $enrollments = $course->getCourseEnrollments($course_id);
            echo json_encode($enrollments, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'user_enrollments':
            $user_id = $_GET['user_id'] ?? 0;
            $enrollments = $course->getUserEnrollments($user_id);
            echo json_encode($enrollments, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'search':
            $keyword = $_GET['keyword'] ?? '';
            $results = $course->searchCourses($keyword);
            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'stats':
            $stats = $course->getCourseStats();
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleCoursePost($course, $performance, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $task_id = $performance->startTask(
                $input['admin_id'] ?? 1,
                'course_creation',
                'ایجاد دوره: ' . ($input['title_fa'] ?? 'بدون عنوان')
            );
            
            $course_id = $course->createCourse([
                'title_fa' => $input['title_fa'],
                'description' => $input['description'],
                'meta_title' => $input['meta_title'],
                'meta_description' => $input['meta_description'],
                'admin_id' => $input['admin_id'] ?? 1,
                'price' => $input['price'] ?? 0,
                'duration' => $input['duration'] ?? '',
                'level' => $input['level'] ?? 'beginner'
            ]);
            
            if ($course_id) {
                $performance->completeTask($task_id, 'completed');
                echo json_encode(['success' => true, 'course_id' => $course_id]);
            } else {
                $performance->completeTask($task_id, 'failed');
                echo json_encode(['success' => false, 'error' => 'Failed to create course']);
            }
            break;
            
        case 'add_lesson':
            $result = $course->addLesson($input['course_id'], [
                'title_fa' => $input['title_fa'],
                'content' => $input['content'],
                'order_num' => $input['order_num'] ?? 1
            ]);
            echo json_encode(['success' => $result]);
            break;
            
        case 'enroll':
            $result = $course->enrollUserInCourse(
                $input['user_id'],
                $input['course_id'],
                $input['payment_id'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'publish':
            $result = $course->publishCourse($input['course_id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'unpublish':
            $result = $course->unpublishCourse($input['course_id']);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleCoursePut($course, $performance, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update':
            $task_id = $performance->startTask(
                $input['admin_id'] ?? 1,
                'course_update',
                'بروزرسانی دوره: ' . ($input['title_fa'] ?? 'بدون عنوان')
            );
            
            $result = $course->updateCourse($input['course_id'], [
                'title_fa' => $input['title_fa'],
                'description' => $input['description'],
                'meta_title' => $input['meta_title'],
                'meta_description' => $input['meta_description'],
                'price' => $input['price'] ?? 0,
                'duration' => $input['duration'] ?? '',
                'level' => $input['level'] ?? 'beginner'
            ]);
            
            if ($result) {
                $performance->completeTask($task_id, 'completed');
                echo json_encode(['success' => true]);
            } else {
                $performance->completeTask($task_id, 'failed');
                echo json_encode(['success' => false]);
            }
            break;
            
        case 'update_lesson':
            $result = $course->updateLesson($input['lesson_id'], [
                'title_fa' => $input['title_fa'],
                'content' => $input['content'],
                'order_num' => $input['order_num'] ?? 1
            ]);
            echo json_encode(['success' => $result]);
            break;
            
        case 'cancel_enrollment':
            $result = $course->cancelEnrollment($input['enrollment_id']);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleCourseDelete($course, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'delete_course':
            if (isset($input['course_id'])) {
                $result = $course->deleteCourse($input['course_id']);
                echo json_encode(['success' => $result]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID required']);
            }
            break;
            
        case 'delete_lesson':
            if (isset($input['lesson_id'])) {
                $result = $course->deleteLesson($input['lesson_id']);
                echo json_encode(['success' => $result]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Lesson ID required']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>