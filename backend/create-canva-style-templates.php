<?php
/**
 * Create Canva-style Birthday Templates with Small Circular Image Placeholders
 * These templates match the visual style shown in the user's image
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Ensure design_templates table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS design_templates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        category ENUM('Birthday', 'Wedding', 'Invitation', 'Posters', 'Photo Frames') NOT NULL,
        canvas_width INT UNSIGNED NOT NULL DEFAULT 800,
        canvas_height INT UNSIGNED NOT NULL DEFAULT 600,
        template_data JSON NOT NULL,
        preview_image_url VARCHAR(500),
        thumbnail_url VARCHAR(500),
        is_public BOOLEAN DEFAULT TRUE,
        is_featured BOOLEAN DEFAULT FALSE,
        created_by INT UNSIGNED DEFAULT NULL,
        usage_count INT UNSIGNED DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_public (is_public),
        INDEX idx_featured (is_featured)
    )");
    
    // Canva-style Birthday Templates with circular image placeholders
    $templates = [
        [
            'name' => "Emma's Birthday Invitation",
            'description' => 'Light purple birthday invitation with circular photo placeholder, balloons, and cupcakes',
            'category' => 'Birthday',
            'canvas_width' => 600,
            'canvas_height' => 800,
            'template_data' => [
                'version' => '1.0',
                'background' => [
                    'type' => 'gradient',
                    'colors' => ['#E8D5E3', '#D4B3D9'],
                    'direction' => 'vertical'
                ],
                'elements' => [
                    // Decorative balloons (top)
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 100, 'y' => 50, 'width' => 40, 'height' => 50, 'fill' => '#9B59B6', 'stroke' => '#8E44AD', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 200, 'y' => 40, 'width' => 35, 'height' => 45, 'fill' => '#E74C3C', 'stroke' => '#C0392B', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 300, 'y' => 45, 'width' => 38, 'height' => 48, 'fill' => '#3498DB', 'stroke' => '#2980B9', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 400, 'y' => 40, 'width' => 35, 'height' => 45, 'fill' => '#F39C12', 'stroke' => '#E67E22', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 500, 'y' => 50, 'width' => 40, 'height' => 50, 'fill' => '#1ABC9C', 'stroke' => '#16A085', 'strokeWidth' => 2],
                    
                    // Main title
                    ['type' => 'text', 'content' => 'EMMA IS TURNING 2', 'x' => 300, 'y' => 120, 'fontSize' => 32, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#8E44AD', 'textAlign' => 'center'],
                    
                    // Subtitle
                    ['type' => 'text', 'content' => 'Join us in birthday celebration', 'x' => 300, 'y' => 160, 'fontSize' => 18, 'fontFamily' => 'Arial', 'fill' => '#7F8C8D', 'textAlign' => 'center'],
                    
                    // Circular photo placeholder (small circle like Canva)
                    ['type' => 'image', 'x' => 250, 'y' => 200, 'width' => 100, 'height' => 100, 'placeholder' => true, 'label' => 'Photo', 'shape' => 'circle', 'borderRadius' => 50],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 250, 'y' => 200, 'width' => 100, 'height' => 100, 'fill' => 'transparent', 'stroke' => '#BDC3C7', 'strokeWidth' => 3, 'strokeDashArray' => [5, 5]],
                    
                    // Decorative cupcakes (bottom)
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 150, 'y' => 350, 'width' => 30, 'height' => 40, 'fill' => '#F39C12', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 155, 'y' => 340, 'width' => 20, 'height' => 20, 'fill' => '#E74C3C'],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 250, 'y' => 350, 'width' => 30, 'height' => 40, 'fill' => '#9B59B6', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 255, 'y' => 340, 'width' => 20, 'height' => 20, 'fill' => '#E74C3C'],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 350, 'y' => 350, 'width' => 30, 'height' => 40, 'fill' => '#3498DB', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 355, 'y' => 340, 'width' => 20, 'height' => 20, 'fill' => '#E74C3C'],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 450, 'y' => 350, 'width' => 30, 'height' => 40, 'fill' => '#1ABC9C', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 455, 'y' => 340, 'width' => 20, 'height' => 20, 'fill' => '#E74C3C'],
                    
                    // Event details
                    ['type' => 'text', 'content' => 'JUNE 9 | 4 PM', 'x' => 300, 'y' => 420, 'fontSize' => 20, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#2C3E50', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => '123 ANYWHERE ST, ANY CITY', 'x' => 300, 'y' => 460, 'fontSize' => 16, 'fontFamily' => 'Arial', 'fill' => '#7F8C8D', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => 'RSVP: [Phone Number]', 'x' => 300, 'y' => 500, 'fontSize' => 14, 'fontFamily' => 'Arial', 'fill' => '#95A5A6', 'textAlign' => 'center', 'placeholder' => true],
                ]
            ],
            'is_featured' => true
        ],
        [
            'name' => "Black & Gold Birthday Party",
            'description' => 'Sophisticated black and gold birthday invitation with tropical leaves',
            'category' => 'Birthday',
            'canvas_width' => 600,
            'canvas_height' => 800,
            'template_data' => [
                'version' => '1.0',
                'background' => ['type' => 'solid', 'color' => '#1A1A1A'],
                'elements' => [
                    // Gold decorative elements
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 80, 'y' => 100, 'width' => 60, 'height' => 60, 'fill' => '#FFD700', 'opacity' => 0.3],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 460, 'y' => 100, 'width' => 60, 'height' => 60, 'fill' => '#FFD700', 'opacity' => 0.3],
                    
                    // Main title
                    ['type' => 'text', 'content' => 'YOU ARE INVITED TO MY', 'x' => 300, 'y' => 200, 'fontSize' => 24, 'fontFamily' => 'Georgia', 'fontStyle' => 'italic', 'fill' => '#FFD700', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => 'Birthday PARTY', 'x' => 300, 'y' => 240, 'fontSize' => 36, 'fontFamily' => 'Georgia', 'fontWeight' => 'bold', 'fill' => '#FFD700', 'textAlign' => 'center'],
                    
                    // Circular photo placeholder
                    ['type' => 'image', 'x' => 250, 'y' => 300, 'width' => 100, 'height' => 100, 'placeholder' => true, 'label' => 'Photo', 'shape' => 'circle', 'borderRadius' => 50],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 250, 'y' => 300, 'width' => 100, 'height' => 100, 'fill' => 'transparent', 'stroke' => '#FFD700', 'strokeWidth' => 3, 'strokeDashArray' => [5, 5]],
                    
                    // Event details
                    ['type' => 'text', 'content' => 'MARCH SUNDAY 16 AT 5 PM 2024', 'x' => 300, 'y' => 450, 'fontSize' => 18, 'fontFamily' => 'Arial', 'fill' => '#FFD700', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => '[Phone Number]', 'x' => 300, 'y' => 500, 'fontSize' => 16, 'fontFamily' => 'Arial', 'fill' => '#BDC3C7', 'textAlign' => 'center', 'placeholder' => true],
                    ['type' => 'text', 'content' => '[Address]', 'x' => 300, 'y' => 540, 'fontSize' => 14, 'fontFamily' => 'Arial', 'fill' => '#95A5A6', 'textAlign' => 'center', 'placeholder' => true],
                ]
            ],
            'is_featured' => true
        ],
        [
            'name' => "Rachelle's Birthday Party",
            'description' => 'Rustic beige invitation with leafy decorations',
            'category' => 'Birthday',
            'canvas_width' => 600,
            'canvas_height' => 800,
            'template_data' => [
                'version' => '1.0',
                'background' => ['type' => 'solid', 'color' => '#F5E6D3'],
                'elements' => [
                    // Leaf decorations
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 100, 'y' => 80, 'width' => 30, 'height' => 30, 'fill' => '#8B4513', 'opacity' => 0.5],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 470, 'y' => 80, 'width' => 30, 'height' => 30, 'fill' => '#8B4513', 'opacity' => 0.5],
                    
                    // Title
                    ['type' => 'text', 'content' => "Please join us to celebrate", 'x' => 300, 'y' => 150, 'fontSize' => 20, 'fontFamily' => 'Georgia', 'fill' => '#654321', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => "Rachelle's Birthday Party", 'x' => 300, 'y' => 190, 'fontSize' => 28, 'fontFamily' => 'Georgia', 'fontWeight' => 'bold', 'fill' => '#8B4513', 'textAlign' => 'center'],
                    
                    // Circular photo placeholder
                    ['type' => 'image', 'x' => 250, 'y' => 250, 'width' => 100, 'height' => 100, 'placeholder' => true, 'label' => 'Photo', 'shape' => 'circle', 'borderRadius' => 50],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 250, 'y' => 250, 'width' => 100, 'height' => 100, 'fill' => 'transparent', 'stroke' => '#8B4513', 'strokeWidth' => 2, 'strokeDashArray' => [5, 5]],
                    
                    // Event details
                    ['type' => 'text', 'content' => 'December Sunday 15 03:00 PM', 'x' => 300, 'y' => 400, 'fontSize' => 18, 'fontFamily' => 'Arial', 'fill' => '#654321', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => '[Address]', 'x' => 300, 'y' => 450, 'fontSize' => 16, 'fontFamily' => 'Arial', 'fill' => '#8B4513', 'textAlign' => 'center', 'placeholder' => true],
                    ['type' => 'text', 'content' => 'RSVP: [Contact]', 'x' => 300, 'y' => 500, 'fontSize' => 14, 'fontFamily' => 'Arial', 'fill' => '#A0522D', 'textAlign' => 'center', 'placeholder' => true],
                ]
            ],
            'is_featured' => false
        ],
        [
            'name' => "Juliana's Birthday Party",
            'description' => 'Colorful invitation with bunting flags and golden balloons',
            'category' => 'Birthday',
            'canvas_width' => 600,
            'canvas_height' => 800,
            'template_data' => [
                'version' => '1.0',
                'background' => ['type' => 'gradient', 'colors' => ['#E3F2FD', '#BBDEFB'], 'direction' => 'vertical'],
                'elements' => [
                    // Bunting flags (top)
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 50, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#E74C3C', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 100, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#3498DB', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 150, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#F39C12', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 200, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#1ABC9C', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 250, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#9B59B6', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 300, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#E74C3C', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 350, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#3498DB', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 400, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#F39C12', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 450, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#1ABC9C', 'rx' => 2],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 500, 'y' => 50, 'width' => 40, 'height' => 30, 'fill' => '#9B59B6', 'rx' => 2],
                    
                    // Title banner
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 150, 'y' => 120, 'width' => 300, 'height' => 50, 'fill' => '#9B59B6', 'rx' => 5],
                    ['type' => 'text', 'content' => "JULIANA'S Birthday Party", 'x' => 300, 'y' => 150, 'fontSize' => 24, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#FFFFFF', 'textAlign' => 'center'],
                    
                    // Golden balloons
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 150, 'y' => 200, 'width' => 50, 'height' => 60, 'fill' => '#FFD700', 'stroke' => '#FFA500', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 400, 'y' => 200, 'width' => 50, 'height' => 60, 'fill' => '#FFD700', 'stroke' => '#FFA500', 'strokeWidth' => 2],
                    
                    // Circular photo placeholder
                    ['type' => 'image', 'x' => 250, 'y' => 280, 'width' => 100, 'height' => 100, 'placeholder' => true, 'label' => 'Photo', 'shape' => 'circle', 'borderRadius' => 50],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 250, 'y' => 280, 'width' => 100, 'height' => 100, 'fill' => 'transparent', 'stroke' => '#9B59B6', 'strokeWidth' => 3, 'strokeDashArray' => [5, 5]],
                    
                    // Event details
                    ['type' => 'text', 'content' => 'SUNDAY 27 AUGUST', 'x' => 300, 'y' => 420, 'fontSize' => 20, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#2C3E50', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => '7:00 PM - 10:00 PM', 'x' => 300, 'y' => 450, 'fontSize' => 18, 'fontFamily' => 'Arial', 'fill' => '#34495E', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => '[Address]', 'x' => 300, 'y' => 500, 'fontSize' => 16, 'fontFamily' => 'Arial', 'fill' => '#7F8C8D', 'textAlign' => 'center', 'placeholder' => true],
                    ['type' => 'text', 'content' => 'RSVP: [Contact]', 'x' => 300, 'y' => 540, 'fontSize' => 14, 'fontFamily' => 'Arial', 'fill' => '#95A5A6', 'textAlign' => 'center', 'placeholder' => true],
                ]
            ],
            'is_featured' => true
        ],
        [
            'name' => "Juliana Silva 18th Birthday Party",
            'description' => 'Light blue invitation with blue balloons and gift boxes',
            'category' => 'Birthday',
            'canvas_width' => 600,
            'canvas_height' => 800,
            'template_data' => [
                'version' => '1.0',
                'background' => ['type' => 'gradient', 'colors' => ['#E3F2FD', '#B3E5FC'], 'direction' => 'vertical'],
                'elements' => [
                    // Blue balloons (top)
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 120, 'y' => 80, 'width' => 45, 'height' => 55, 'fill' => '#2196F3', 'stroke' => '#1976D2', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 200, 'y' => 75, 'width' => 50, 'height' => 60, 'fill' => '#03A9F4', 'stroke' => '#0288D1', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 280, 'y' => 80, 'width' => 45, 'height' => 55, 'fill' => '#00BCD4', 'stroke' => '#0097A7', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 360, 'y' => 75, 'width' => 50, 'height' => 60, 'fill' => '#2196F3', 'stroke' => '#1976D2', 'strokeWidth' => 2],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 440, 'y' => 80, 'width' => 45, 'height' => 55, 'fill' => '#03A9F4', 'stroke' => '#0288D1', 'strokeWidth' => 2],
                    
                    // Title
                    ['type' => 'text', 'content' => 'YOU ARE INVITED TO', 'x' => 300, 'y' => 180, 'fontSize' => 22, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#1976D2', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => 'Juliana Silva 18 TH', 'x' => 300, 'y' => 220, 'fontSize' => 28, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#0D47A1', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => 'BIRTHDAY PARTY', 'x' => 300, 'y' => 260, 'fontSize' => 32, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#0D47A1', 'textAlign' => 'center'],
                    
                    // Circular photo placeholder
                    ['type' => 'image', 'x' => 250, 'y' => 320, 'width' => 100, 'height' => 100, 'placeholder' => true, 'label' => 'Photo', 'shape' => 'circle', 'borderRadius' => 50],
                    ['type' => 'shape', 'shape' => 'circle', 'x' => 250, 'y' => 320, 'width' => 100, 'height' => 100, 'fill' => 'transparent', 'stroke' => '#2196F3', 'strokeWidth' => 3, 'strokeDashArray' => [5, 5]],
                    
                    // Gift boxes (bottom)
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 150, 'y' => 480, 'width' => 50, 'height' => 50, 'fill' => '#2196F3', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 220, 'y' => 480, 'width' => 50, 'height' => 50, 'fill' => '#03A9F4', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 330, 'y' => 480, 'width' => 50, 'height' => 50, 'fill' => '#00BCD4', 'rx' => 5],
                    ['type' => 'shape', 'shape' => 'rectangle', 'x' => 400, 'y' => 480, 'width' => 50, 'height' => 50, 'fill' => '#2196F3', 'rx' => 5],
                    
                    // Event details
                    ['type' => 'text', 'content' => 'JANUARY MONDAY 15 10:00am', 'x' => 300, 'y' => 580, 'fontSize' => 18, 'fontFamily' => 'Arial', 'fontWeight' => 'bold', 'fill' => '#0D47A1', 'textAlign' => 'center'],
                    ['type' => 'text', 'content' => '[Address]', 'x' => 300, 'y' => 630, 'fontSize' => 16, 'fontFamily' => 'Arial', 'fill' => '#1976D2', 'textAlign' => 'center', 'placeholder' => true],
                ]
            ],
            'is_featured' => false
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO design_templates (name, description, category, canvas_width, canvas_height, template_data, is_public, is_featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            description = VALUES(description),
            template_data = VALUES(template_data),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $inserted = 0;
    foreach ($templates as $template) {
        $stmt->execute([
            $template['name'],
            $template['description'],
            $template['category'],
            $template['canvas_width'],
            $template['canvas_height'],
            json_encode($template['template_data']),
            true,
            $template['is_featured']
        ]);
        $inserted++;
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => "Created {$inserted} Canva-style birthday templates with circular image placeholders",
        'templates' => $inserted
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error creating templates: ' . $e->getMessage()
    ]);
}
?>



