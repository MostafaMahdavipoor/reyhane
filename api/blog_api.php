<?php

// api/blog_api.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/BlogModel.php';
require_once '../models/PerformanceModel.php';

$database = new Database();
$db = $database->getConnection();
$blog = new BlogModel($db);
$performance = new PerformanceModel($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleBlogGet($blog);
        break;
    case 'POST':
        handleBlogPost($blog, $performance, $input);
        break;
    case 'PUT':
        handleBlogPut($blog, $performance, $input);
        break;
    case 'DELETE':
        handleBlogDelete($blog, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleBlogGet($blog) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $status = $_GET['status'] ?? null;
            $blogs = $blog->getAllBlogs($page, $limit, $status);
            echo json_encode($blogs, JSON_UNESCAPED_UNICODE);
            break;

        case 'get':
            if (isset($_GET['slug'])) {
                $blogData = $blog->getBlogBySlug($_GET['slug']);
            } elseif (isset($_GET['id'])) {
                $blogData = $blog->getBlogById($_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Blog ID or Slug is required']);
                return;
            }
            echo json_encode($blogData, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'tags':
            $tags = $blog->getAllTags();
            echo json_encode($tags, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'search':
            $keyword = $_GET['keyword'] ?? '';
            $results = $blog->searchBlogs($keyword);
            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'stats':
            $stats = $blog->getBlogStats();
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleBlogPost($blog, $performance, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $task_id = $performance->startTask(
                $input['admin_id'] ?? 1,
                'blog_creation',
                'ایجاد مقاله: ' . ($input['title_fa'] ?? 'بدون عنوان')
            );

            $blog_id = $blog->createBlog([
                'title_fa' => $input['title_fa'],
                'content' => $blog->processContent($input['content']),
                'meta_title' => $input['meta_title'],
                'meta_description' => $input['meta_description'],
                'tags' => $input['tags'] ?? '',
                'admin_id' => $input['admin_id'] ?? 1,
                'featured_image' => $input['featured_image'] ?? null 
            ]);
            if ($blog_id) {
                $performance->completeTask($task_id, 'completed');
                echo json_encode(['success' => true, 'blog_id' => $blog_id]);
            } else {
                $performance->completeTask($task_id, 'failed');
                echo json_encode(['success' => false, 'error' => 'Failed to create blog']);
            }
            break;
            
        case 'publish':
            $result = $blog->publishBlog($input['blog_id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'unpublish':
            $result = $blog->unpublishBlog($input['blog_id']);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleBlogPut($blog, $performance, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update':
            $task_id = $performance->startTask(
                $input['admin_id'] ?? 1,
                'blog_update',
                'بروزرسانی مقاله: ' . ($input['title_fa'] ?? 'بدون عنوان')
            );

            $result = $blog->updateBlog($input['blog_id'], [
                'title_fa' => $input['title_fa'],
                'content' => $blog->processContent($input['content']),
                'meta_title' => $input['meta_title'],
                'meta_description' => $input['meta_description'],
                'tags' => $input['tags'] ?? '',
                'featured_image' => $input['featured_image'] ?? null // این خط را اضافه کنید
            ]);
            
            if ($result) {
                $performance->completeTask($task_id, 'completed');
                echo json_encode(['success' => true]);
            } else {
                $performance->completeTask($task_id, 'failed');
                echo json_encode(['success' => false]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleBlogDelete($blog, $input) {
    if (isset($input['blog_id'])) {
        $result = $blog->deleteBlog($input['blog_id']);
        echo json_encode(['success' => $result]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Blog ID required']);
    }
}
