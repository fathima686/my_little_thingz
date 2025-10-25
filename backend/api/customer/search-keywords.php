<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Comprehensive keyword mapping for enhanced search
class SearchKeywordMapper {
    
    private static $keywordMappings = [
        // Sweet treats and chocolates
        'sweet' => ['chocolate', 'candy', 'treats', 'dessert', 'sugar', 'sweet', 'chocolates', 'custom chocolate', 'personalized chocolate'],
        'sweet treats' => ['chocolate', 'candy', 'treats', 'dessert', 'sugar', 'sweet', 'chocolates', 'custom chocolate', 'personalized chocolate'],
        'chocolate' => ['chocolate', 'candy', 'treats', 'dessert', 'sugar', 'sweet', 'chocolates', 'custom chocolate', 'personalized chocolate'],
        'candy' => ['chocolate', 'candy', 'treats', 'dessert', 'sugar', 'sweet', 'chocolates', 'custom chocolate', 'personalized chocolate'],
        'treats' => ['chocolate', 'candy', 'treats', 'dessert', 'sugar', 'sweet', 'chocolates', 'custom chocolate', 'personalized chocolate'],
        'dessert' => ['chocolate', 'candy', 'treats', 'dessert', 'sugar', 'sweet', 'chocolates', 'custom chocolate', 'personalized chocolate'],
        
        // Photos and polaroids
        'photo' => ['polaroid', 'photos', 'picture', 'image', 'print', 'polaroids', 'instant photo', 'photo print'],
        'photos' => ['polaroid', 'photos', 'picture', 'image', 'print', 'polaroids', 'instant photo', 'photo print'],
        'polaroid' => ['polaroid', 'photos', 'picture', 'image', 'print', 'polaroids', 'instant photo', 'photo print'],
        'polaroids' => ['polaroid', 'photos', 'picture', 'image', 'print', 'polaroids', 'instant photo', 'photo print'],
        'picture' => ['polaroid', 'photos', 'picture', 'image', 'print', 'polaroids', 'instant photo', 'photo print'],
        'print' => ['polaroid', 'photos', 'picture', 'image', 'print', 'polaroids', 'instant photo', 'photo print'],
        
        // Frames and displays
        'frame' => ['frame', 'frames', 'photo frame', 'picture frame', 'display', 'wall frame', 'a4 frame', 'a3 frame'],
        'frames' => ['frame', 'frames', 'photo frame', 'picture frame', 'display', 'wall frame', 'a4 frame', 'a3 frame'],
        'display' => ['frame', 'frames', 'photo frame', 'picture frame', 'display', 'wall frame', 'a4 frame', 'a3 frame'],
        
        // Albums and books
        'album' => ['album', 'albums', 'photo album', 'scrapbook', 'book', 'memory book', 'photo book'],
        'albums' => ['album', 'albums', 'photo album', 'scrapbook', 'book', 'memory book', 'photo book'],
        'scrapbook' => ['album', 'albums', 'photo album', 'scrapbook', 'book', 'memory book', 'photo book'],
        'book' => ['album', 'albums', 'photo album', 'scrapbook', 'book', 'memory book', 'photo book'],
        
        // Gifts and hampers
        'gift' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'gifts' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'gift box' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'gift boxes' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'hamper' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'hampers' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'present' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        'presents' => ['gift', 'gifts', 'gift box', 'gift boxes', 'hamper', 'hampers', 'present', 'presents'],
        
        // Flowers and bouquets
        'flower' => ['flower', 'flowers', 'bouquet', 'bouquets', 'arrangement', 'floral', 'bloom'],
        'flowers' => ['flower', 'flowers', 'bouquet', 'bouquets', 'arrangement', 'floral', 'bloom'],
        'bouquet' => ['flower', 'flowers', 'bouquet', 'bouquets', 'arrangement', 'floral', 'bloom'],
        'bouquets' => ['flower', 'flowers', 'bouquet', 'bouquets', 'arrangement', 'floral', 'bloom'],
        'floral' => ['flower', 'flowers', 'bouquet', 'bouquets', 'arrangement', 'floral', 'bloom'],
        
        // Wedding related
        'wedding' => ['wedding', 'wedding hamper', 'wedding card', 'marriage', 'bridal', 'ceremony'],
        'marriage' => ['wedding', 'wedding hamper', 'wedding card', 'marriage', 'bridal', 'ceremony'],
        'bridal' => ['wedding', 'wedding hamper', 'wedding card', 'marriage', 'bridal', 'ceremony'],
        
        // Art and drawings
        'art' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'artwork' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'drawing' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'drawings' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'sketch' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'portrait' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'custom' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        'handmade' => ['art', 'artwork', 'drawing', 'drawings', 'sketch', 'portrait', 'custom', 'handmade'],
        
        // Size related
        'small' => ['mini', 'micro', 'small', 'compact', 'tiny'],
        'mini' => ['mini', 'micro', 'small', 'compact', 'tiny'],
        'micro' => ['mini', 'micro', 'small', 'compact', 'tiny'],
        'large' => ['large', 'big', 'a3', 'a4', 'bigger'],
        'big' => ['large', 'big', 'a3', 'a4', 'bigger'],
        
        // Price related
        'cheap' => ['budget', 'affordable', 'low cost', 'inexpensive', 'cheap'],
        'budget' => ['budget', 'affordable', 'low cost', 'inexpensive', 'cheap'],
        'affordable' => ['budget', 'affordable', 'low cost', 'inexpensive', 'cheap'],
        'expensive' => ['premium', 'luxury', 'high end', 'expensive', 'deluxe'],
        'premium' => ['premium', 'luxury', 'high end', 'expensive', 'deluxe'],
        'luxury' => ['premium', 'luxury', 'high end', 'expensive', 'deluxe'],
        
        // Occasion related
        'birthday' => ['birthday', 'birthday gift', 'birthday box', 'celebration', 'party'],
        'anniversary' => ['anniversary', 'anniversary gift', 'celebration', 'milestone'],
        'valentine' => ['valentine', 'valentine gift', 'romantic', 'love', 'couple'],
        'mother' => ['mother', 'mom', 'mothers day', 'maternal', 'parent'],
        'father' => ['father', 'dad', 'fathers day', 'paternal', 'parent'],
        'graduation' => ['graduation', 'graduation gift', 'achievement', 'success'],
        'baby' => ['baby', 'newborn', 'infant', 'child', 'kids'],
        'kids' => ['baby', 'newborn', 'infant', 'child', 'kids'],
        'child' => ['baby', 'newborn', 'infant', 'child', 'kids'],
        
        // Material related
        'wood' => ['wood', 'wooden', 'timber', 'oak', 'pine'],
        'metal' => ['metal', 'steel', 'iron', 'aluminum', 'brass'],
        'glass' => ['glass', 'crystal', 'transparent', 'clear'],
        'paper' => ['paper', 'cardboard', 'card', 'sheet'],
        'fabric' => ['fabric', 'cloth', 'textile', 'cotton', 'silk'],
        
        // Color related
        'blue' => ['blue', 'navy', 'sky', 'azure', 'cobalt'],
        'red' => ['red', 'crimson', 'scarlet', 'burgundy', 'maroon'],
        'green' => ['green', 'emerald', 'forest', 'lime', 'mint'],
        'yellow' => ['yellow', 'gold', 'amber', 'lemon', 'golden'],
        'pink' => ['pink', 'rose', 'magenta', 'coral', 'salmon'],
        'purple' => ['purple', 'violet', 'lavender', 'plum', 'indigo'],
        'black' => ['black', 'dark', 'charcoal', 'ebony', 'onyx'],
        'white' => ['white', 'ivory', 'cream', 'pearl', 'snow'],
        
        // Quality related
        'quality' => ['quality', 'premium', 'high quality', 'excellent', 'superior'],
        'durable' => ['durable', 'strong', 'sturdy', 'long lasting', 'robust'],
        'beautiful' => ['beautiful', 'gorgeous', 'stunning', 'lovely', 'attractive'],
        'elegant' => ['elegant', 'sophisticated', 'classy', 'refined', 'graceful'],
        
        // Action related
        'buy' => ['buy', 'purchase', 'order', 'get', 'acquire'],
        'purchase' => ['buy', 'purchase', 'order', 'get', 'acquire'],
        'order' => ['buy', 'purchase', 'order', 'get', 'acquire'],
        'gift' => ['gift', 'present', 'give', 'donate', 'offer'],
        'present' => ['gift', 'present', 'give', 'donate', 'offer'],
    ];
    
    public static function expandSearchTerms($searchTerm) {
        $searchTerm = strtolower(trim($searchTerm));
        
        // If exact match found, return expanded terms
        if (isset(self::$keywordMappings[$searchTerm])) {
            return self::$keywordMappings[$searchTerm];
        }
        
        // Check for partial matches
        $expandedTerms = [$searchTerm]; // Always include original term
        
        foreach (self::$keywordMappings as $key => $values) {
            if (strpos($key, $searchTerm) !== false || strpos($searchTerm, $key) !== false) {
                $expandedTerms = array_merge($expandedTerms, $values);
            }
        }
        
        // Remove duplicates and return
        return array_unique($expandedTerms);
    }
    
    public static function getSearchSuggestions($searchTerm) {
        $searchTerm = strtolower(trim($searchTerm));
        $suggestions = [];
        
        foreach (self::$keywordMappings as $key => $values) {
            if (strpos($key, $searchTerm) !== false) {
                $suggestions[] = $key;
            }
        }
        
        return array_slice($suggestions, 0, 10); // Return top 10 suggestions
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'expand';
    $searchTerm = $_GET['term'] ?? '';
    
    if (empty($searchTerm)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Search term is required'
        ]);
        exit;
    }
    
    switch ($action) {
        case 'expand':
            $expandedTerms = SearchKeywordMapper::expandSearchTerms($searchTerm);
            echo json_encode([
                'status' => 'success',
                'original_term' => $searchTerm,
                'expanded_terms' => $expandedTerms,
                'count' => count($expandedTerms)
            ]);
            break;
            
        case 'suggestions':
            $suggestions = SearchKeywordMapper::getSearchSuggestions($searchTerm);
            echo json_encode([
                'status' => 'success',
                'suggestions' => $suggestions,
                'count' => count($suggestions)
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action. Use "expand" or "suggestions"'
            ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
?>



