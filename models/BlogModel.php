<?php
// models/BlogModel.php
class BlogModel
{
    private $conn;
    private $blogs_table = "blogs";
    private $blog_tags_table = "blog_tags";
    private $tags_table = "tags";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create new blog post
    public function createBlog($data)
    {
        $query = "INSERT INTO " . $this->blogs_table . " 
              (title_fa, content, meta_title, meta_description, slug, 
               admin_id, status, featured_image, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, 'draft', ?, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $slug = $this->generateSlug($data['title_fa']);

        if ($stmt->execute([
            $data['title_fa'],
            $data['content'],
            $data['meta_title'],
            $data['meta_description'],
            $slug,
            $data['admin_id'],
            $data['featured_image'] ?? null
        ])) {
            $blog_id = $this->conn->lastInsertId();

            if (!empty($data['tags'])) {
                $this->addBlogTags($blog_id, $data['tags']);
            }

            return $blog_id;
        }
        return false;
    }

    // Update existing blog
    public function updateBlog($blog_id, $data)
    {
        $query = "UPDATE " . $this->blogs_table . " 
              SET title_fa = ?, content = ?, meta_title = ?, 
                  meta_description = ?, slug = ?, featured_image = ?, updated_at = NOW() 
              WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $slug = $this->generateSlug($data['title_fa'], $blog_id); // Pass blog_id to avoid self-collision

        $result = $stmt->execute([
            $data['title_fa'],
            $data['content'],
            $data['meta_title'],
            $data['meta_description'],
            $slug,
            $data['featured_image'] ?? null,
            $blog_id
        ]);

        if ($result && !empty($data['tags'])) {
            $this->updateBlogTags($blog_id, $data['tags']);
        }

        return $result;
    }

    public function getAllBlogs($page = 1, $limit = 10, $status = null)
    {

        $offset = ($page - 1) * $limit;

        $where = "";
        $params = [];
        if ($status) {
            $where = "WHERE b.status = ?";
            $params[] = $status;
        }

        // --- خط زیر اصلاح شده است ---

        $query = "SELECT b.id, b.title_fa, b.meta_description, b.slug, b.status, b.published_at, b.featured_image 
          FROM " . $this->blogs_table . " b 
              $where
              ORDER BY b.created_at DESC 
              LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        // اتصال پارامترها
        $param_index = 1;
        foreach ($params as $param) {
            $stmt->bindValue($param_index++, $param);
        }
        $stmt->bindValue($param_index++, (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue($param_index++, (int) $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single blog by ID
    public function getBlogById($id)
    {
        $query = "SELECT b.*, GROUP_CONCAT(t.name SEPARATOR ', ') as tags
                  FROM " . $this->blogs_table . " b 
                  LEFT JOIN " . $this->blog_tags_table . " bt ON b.id = bt.blog_id
                  LEFT JOIN " . $this->tags_table . " t ON bt.tag_id = t.id
                  WHERE b.id = ?
                  GROUP BY b.id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get single blog by ID or Slug
    public function getBlogBySlug($slug)
    {
        $query = "SELECT b.*, GROUP_CONCAT(t.name SEPARATOR ', ') as tags
              FROM " . $this->blogs_table . " b
              LEFT JOIN " . $this->blog_tags_table . " bt ON b.id = bt.blog_id
              LEFT JOIN " . $this->tags_table . " t ON bt.tag_id = t.id
              WHERE b.slug = ?
              GROUP BY b.id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Publish blog
    public function publishBlog($blog_id)
    {
        $query = "UPDATE " . $this->blogs_table . " 
                  SET status = 'published', published_at = NOW() 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$blog_id]);
    }

    // Unpublish blog
    public function unpublishBlog($blog_id)
    {
        $query = "UPDATE " . $this->blogs_table . " 
                  SET status = 'draft' 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$blog_id]);
    }

    // Delete blog
    public function deleteBlog($blog_id)
    {
        $this->conn->beginTransaction();

        try {
            // Delete blog tags
            $stmt = $this->conn->prepare("DELETE FROM " . $this->blog_tags_table . " WHERE blog_id = ?");
            $stmt->execute([$blog_id]);

            // Delete blog
            $stmt = $this->conn->prepare("DELETE FROM " . $this->blogs_table . " WHERE id = ?");
            $stmt->execute([$blog_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Generate SEO-friendly slug
    private function generateSlug($title, $ignore_id = null)
    {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}\s]/u', '', $slug);
        $slug = preg_replace('/\s+/', '-', trim($slug));
        $slug = trim($slug, '-');

        $original_slug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $ignore_id)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists($slug, $ignore_id = null)
    {
        $query = "SELECT COUNT(*) FROM " . $this->blogs_table . " WHERE slug = ?";
        $params = [$slug];
        if ($ignore_id) {
            $query .= " AND id != ?";
            $params[] = $ignore_id;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    // Tag management
    public function addBlogTags($blog_id, $tags)
    {
        $tag_names = array_map('trim', explode(',', $tags));

        foreach ($tag_names as $tag_name) {
            if (empty($tag_name)) continue;

            $tag_id = $this->getOrCreateTag($tag_name);

            // Link tag to blog
            $query = "INSERT IGNORE INTO " . $this->blog_tags_table . " (blog_id, tag_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$blog_id, $tag_id]);
        }
    }

    public function updateBlogTags($blog_id, $tags)
    {
        // Remove existing tags
        $stmt = $this->conn->prepare("DELETE FROM " . $this->blog_tags_table . " WHERE blog_id = ?");
        $stmt->execute([$blog_id]);

        // Add new tags
        $this->addBlogTags($blog_id, $tags);
    }

    private function getOrCreateTag($tag_name)
    {
        // Check if tag exists
        $query = "SELECT id FROM " . $this->tags_table . " WHERE name = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$tag_name]);
        $tag_id = $stmt->fetchColumn();

        if (!$tag_id) {
            // Create new tag
            $query = "INSERT INTO " . $this->tags_table . " (name, created_at) VALUES (?, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$tag_name]);
            $tag_id = $this->conn->lastInsertId();
        }

        return $tag_id;
    }

    // Get all tags
    public function getAllTags()
    {
        $query = "SELECT t.*, COUNT(bt.blog_id) as blog_count 
                  FROM " . $this->tags_table . " t
                  LEFT JOIN " . $this->blog_tags_table . " bt ON t.id = bt.tag_id
                  GROUP BY t.id
                  ORDER BY t.name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search blogs
    public function searchBlogs($keyword, $limit = 10)
    {
        $query = "SELECT b.*, GROUP_CONCAT(t.name SEPARATOR ', ') as tags
                  FROM " . $this->blogs_table . " b 
                  LEFT JOIN " . $this->blog_tags_table . " bt ON b.id = bt.blog_id
                  LEFT JOIN " . $this->tags_table . " t ON bt.tag_id = t.id
                  WHERE b.title_fa LIKE ? OR b.content LIKE ? OR b.meta_description LIKE ?
                  GROUP BY b.id
                  ORDER BY b.created_at DESC 
                  LIMIT ?";

        $searchTerm = "%$keyword%";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get blog statistics
    public function getBlogStats()
    {
        $stats = [];

        // Total blogs
        $query = "SELECT COUNT(*) FROM " . $this->blogs_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_blogs'] = $stmt->fetchColumn();

        // Published blogs
        $query = "SELECT COUNT(*) FROM " . $this->blogs_table . " WHERE status = 'published'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['published_blogs'] = $stmt->fetchColumn();

        // Draft blogs
        $query = "SELECT COUNT(*) FROM " . $this->blogs_table . " WHERE status = 'draft'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['draft_blogs'] = $stmt->fetchColumn();

        // Total tags
        $query = "SELECT COUNT(*) FROM " . $this->tags_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_tags'] = $stmt->fetchColumn();

        return $stats;
    }

    // Process content with tags and formatting
    public function processContent($content)
    {
        // Convert simple markdown-like syntax
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);

        // Process hashtags
        $content = preg_replace('/#([^\s#]+)/', '<span class="hashtag">#$1</span>', $content);

        // Process line breaks
        $content = nl2br($content);

        return $content;
    }
}
