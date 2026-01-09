<?php
// FINAL FIX: Add image column to custom_requests table and populate it
echo "<h1>🔧 FINAL IMAGE DISPLAY FIX</h1>";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Step 1: Add image_url column to custom_requests table
    echo "<h2>📋 Step 1: Adding image_url column to custom_requests table</h2>";
    
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN image_url VARCHAR(500) DEFAULT '' AFTER description");
        echo "<p style='color: green;'>✅ Added image_url column to custom_requests table</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>📋 image_url column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Populate image_url column with first image from custom_request_images
    echo "<h2>🖼️ Step 2: Populating image URLs</h2>";
    
    $requests = $pdo->query("SELECT id, order_id, title FROM custom_requests")->fetchAll();
    $updatedCount = 0;
    
    foreach ($requests as $request) {
        // Get first image for this request
        $imageStmt = $pdo->prepare("
            SELECT image_url, filename, original_filename 
            FROM custom_request_images 
            WHERE request_id = ? 
            ORDER BY uploaded_at ASC 
            LIMIT 1
        ");
        $imageStmt->execute([$request['id']]);
        $image = $imageStmt->fetch();
        
        if ($image) {
            // Update custom_requests with image URL
            $updateStmt = $pdo->prepare("UPDATE custom_requests SET image_url = ? WHERE id = ?");
            $updateStmt->execute([$image['image_url'], $request['id']]);
            
            echo "<p style='color: green;'>✅ Updated Request {$request['id']}: {$request['title']} with image: {$image['filename']}</p>";
            $updatedCount++;
        } else {
            // No image found, set a placeholder
            $placeholderUrl = 'uploads/custom-requests/placeholder.svg';
            $updateStmt = $pdo->prepare("UPDATE custom_requests SET image_url = ? WHERE id = ?");
            $updateStmt->execute([$placeholderUrl, $request['id']]);
            
            echo "<p style='color: orange;'>⚠️ Request {$request['id']}: {$request['title']} - No image found, set placeholder</p>";
        }
    }
    
    echo "<p><strong>Updated $updatedCount requests with actual images</strong></p>";
    
    // Step 3: Create placeholder image file
    echo "<h2>📁 Step 3: Creating placeholder image</h2>";
    
    $uploadDir = __DIR__ . "/backend/uploads/custom-requests/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p style='color: green;'>✅ Created upload directory</p>";
    }
    
    $placeholderPath = $uploadDir . "placeholder.svg";
    if (!file_exists($placeholderPath)) {
        $placeholderSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <rect width="100" height="100" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
    <circle cx="35" cy="35" r="8" fill="#6c757d"/>
    <path d="M20 70 L35 55 L50 70 L65 55 L80 70 L80 80 L20 80 Z" fill="#6c757d"/>
    <text x="50" y="95" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="8">No Image</text>
</svg>';
        
        file_put_contents($placeholderPath, $placeholderSvg);
        echo "<p style='color: green;'>✅ Created placeholder image</p>";
    }
    
    // Step 4: Update the API to use the new image_url column
    echo "<h2>🔧 Step 4: Updating API</h2>";
    
    $apiContent = '<?php
// FIXED Custom Requests API - Uses image_url column directly
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER["REQUEST_METHOD"];
    
    if ($method === "GET") {
        $status = $_GET["status"] ?? "all";
        $limit = min((int)($_GET["limit"] ?? 50), 100);
        $offset = max((int)($_GET["offset"] ?? 0), 0);
        
        // Build query
        $whereClause = "";
        $params = [];
        
        if ($status !== "all") {
            if ($status === "pending") {
                $whereClause = "WHERE status IN (\'submitted\', \'pending\')";
            } else {
                $whereClause = "WHERE status = ?";
                $params[] = $status;
            }
        }
        
        $query = "SELECT * FROM custom_requests $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each request
        $baseUrl = "http://localhost/my_little_thingz/backend/";
        
        foreach ($requests as &$request) {
            // Customer info
            $nameParts = explode(" ", $request["customer_name"], 2);
            $request["first_name"] = $nameParts[0] ?? "";
            $request["last_name"] = $nameParts[1] ?? "";
            $request["email"] = $request["customer_email"];
            $request["phone"] = $request["customer_phone"] ?? "";
            
            // Request details
            $request["category_name"] = $request["occasion"] ?: "General";
            $request["description"] = $request["description"] ?: "";
            $request["requirements"] = $request["requirements"] ?: "";
            
            // FIXED: Use image_url column directly
            $request["images"] = [];
            
            if (!empty($request["image_url"])) {
                $imageUrl = $request["image_url"];
                
                // Make URL absolute if relative
                if (!preg_match(\'/^https?:\/\/\', $imageUrl)) {
                    $imageUrl = $baseUrl . ltrim($imageUrl, \'/\');
                }
                
                $request["images"][] = [
                    "url" => $imageUrl,
                    "filename" => basename($request["image_url"]),
                    "original_name" => basename($request["image_url"]),
                    "uploaded_at" => $request["created_at"]
                ];
            } else {
                // Default placeholder
                $request["images"][] = [
                    "url" => $baseUrl . "uploads/custom-requests/placeholder.svg",
                    "filename" => "placeholder.svg",
                    "original_name" => "No image uploaded",
                    "uploaded_at" => null,
                    "is_placeholder" => true
                ];
            }
            
            // Calculate deadline
            if ($request["deadline"]) {
                $deadlineDate = new DateTime($request["deadline"]);
                $today = new DateTime();
                $interval = $today->diff($deadlineDate);
                $request["days_until_deadline"] = $interval->invert ? -$interval->days : $interval->days;
            } else {
                $request["days_until_deadline"] = 30;
            }
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM custom_requests $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2));
        $totalCount = $countStmt->fetchColumn();
        
        // Get statistics
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN (\'submitted\', \'pending\') THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = \'in_progress\' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = \'cancelled\' THEN 1 ELSE 0 END) as cancelled_requests
            FROM custom_requests
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "requests" => $requests,
            "total_count" => (int)$totalCount,
            "showing_count" => count($requests),
            "stats" => $stats,
            "message" => "Custom requests loaded successfully with FIXED images",
            "api_version" => "fixed-v1.0",
            "timestamp" => date("Y-m-d H:i:s")
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents(__DIR__ . '/backend/api/admin/custom-requests-fixed.php', $apiContent);
    echo "<p style='color: green;'>✅ Created fixed API: backend/api/admin/custom-requests-fixed.php</p>";
    
    // Step 5: Update the dashboard to use fixed API
    echo "<h2>🎯 Step 5: Creating fixed dashboard</h2>";
    
    $dashboardJs = "
class CustomRequestsDashboard {
    constructor() {
        this.apiBaseUrl = '../../backend/api/admin/custom-requests-fixed.php';
        this.adminEmail = 'admin@mylittlethingz.com';
        this.currentRequests = [];
        this.filteredRequests = [];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadRequests();
        
        setInterval(() => {
            this.loadRequests(false);
        }, 30000);
    }
    
    setupEventListeners() {
        window.refreshRequests = () => this.loadRequests();
    }
    
    async loadRequests(showLoading = true) {
        if (showLoading) {
            this.showLoadingState();
        }
        
        try {
            const response = await fetch(this.apiBaseUrl);
            const data = await response.json();
            
            console.log('✅ API Response:', data);
            
            if (data.status === 'success') {
                this.currentRequests = data.requests;
                this.renderRequests();
            } else {
                throw new Error(data.message || 'Failed to load requests');
            }
            
        } catch (error) {
            console.error('Error loading requests:', error);
            this.showError('Failed to load custom requests: ' + error.message);
        }
    }
    
    renderRequests() {
        const tbody = document.getElementById('requestsTableBody');
        
        if (this.currentRequests.length === 0) {
            tbody.innerHTML = '<tr><td colspan=\"8\" class=\"text-center py-4\">No custom requests found</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.currentRequests.map(request => this.renderRequestRow(request)).join('');
    }
    
    renderRequestRow(request) {
        const customerInitials = this.getCustomerInitials(request.customer_name);
        
        return \`
            <tr>
                <td><strong>\${request.order_id || request.id}</strong></td>
                <td>
                    <div class=\"customer-info\">
                        <div class=\"customer-avatar\">\${customerInitials}</div>
                        <div>
                            <div class=\"fw-bold\">\${request.customer_name}</div>
                            <small class=\"text-muted\">\${request.customer_email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class=\"fw-bold\">\${request.title}</div>
                    \${request.occasion ? \`<small class=\"text-muted\">For: \${request.occasion}</small>\` : ''}
                </td>
                <td>
                    \${this.renderImages(request)}
                </td>
                <td>\${request.deadline || 'Not set'}</td>
                <td><span class=\"badge bg-primary\">\${request.status}</span></td>
                <td><span class=\"badge bg-secondary\">\${request.priority || 'medium'}</span></td>
                <td>
                    <button class=\"btn btn-sm btn-primary\">View</button>
                </td>
            </tr>
        \`;
    }
    
    renderImages(request) {
        if (!request.images || request.images.length === 0) {
            return '<div class=\"text-muted small\"><i class=\"fas fa-image\"></i> No images</div>';
        }
        
        const firstImage = request.images[0];
        const imageUrl = firstImage.url || '';
        
        if (!imageUrl) {
            return '<div class=\"text-muted small\"><i class=\"fas fa-exclamation-triangle\"></i> Image error</div>';
        }
        
        return \`
            <div class=\"image-preview\">
                <img src=\"\${imageUrl}\" 
                     alt=\"\${firstImage.original_name || 'Reference image'}\" 
                     class=\"img-thumbnail request-image-thumb\"
                     style=\"width: 40px; height: 40px; object-fit: cover; cursor: pointer;\"
                     onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjZjhmOWZhIiBzdHJva2U9IiNkZWUyZTYiIHN0cm9rZS13aWR0aD0iMiIvPgo8Y2lyY2xlIGN4PSIxNSIgY3k9IjE1IiByPSIzIiBmaWxsPSIjNmM3NTdkIi8+CjxwYXRoIGQ9Ik04IDI4IEwxNSAyMSBMMjIgMjggTDI4IDIxIEwzMiAyOCBMMzIgMzIgTDggMzIgWiIgZmlsbD0iIzZjNzU3ZCIvPgo8L3N2Zz4K'; this.onerror=null;\">
            </div>
        \`;
    }
    
    showLoadingState() {
        document.getElementById('requestsTableBody').innerHTML = '<tr><td colspan=\"8\" class=\"text-center py-4\">Loading...</td></tr>';
    }
    
    showError(message) {
        document.getElementById('requestsTableBody').innerHTML = \`<tr><td colspan=\"8\" class=\"text-center py-4 text-danger\">\${message}</td></tr>\`;
    }
    
    getCustomerInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.customRequestsDashboard = new CustomRequestsDashboard();
});
";
    
    file_put_contents(__DIR__ . '/frontend/admin/js/custom-requests-fixed.js', $dashboardJs);
    echo "<p style='color: green;'>✅ Created fixed dashboard JS: frontend/admin/js/custom-requests-fixed.js</p>";
    
    // Create fixed dashboard HTML
    $dashboardHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIXED Custom Requests Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .customer-info { display: flex; align-items: center; gap: 10px; }
        .customer-avatar { width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(45deg, #007bff, #6f42c1); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9em; }
        .image-preview { position: relative; display: inline-block; }
        .request-image-thumb { border-radius: 6px; transition: all 0.3s ease; border: 2px solid #e9ecef; }
        .request-image-thumb:hover { transform: scale(1.1); border-color: #007bff; box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-palette"></i> FIXED Custom Requests Dashboard</h2>
                        <p class="text-muted mb-0">Images now working with direct image_url column</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Title</th>
                                        <th>Images</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="requestsTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center py-4">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/custom-requests-fixed.js"></script>
</body>
</html>';
    
    file_put_contents(__DIR__ . '/frontend/admin/custom-requests-fixed.html', $dashboardHtml);
    echo "<p style='color: green;'>✅ Created fixed dashboard HTML: frontend/admin/custom-requests-fixed.html</p>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>🎉 FINAL FIX COMPLETE!</h2>";
    echo "<p style='color: #155724;'><strong>What was done:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>✅ Added image_url column to custom_requests table</li>";
    echo "<li>✅ Populated image URLs from custom_request_images table</li>";
    echo "<li>✅ Created placeholder image for requests without images</li>";
    echo "<li>✅ Created completely new fixed API</li>";
    echo "<li>✅ Created completely new fixed dashboard</li>";
    echo "</ul>";
    echo "<p style='color: #155724;'><strong>NOW TEST THE FIXED DASHBOARD:</strong></p>";
    echo "<p><a href='frontend/admin/custom-requests-fixed.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🎯 OPEN FIXED DASHBOARD</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
}

a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>