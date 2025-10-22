<?php
/**
 * API Endpoint: Get Published Notices
 * Returns notices with publisher information and sanitized content
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

// HTML Sanitization function
function sanitizeHTML($html) {
    // Remove script tags and event handlers
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/\bon\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $html);

    // Allow only safe HTML tags
    $allowed_tags = '<p><br><strong><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><span><div>';
    $html = strip_tags($html, $allowed_tags);

    // Remove javascript: and data: protocols from links
    $html = preg_replace('/href\s*=\s*["\']?\s*javascript:/i', 'href="#"', $html);
    $html = preg_replace('/href\s*=\s*["\']?\s*data:/i', 'href="#"', $html);

    return $html;
}

// Create excerpt from HTML content
function createExcerpt($html, $length = 150) {
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (strlen($text) <= $length) {
        return $text;
    }

    $text = substr($text, 0, $length);
    $lastSpace = strrpos($text, ' ');

    if ($lastSpace !== false) {
        $text = substr($text, 0, $lastSpace);
    }

    return $text . '...';
}

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3;
    $limit = max(1, min($limit, 50)); // Between 1 and 50

    // Check if publisher columns exist
    $check_columns = $conn->query("SHOW COLUMNS FROM notices LIKE 'publisher_type'");
    $has_publisher_cols = ($check_columns && $check_columns->num_rows > 0);

    if ($has_publisher_cols) {
        // New query with publisher information
        $sql = "SELECT
                    n.id,
                    n.title,
                    n.content,
                    n.publish_date,
                    n.created_at,
                    n.publisher_type,
                    n.publisher_id,
                    n.publisher_name
                FROM notices n
                WHERE n.status = 'published'
                ORDER BY n.publish_date DESC, n.created_at DESC
                LIMIT ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Fallback for old schema - use admin table (not users)
        $sql = "SELECT
                    n.id,
                    n.title,
                    n.content,
                    n.publish_date,
                    n.created_at,
                    a.email as creator_email
                FROM notices n
                LEFT JOIN admin a ON n.created_by = a.id
                WHERE n.status = 'published'
                ORDER BY n.publish_date DESC, n.created_at DESC
                LIMIT ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $notices = [];

    while ($row = $result->fetch_assoc()) {
        // Sanitize content
        $sanitized_content = sanitizeHTML($row['content']);

        // Determine publisher information
        if ($has_publisher_cols) {
            $publisher_type = $row['publisher_type'];
            $publisher_name = htmlspecialchars($row['publisher_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
        } else {
            $publisher_type = 'admin';
            // For old schema, use email or default to Administrator
            $publisher_name = isset($row['creator_email']) ?
                htmlspecialchars('Administrator', ENT_QUOTES, 'UTF-8') :
                'Administrator';
        }

        $notices[] = [
            'id' => intval($row['id']),
            'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
            'content' => $sanitized_content,
            'excerpt' => createExcerpt($sanitized_content, 150),
            'publish_date' => $row['publish_date'],
            'created_at' => $row['created_at'],
            'published_at' => date('c', strtotime($row['publish_date'] . ' ' . $row['created_at'])),
            'publisher_type' => $publisher_type,
            'publisher_name' => $publisher_name,
            'category' => 'general' // Can be enhanced later
        ];
    }

    echo json_encode([
        'success' => true,
        'notices' => $notices,
        'count' => count($notices)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notices',
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

$conn->close();
?>
