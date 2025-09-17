<?php
// models/CourseModel.php
class CourseModel {
    private $conn;
    private $courses_table = "courses";
    private $course_enrollments_table = "course_enrollments";
    private $course_lessons_table = "course_lessons";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new course
    public function createCourse($data) {
        $query = "INSERT INTO " . $this->courses_table . " 
                  (title_fa, description, meta_title, meta_description, slug, 
                   admin_id, price, duration, level, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        $slug = $this->generateSlug($data['title_fa']);
        
        if ($stmt->execute([
            $data['title_fa'],
            $data['description'],
            $data['meta_title'],
            $data['meta_description'],
            $slug,
            $data['admin_id'],
            $data['price'],
            $data['duration'],
            $data['level']
        ])) {
            $course_id = $this->conn->lastInsertId();
            return $course_id;
        }
        return false;
    }

    // Update existing course
    public function updateCourse($course_id, $data) {
        $query = "UPDATE " . $this->courses_table . " 
                  SET title_fa = ?, description = ?, meta_title = ?, 
                      meta_description = ?, slug = ?, price = ?, 
                      duration = ?, level = ?, updated_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $slug = $this->generateSlug($data['title_fa']);
        
        $result = $stmt->execute([
            $data['title_fa'],
            $data['description'],
            $data['meta_title'],
            $data['meta_description'],
            $slug,
            $data['price'],
            $data['duration'],
            $data['level'],
            $course_id
        ]);
        
        return $result;
    }

    // Get all courses with pagination
    public function getAllCourses($page = 1, $limit = 10, $status = null) {
        $offset = ($page - 1) * $limit;
        
        $where = "";
        $params = [];
        if ($status) {
            $where = "WHERE status = ?";
            $params[] = $status;
        }
        
        $query = "SELECT c.*, COUNT(ce.id) as enrollment_count
                  FROM " . $this->courses_table . " c 
                  LEFT JOIN " . $this->course_enrollments_table . " ce ON c.id = ce.course_id
                  $where
                  GROUP BY c.id
                  ORDER BY c.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single course by ID
    public function getCourseById($id) {
        $query = "SELECT c.*, COUNT(ce.id) as enrollment_count
                  FROM " . $this->courses_table . " c 
                  LEFT JOIN " . $this->course_enrollments_table . " ce ON c.id = ce.course_id
                  WHERE c.id = ?
                  GROUP BY c.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Publish course
    public function publishCourse($course_id) {
        $query = "UPDATE " . $this->courses_table . " 
                  SET status = 'published', published_at = NOW() 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$course_id]);
    }

    // Unpublish course
    public function unpublishCourse($course_id) {
        $query = "UPDATE " . $this->courses_table . " 
                  SET status = 'draft' 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$course_id]);
    }

    // Delete course
    public function deleteCourse($course_id) {
        $this->conn->beginTransaction();
        
        try {
            // Delete course lessons
            $stmt = $this->conn->prepare("DELETE FROM " . $this->course_lessons_table . " WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            // Delete course enrollments
            $stmt = $this->conn->prepare("DELETE FROM " . $this->course_enrollments_table . " WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            // Delete course
            $stmt = $this->conn->prepare("DELETE FROM " . $this->courses_table . " WHERE id = ?");
            $stmt->execute([$course_id]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Generate SEO-friendly slug
    private function generateSlug($title) {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}\s]/u', '', $slug);
        $slug = preg_replace('/\s+/', '-', trim($slug));
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $original_slug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists($slug) {
        $query = "SELECT COUNT(*) FROM " . $this->courses_table . " WHERE slug = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetchColumn() > 0;
    }

    // Add lesson to course
    public function addLesson($course_id, $data) {
        $query = "INSERT INTO " . $this->course_lessons_table . " 
                  (course_id, title_fa, content, order_num, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $course_id,
            $data['title_fa'],
            $data['content'],
            $data['order_num']
        ]);
    }

    // Update lesson
    public function updateLesson($lesson_id, $data) {
        $query = "UPDATE " . $this->course_lessons_table . " 
                  SET title_fa = ?, content = ?, order_num = ? 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title_fa'],
            $data['content'],
            $data['order_num'],
            $lesson_id
        ]);
    }

    // Delete lesson
    public function deleteLesson($lesson_id) {
        $query = "DELETE FROM " . $this->course_lessons_table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$lesson_id]);
    }

    // Get lessons for a course
    public function getCourseLessons($course_id) {
        $query = "SELECT * FROM " . $this->course_lessons_table . " 
                  WHERE course_id = ? 
                  ORDER BY order_num ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Enroll user in course
    public function enrollUserInCourse($user_id, $course_id, $payment_id = null) {
        $query = "INSERT INTO " . $this->course_enrollments_table . " 
                  (user_id, course_id, payment_id, enrollment_date, status) 
                  VALUES (?, ?, ?, NOW(), 'active')";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $user_id,
            $course_id,
            $payment_id
        ]);
    }

    // Check if user is enrolled in course
    public function isUserEnrolled($user_id, $course_id) {
        $query = "SELECT COUNT(*) FROM " . $this->course_enrollments_table . " 
                  WHERE user_id = ? AND course_id = ? AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $course_id]);
        return $stmt->fetchColumn() > 0;
    }

    // Get all enrollments for a user
    public function getUserEnrollments($user_id) {
        $query = "SELECT ce.*, c.title_fa, c.description, c.level 
                  FROM " . $this->course_enrollments_table . " ce 
                  JOIN " . $this->courses_table . " c ON ce.course_id = c.id 
                  WHERE ce.user_id = ? AND ce.status = 'active' 
                  ORDER BY ce.enrollment_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all enrollments for a course
    public function getCourseEnrollments($course_id) {
        $query = "SELECT ce.*, u.username, u.email 
                  FROM " . $this->course_enrollments_table . " ce 
                  JOIN users u ON ce.user_id = u.id 
                  WHERE ce.course_id = ? 
                  ORDER BY ce.enrollment_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cancel enrollment
    public function cancelEnrollment($enrollment_id) {
        $query = "UPDATE " . $this->course_enrollments_table . " 
                  SET status = 'cancelled' 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$enrollment_id]);
    }

    // Search courses
    public function searchCourses($keyword, $limit = 10) {
        $query = "SELECT c.*, COUNT(ce.id) as enrollment_count
                  FROM " . $this->courses_table . " c 
                  LEFT JOIN " . $this->course_enrollments_table . " ce ON c.id = ce.course_id
                  WHERE c.title_fa LIKE ? OR c.description LIKE ? OR c.meta_description LIKE ?
                  GROUP BY c.id
                  ORDER BY c.created_at DESC 
                  LIMIT ?";
        
        $searchTerm = "%$keyword%";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get course statistics
    public function getCourseStats() {
        $stats = [];
        
        // Total courses
        $query = "SELECT COUNT(*) FROM " . $this->courses_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_courses'] = $stmt->fetchColumn();
        
        // Published courses
        $query = "SELECT COUNT(*) FROM " . $this->courses_table . " WHERE status = 'published'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['published_courses'] = $stmt->fetchColumn();
        
        // Draft courses
        $query = "SELECT COUNT(*) FROM " . $this->courses_table . " WHERE status = 'draft'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['draft_courses'] = $stmt->fetchColumn();
        
        // Total enrollments
        $query = "SELECT COUNT(*) FROM " . $this->course_enrollments_table . " WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_enrollments'] = $stmt->fetchColumn();
        
        return $stats;
    }
}

// Database schema for course system
/*
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title_fa VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    slug VARCHAR(255) UNIQUE,
    admin_id INT NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    duration VARCHAR(100),
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS course_lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title_fa VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    order_num INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    payment_id INT,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id)
);
*/
?>