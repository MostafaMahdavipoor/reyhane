<?php

class PerformanceModel
{
    private $conn;
    private $metrics_table = "performance_metrics";
    private $summary_table = "daily_performance_summary";
    private $benchmarks_table = "system_benchmarks";
    private $tasks_table = "admin_tasks";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Record a new performance metric
    public function recordMetric($metric_type, $metric_value, $admin_id, $related_entity_id = null)
    {
        $query = "INSERT INTO " . $this->metrics_table . "
(metric_type, metric_value, admin_id, related_entity_id)
VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$metric_type, $metric_value, $admin_id, $related_entity_id]);
    }

    // Start tracking an admin task
    public function startTask($admin_id, $task_type, $task_description)
    {
        $query = "INSERT INTO " . $this->tasks_table . "
(admin_id, task_type, task_description, start_time)
VALUES (?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$admin_id, $task_type, $task_description])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Complete a task and record duration
    public function completeTask($task_id, $status = 'completed', $notes = null)
    {
        $query = "UPDATE " . $this->tasks_table . "
SET end_time = NOW(), status = ?, notes = ?
WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $notes, $task_id]);
    }

    // Get daily performance summary
    public function getDailySummary($admin_id, $date = null)
    {
        if (!$date) $date = date('Y-m-d');

        $query = "SELECT * FROM " . $this->summary_table . "
WHERE admin_id = ? AND date = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update daily performance summary
    public function updateDailySummary($admin_id, $date = null)
    {
        if (!$date) $date = date('Y-m-d');

        // Calculate daily statistics
        $stats = $this->calculateDailyStats($admin_id, $date);

        $query = "INSERT INTO " . $this->summary_table . "
(date, admin_id, total_tasks_completed, avg_task_completion_time,
payments_processed, avg_payment_processing_time,
enrollments_managed, avg_enrollment_time,
blog_posts_created, avg_blog_creation_time,
courses_updated, avg_course_update_time,
user_queries_responded, avg_response_time, efficiency_score)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
total_tasks_completed = VALUES(total_tasks_completed),
avg_task_completion_time = VALUES(avg_task_completion_time),
payments_processed = VALUES(payments_processed),
avg_payment_processing_time = VALUES(avg_payment_processing_time),
enrollments_managed = VALUES(enrollments_managed),
avg_enrollment_time = VALUES(avg_enrollment_time),
blog_posts_created = VALUES(blog_posts_created),
avg_blog_creation_time = VALUES(avg_blog_creation_time),
courses_updated = VALUES(courses_updated),
avg_course_update_time = VALUES(avg_course_update_time),
user_queries_responded = VALUES(user_queries_responded),
avg_response_time = VALUES(avg_response_time),
efficiency_score = VALUES(efficiency_score)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $date,
            $admin_id,
            $stats['total_tasks'],
            $stats['avg_completion_time'],
            $stats['payments_processed'],
            $stats['avg_payment_time'],
            $stats['enrollments_managed'],
            $stats['avg_enrollment_time'],
            $stats['blog_posts_created'],
            $stats['avg_blog_time'],
            $stats['courses_updated'],
            $stats['avg_course_time'],
            $stats['user_queries'],
            $stats['avg_response_time'],
            $stats['efficiency_score']
        ]);
    }

    private function calculateDailyStats($admin_id, $date)
    {
        $stats = [];

        // Total completed tasks
        $query = "SELECT COUNT(*) as count, AVG(duration_seconds) as avg_duration
FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_tasks'] = $result['count'] ?: 0;
        $stats['avg_completion_time'] = $result['avg_duration'] ?: 0;

        // Payment processing stats
        $query = "SELECT COUNT(*) as count, AVG(duration_seconds) as avg_duration
FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND task_type = 'payment_review' AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['payments_processed'] = $result['count'] ?: 0;
        $stats['avg_payment_time'] = $result['avg_duration'] ?: 0;

        // Enrollment management stats
        $query = "SELECT COUNT(*) as count, AVG(duration_seconds) as avg_duration
FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND task_type = 'enrollment_confirmation' AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['enrollments_managed'] = $result['count'] ?: 0;
        $stats['avg_enrollment_time'] = $result['avg_duration'] ?: 0;

        // Blog creation stats
        $query = "SELECT COUNT(*) as count, AVG(duration_seconds) as avg_duration
FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND task_type = 'blog_creation' AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['blog_posts_created'] = $result['count'] ?: 0;
        $stats['avg_blog_time'] = $result['avg_duration'] ?: 0;

        // Course update stats
        $query = "SELECT COUNT(*) as count, AVG(duration_seconds) as avg_duration
FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND task_type = 'course_update' AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['courses_updated'] = $result['count'] ?: 0;
        $stats['avg_course_time'] = $result['avg_duration'] ?: 0;

        // User support stats
        $query = "SELECT COUNT(*) as count, AVG(duration_seconds) as avg_duration
FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND task_type = 'user_support' AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['user_queries'] = $result['count'] ?: 0;
        $stats['avg_response_time'] = $result['avg_duration'] ?: 0;

        // Calculate efficiency score (0-100)
        $efficiency_score = $this->calculateEfficiencyScore($stats);
        $stats['efficiency_score'] = $efficiency_score;

        return $stats;
    }

    private function calculateEfficiencyScore($stats)
    {
        $score = 100;

        // Deduct points for slow performance
        if ($stats['avg_payment_time'] > 300) $score -= 10; // More than 5 minutes
        if ($stats['avg_enrollment_time'] > 180) $score -= 10; // More than 3 minutes
        if ($stats['avg_blog_time'] > 1800) $score -= 10; // More than 30 minutes
        if ($stats['avg_course_time'] > 600) $score -= 10; // More than 10 minutes
        if ($stats['avg_response_time'] > 3600) $score -= 10; // More than 1 hour

        // Bonus points for high productivity
        if ($stats['total_tasks'] >= 20) $score += 10;
        if ($stats['payments_processed'] >= 10) $score += 5;
        if ($stats['blog_posts_created'] >= 2) $score += 5;

        return max(0, min(100, $score));
    }

    // Get system benchmarks
    public function getSystemBenchmarks()
    {
        $query = "SELECT * FROM current_benchmarks_status ORDER BY achievement_percentage DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update system benchmark current value
    public function updateBenchmark($benchmark_name, $current_value)
    {
        $query = "UPDATE " . $this->benchmarks_table . "
SET current_value = ?
WHERE benchmark_name = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$current_value, $benchmark_name]);
    }

    // Get performance trends (last 30 days)
    public function getPerformanceTrends($admin_id)
    {
        $query = "SELECT date, efficiency_score, total_tasks_completed,
avg_task_completion_time, payments_processed,
enrollments_managed, blog_posts_created, courses_updated
FROM " . $this->summary_table . "
WHERE admin_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get real-time metrics
    public function getRealTimeMetrics($admin_id)
    {
        $today = date('Y-m-d');

        $metrics = [];

        // Today's active tasks
        $query = "SELECT COUNT(*) as count FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND status = 'in_progress'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $today]);
        $metrics['active_tasks'] = $stmt->fetchColumn();

        // Today's completed tasks
        $query = "SELECT COUNT(*) as count FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $today]);
        $metrics['completed_tasks'] = $stmt->fetchColumn();

        // Pending payments
        $query = "SELECT COUNT(*) as count FROM payments WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $metrics['pending_payments'] = $stmt->fetchColumn();

        // Average response time today
        $query = "SELECT AVG(duration_seconds) as avg_time FROM " . $this->tasks_table . "
WHERE admin_id = ? AND DATE(start_time) = ? AND task_type = 'user_support' AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$admin_id, $today]);
        $metrics['avg_response_time'] = $stmt->fetchColumn() ?: 0;

        return $metrics;
    }
}
