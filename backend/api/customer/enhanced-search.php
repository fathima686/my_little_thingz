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

// Enhanced Search with Bayesian ML Integration
class EnhancedSearchAPI {
    
    private $mysqli;
    private $python_ml_url = 'http://localhost:5001/api/ml';
    
    public function __construct() {
        $this->mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
        $this->mysqli->set_charset('utf8mb4');
        
        if ($this->mysqli->connect_error) {
            throw new Exception("Database connection failed: " . $this->mysqli->connect_error);
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'search';
        
        try {
            switch ($action) {
                case 'search':
                    return $this->enhancedSearch();
                case 'suggestions':
                    return $this->getSearchSuggestionsAPI();
                case 'category-predict':
                    return $this->predictCategory();
                default:
                    return $this->errorResponse('Invalid action');
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    private function enhancedSearch() {
        $searchTerm = $_GET['term'] ?? $_POST['term'] ?? '';
        $limit = intval($_GET['limit'] ?? 20);
        $category = $_GET['category'] ?? '';
        $minPrice = floatval($_GET['min_price'] ?? 0);
        $maxPrice = floatval($_GET['max_price'] ?? 999999);
        
        if (empty($searchTerm)) {
            return $this->errorResponse('Search term is required');
        }
        
        // Step 1: Get ML prediction for the search term
        $mlPrediction = $this->getMLPrediction($searchTerm);
        
        // Step 2: Build enhanced search query
        $searchResults = $this->performEnhancedSearch($searchTerm, $mlPrediction, $limit, $category, $minPrice, $maxPrice);
        
        // Step 3: Add ML insights to results
        $searchResults['ml_insights'] = $mlPrediction;
        $searchResults['search_enhancement'] = $this->getSearchEnhancement($searchTerm, $mlPrediction);
        
        return $this->successResponse($searchResults);
    }
    
    private function getMLPrediction($searchTerm) {
        try {
            // Call Python ML service for Gift Category Prediction
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->python_ml_url . '/gift-category/predict');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'search_term' => $searchTerm,
                'confidence_threshold' => 0.6
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Reduced timeout for efficiency
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Faster connection timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $mlData = json_decode($response, true);
                if ($mlData && $mlData['success']) {
                    return [
                        'predicted_category' => $mlData['predicted_category'],
                        'confidence' => $mlData['confidence'],
                        'confidence_percent' => isset($mlData['confidence_percent']) ? $mlData['confidence_percent'] : ($mlData['confidence'] * 100),
                        'algorithm' => $mlData['algorithm'],
                        'suggestions' => $mlData['recommendations'] ?? [],
                        'action' => $mlData['action'] ?? 'suggest'
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("ML prediction error: " . $e->getMessage());
        }
        
        // Enhanced fallback to keyword-based prediction for efficiency
        return $this->getEnhancedFallbackPrediction($searchTerm);
    }
    
    private function getEnhancedFallbackPrediction($searchTerm) {
        $searchTerm = strtolower(trim($searchTerm));
        
        // Enhanced keyword mapping for your exact special keywords
        $specialKeywords = [
            'sweet' => [
                'category' => 'sweet',
                'confidence' => 0.95,
                'suggestions' => ['Custom Chocolate Box', 'Chocolate Bouquet']
            ],
            'wedding' => [
                'category' => 'wedding',
                'confidence' => 0.95,
                'suggestions' => ['Wedding Card', 'Couple Frame', 'Wedding Hamper']
            ],
            'birthday' => [
                'category' => 'birthday',
                'confidence' => 0.95,
                'suggestions' => ['Birthday Cake Topper', 'Birthday Mug', 'Greeting Card']
            ],
            'baby' => [
                'category' => 'baby',
                'confidence' => 0.95,
                'suggestions' => ['Baby Rattle', 'Soft Toy', 'Baby Blanket']
            ],
            'valentine' => [
                'category' => 'valentine',
                'confidence' => 0.95,
                'suggestions' => ['Love Frame', 'Heart Chocolate', 'Couple Lamp']
            ],
            'house' => [
                'category' => 'house',
                'confidence' => 0.95,
                'suggestions' => ['Wall Frame', 'Indoor Plant', 'Name Plate']
            ],
            'farewell' => [
                'category' => 'farewell',
                'confidence' => 0.95,
                'suggestions' => ['Pen Set', 'Thank You Card', 'Planner Diary']
            ]
        ];
        
        // Direct match for your special keywords
        if (isset($specialKeywords[$searchTerm])) {
            $data = $specialKeywords[$searchTerm];
            return [
                'predicted_category' => $data['category'],
                'confidence' => $data['confidence'],
                'confidence_percent' => $data['confidence'] * 100,
                'algorithm' => 'Enhanced Keyword Matching',
                'suggestions' => $data['suggestions'],
                'action' => 'auto_assign'
            ];
        }
        
        // Fallback to original method for other keywords
        return $this->getFallbackPrediction($searchTerm);
    }
    
    private function getFallbackPrediction($searchTerm) {
        $searchTerm = strtolower(trim($searchTerm));
        
        // Enhanced keyword mapping for fallback based on your exact requirements
        $keywordMappings = [
            'sweet' => ['sweet'],
            'wedding' => ['wedding'],
            'birthday' => ['birthday'],
            'baby' => ['baby'],
            'valentine' => ['valentine'],
            'house' => ['house'],
            'farewell' => ['farewell']
        ];
        
        $predictedCategory = 'gift_box'; // Default
        $confidence = 0.3;
        $suggestions = ['Gift Box', 'Gift Hamper', 'Gift Basket'];
        
        foreach ($keywordMappings as $keyword => $categories) {
            if (strpos($searchTerm, $keyword) !== false) {
                $predictedCategory = $categories[0];
                $confidence = 0.7;
                
                // Set appropriate suggestions based on your exact requirements
                switch ($predictedCategory) {
                    case 'sweet':
                        $suggestions = ['Custom Chocolate Box', 'Chocolate Bouquet'];
                        break;
                    case 'wedding':
                        $suggestions = ['Wedding Card', 'Couple Frame', 'Wedding Hamper'];
                        break;
                    case 'birthday':
                        $suggestions = ['Birthday Cake Topper', 'Birthday Mug', 'Greeting Card'];
                        break;
                    case 'baby':
                        $suggestions = ['Baby Rattle', 'Soft Toy', 'Baby Blanket'];
                        break;
                    case 'valentine':
                        $suggestions = ['Love Frame', 'Heart Chocolate', 'Couple Lamp'];
                        break;
                    case 'house':
                        $suggestions = ['Wall Frame', 'Indoor Plant', 'Name Plate'];
                        break;
                    case 'farewell':
                        $suggestions = ['Pen Set', 'Thank You Card', 'Planner Diary'];
                        break;
                    default:
                        $suggestions = ['Gift Box', 'Gift Hamper', 'Gift Basket'];
                }
                break;
            }
        }
        
        return [
            'predicted_category' => $predictedCategory,
            'confidence' => $confidence,
            'confidence_percent' => $confidence * 100,
            'algorithm' => 'Keyword Fallback',
            'suggestions' => $suggestions,
            'action' => 'suggest'
        ];
    }
    
    private function performEnhancedSearch($searchTerm, $mlPrediction, $limit, $category, $minPrice, $maxPrice) {
        // Simplified enhanced search that works reliably
        $artworks = [];
        $categoryGroups = [];
        
        // Get comprehensive search terms
        $expandedTerms = $this->getComprehensiveSearchTerms($searchTerm);
        $relatedCategories = $this->getRelatedCategories($searchTerm);
        
        // Build search patterns
        $searchPatterns = [$searchTerm];
        if (!empty($expandedTerms)) {
            $searchPatterns = array_merge($searchPatterns, array_slice($expandedTerms, 0, 5)); // Limit to 5 terms
        }
        
        // Search for each pattern
        foreach ($searchPatterns as $pattern) {
            $sql = "SELECT DISTINCT a.*, c.name as category_name, c.id as category_id
                    FROM artworks a
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE a.status = 'active' 
                    AND a.availability != 'out_of_stock'
                    AND (a.title LIKE ? OR a.description LIKE ? OR c.name LIKE ?)";
            
            // Add price filters
            if ($minPrice > 0) {
                $sql .= " AND a.price >= ?";
            }
            if ($maxPrice < 999999) {
                $sql .= " AND a.price <= ?";
            }
            
            $sql .= " ORDER BY 
                        CASE 
                            WHEN a.title LIKE ? THEN 1
                            WHEN a.description LIKE ? THEN 2
                            WHEN c.name LIKE ? THEN 3
                            ELSE 4
                        END ASC
                    LIMIT ?";
            
            $stmt = $this->mysqli->prepare($sql);
            if ($stmt) {
                $patternParam = "%{$pattern}%";
                $params = [$patternParam, $patternParam, $patternParam];
                $paramTypes = 'sss';
                
                if ($minPrice > 0) {
                    $params[] = $minPrice;
                    $paramTypes .= 'd';
                }
                if ($maxPrice < 999999) {
                    $params[] = $maxPrice;
                    $paramTypes .= 'd';
                }
                
                $params[] = $patternParam;
                $params[] = $patternParam;
                $params[] = $patternParam;
                $paramTypes .= 'sssi';
                $params[] = $limit;
                
                $stmt->bind_param($paramTypes, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $artwork = $this->formatArtwork($row);
                    
                    // Avoid duplicates
                    $found = false;
                    foreach ($artworks as $existing) {
                        if ($existing['id'] == $artwork['id']) {
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $artworks[] = $artwork;
                        
                        // Group by category
                        $categoryName = $artwork['category_name'];
                        if (!isset($categoryGroups[$categoryName])) {
                            $categoryGroups[$categoryName] = [];
                        }
                        $categoryGroups[$categoryName][] = $artwork;
                    }
                }
                
                $stmt->close();
            }
        }
        
        // Also search by related categories
        if (!empty($relatedCategories)) {
            foreach ($relatedCategories as $catName) {
                $sql = "SELECT DISTINCT a.*, c.name as category_name, c.id as category_id
                        FROM artworks a
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE a.status = 'active' 
                        AND a.availability != 'out_of_stock'
                        AND c.name = ?";
                
                if ($minPrice > 0) {
                    $sql .= " AND a.price >= ?";
                }
                if ($maxPrice < 999999) {
                    $sql .= " AND a.price <= ?";
                }
                
                $sql .= " ORDER BY a.price ASC LIMIT ?";
                
                $stmt = $this->mysqli->prepare($sql);
                if ($stmt) {
                    $params = [$catName];
                    $paramTypes = 's';
                    
                    if ($minPrice > 0) {
                        $params[] = $minPrice;
                        $paramTypes .= 'd';
                    }
                    if ($maxPrice < 999999) {
                        $params[] = $maxPrice;
                        $paramTypes .= 'd';
                    }
                    
                    $params[] = $limit;
                    $paramTypes .= 'i';
                    
                    $stmt->bind_param($paramTypes, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $artwork = $this->formatArtwork($row);
                        
                        // Avoid duplicates
                        $found = false;
                        foreach ($artworks as $existing) {
                            if ($existing['id'] == $artwork['id']) {
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            $artworks[] = $artwork;
                            
                            // Group by category
                            $categoryName = $artwork['category_name'];
                            if (!isset($categoryGroups[$categoryName])) {
                                $categoryGroups[$categoryName] = [];
                            }
                            $categoryGroups[$categoryName][] = $artwork;
                        }
                    }
                    
                    $stmt->close();
                }
            }
        }
        
        // Limit total results
        $artworks = array_slice($artworks, 0, $limit);
        
        return [
            'artworks' => $artworks,
            'category_groups' => $categoryGroups,
            'total_found' => count($artworks),
            'search_term' => $searchTerm,
            'search_enhanced' => true,
            'related_categories' => array_keys($categoryGroups)
        ];
    }
    
    private function getComprehensiveSearchTerms($searchTerm) {
        $expandedTerms = [$searchTerm];
        
        // Comprehensive keyword mapping for your exact search inputs
        $comprehensiveMappings = [
            'sweet' => [
                'chocolate', 'candy', 'treat', 'dessert', 'sugar', 'cocoa', 'truffle', 'praline',
                'ganache', 'fudge', 'brownie', 'cookie', 'biscuit', 'indulgence', 'caramel',
                'toffee', 'nougat', 'mint', 'vanilla', 'strawberry', 'hazelnut', 'almond',
                'walnut', 'peanut', 'm&m', 'kitkat', 'snickers', 'twix', 'mars', 'bounty',
                'ferrero', 'lindt', 'cadbury', 'nestle', 'hershey', 'godiva', 'toblerone'
            ],
            'wedding' => [
                'card', 'invitation', 'invite', 'ceremony', 'marriage', 'bride', 'groom',
                'couple', 'union', 'matrimony', 'nuptials', 'reception', 'party',
                'celebration', 'anniversary', 'engagement', 'proposal', 'love', 'romance',
                'special day', 'big day', 'happily ever after', 'forever', 'together',
                'wedding invitation', 'marriage card', 'nuptial card', 'wedding invite'
            ],
            'birthday' => [
                'cake', 'topper', 'mug', 'greeting card', 'celebration', 'party',
                'age', 'years old', 'turning', 'special day', 'birthday gift', 'birthday present',
                'birthday card', 'birthday mug', 'birthday cake topper', 'birthday decoration',
                'party supplies', 'celebration gift', 'age milestone', 'birthday surprise'
            ],
            'baby' => [
                'infant', 'newborn', 'toddler', 'child', 'kids', 'rattle', 'soft toy',
                'blanket', 'baby gift', 'baby present', 'baby shower', 'newborn gift',
                'baby rattle', 'soft toy', 'baby blanket', 'baby clothes', 'baby accessories',
                'nursery', 'crib', 'stroller', 'diaper', 'feeding', 'baby care'
            ],
            'valentine' => [
                'valentines', 'love', 'romantic', 'heart', 'couple', 'lamp',
                'romance', 'affection', 'passion', 'intimate', 'sweetheart', 'beloved',
                'love frame', 'heart chocolate', 'couple lamp', 'romantic gift', 'love gift',
                'valentine gift', 'romantic present', 'couple gift', 'love present'
            ],
            'house' => [
                'home', 'wall', 'frame', 'indoor', 'plant', 'name plate', 'decoration',
                'home decor', 'wall art', 'indoor plant', 'name plate', 'housewarming',
                'home gift', 'house gift', 'decoration', 'interior', 'furnishing', 'home accessory'
            ],
            'farewell' => [
                'goodbye', 'leaving', 'departure', 'retirement', 'moving', 'pen set',
                'thank you card', 'planner diary', 'farewell gift', 'goodbye present',
                'leaving gift', 'departure gift', 'retirement gift', 'moving gift',
                'thank you', 'appreciation', 'gratitude', 'memories', 'keepsake'
            ]
        ];
        
        $searchTermLower = strtolower(trim($searchTerm));
        
        // Get comprehensive terms for the search keyword
        if (isset($comprehensiveMappings[$searchTermLower])) {
            $expandedTerms = array_merge($expandedTerms, $comprehensiveMappings[$searchTermLower]);
        }
        
        // Also check for partial matches
        foreach ($comprehensiveMappings as $key => $terms) {
            if (strpos($key, $searchTermLower) !== false || strpos($searchTermLower, $key) !== false) {
                $expandedTerms = array_merge($expandedTerms, $terms);
            }
        }
        
        return array_unique($expandedTerms);
    }
    
    private function getRelatedCategories($searchTerm) {
        $searchTermLower = strtolower(trim($searchTerm));
        
        // Map search terms to actual category names in your database
        $categoryMappings = [
            'sweet' => ['custom chocolate', 'Gift box'],
            'chocolate' => ['custom chocolate'],
            'flower' => ['boquetes'],
            'roses' => ['boquetes'],
            'bouquet' => ['boquetes'],
            'gift' => ['Gift box', 'boquetes', 'custom chocolate'],
            'wedding' => ['Wedding card', 'boquetes', 'Gift box'],
            'card' => ['Wedding card'],
            'custom' => ['custom chocolate', 'Gift box'],
            'personalized' => ['custom chocolate', 'Gift box'],
            'nuts' => ['Gift box'], // nuts hamper is in Gift box category
            'healthy' => ['Gift box'],
            'premium' => ['Gift box', 'custom chocolate'],
            'luxury' => ['Gift box', 'custom chocolate'],
            'romantic' => ['boquetes', 'custom chocolate'],
            'love' => ['boquetes', 'custom chocolate'],
            'anniversary' => ['Wedding card', 'boquetes', 'custom chocolate'],
            'valentine' => ['boquetes', 'custom chocolate'],
            'birthday' => ['Wedding card', 'Gift box'], // birthday cards and gifts
            'frame' => ['frames'],
            'photo' => ['poloroid', 'album'],
            'drawing' => ['drawings'],
            'art' => ['drawings']
        ];
        
        $relatedCategories = [];
        
        // Direct match
        if (isset($categoryMappings[$searchTermLower])) {
            $relatedCategories = array_merge($relatedCategories, $categoryMappings[$searchTermLower]);
        }
        
        // Partial match
        foreach ($categoryMappings as $key => $categories) {
            if (strpos($key, $searchTermLower) !== false || strpos($searchTermLower, $key) !== false) {
                $relatedCategories = array_merge($relatedCategories, $categories);
            }
        }
        
        return array_unique($relatedCategories);
    }
    
    private function expandSearchTerms($searchTerm, $mlPrediction) {
        $expandedTerms = [$searchTerm];
        
        // Add ML-predicted category terms
        if (!empty($mlPrediction['predicted_category'])) {
            $categoryTerms = $this->getCategoryTerms($mlPrediction['predicted_category']);
            $expandedTerms = array_merge($expandedTerms, $categoryTerms);
        }
        
        // Add semantic synonyms
        $semanticTerms = $this->getSemanticSynonyms($searchTerm);
        $expandedTerms = array_merge($expandedTerms, $semanticTerms);
        
        return array_unique($expandedTerms);
    }
    
    private function getCategoryTerms($category) {
        $categoryTerms = [
            'chocolate' => ['sweet', 'candy', 'treat', 'dessert', 'cocoa', 'truffle'],
            'bouquet' => ['flower', 'roses', 'floral', 'arrangement', 'bloom'],
            'gift_box' => ['hamper', 'basket', 'package', 'collection', 'set'],
            'wedding_card' => ['invitation', 'card', 'ceremony', 'marriage', 'bridal'],
            'custom_chocolate' => ['personalized', 'bespoke', 'engraved', 'customized'],
            'nuts' => ['almond', 'walnut', 'trail mix', 'healthy', 'snack']
        ];
        
        return $categoryTerms[$category] ?? [];
    }
    
    private function getSemanticSynonyms($searchTerm) {
        $synonyms = [
            'sweet' => ['candy', 'treat', 'dessert', 'sugar'],
            'gift' => ['present', 'surprise', 'special', 'occasion'],
            'premium' => ['luxury', 'deluxe', 'high-end', 'exclusive'],
            'custom' => ['personalized', 'bespoke', 'tailored', 'unique'],
            'romantic' => ['love', 'anniversary', 'valentine', 'couple'],
            'healthy' => ['natural', 'organic', 'nutritious', 'wholesome']
        ];
        
        return $synonyms[strtolower($searchTerm)] ?? [];
    }
    
    private function getSearchEnhancement($searchTerm, $mlPrediction) {
        $enhancement = [
            'original_search' => $searchTerm,
            'ml_prediction' => $mlPrediction['predicted_category'],
            'confidence' => $mlPrediction['confidence_percent'],
            'search_expanded' => true,
            'algorithm_used' => $mlPrediction['algorithm']
        ];
        
        // Add search suggestions based on prediction
        if ($mlPrediction['confidence'] > 0.7) {
            $enhancement['suggestions'] = $this->getSearchSuggestions($searchTerm, $mlPrediction);
        }
        
        return $enhancement;
    }
    
    private function getSearchSuggestions($searchTerm, $mlPrediction) {
        $suggestions = [];
        
        // Add related search terms
        $relatedTerms = $this->getCategoryTerms($mlPrediction['predicted_category']);
        foreach ($relatedTerms as $term) {
            if ($term !== $searchTerm) {
                $suggestions[] = $term;
            }
        }
        
        return array_slice($suggestions, 0, 5);
    }
    
    private function getSearchSuggestionsAPI() {
        $searchTerm = $_GET['term'] ?? '';
        
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return $this->successResponse(['suggestions' => []]);
        }
        
        // Get ML prediction for suggestions
        $mlPrediction = $this->getMLPrediction($searchTerm);
        
        // Generate suggestions based on prediction
        $suggestions = [];
        
        if ($mlPrediction['confidence'] > 0.6) {
            $categoryTerms = $this->getCategoryTerms($mlPrediction['predicted_category']);
            $suggestions = array_slice($categoryTerms, 0, 8);
        }
        
        // Add common search terms
        $commonTerms = ['photo', 'gift', 'custom', 'premium', 'wedding', 'flower', 'romantic'];
        foreach ($commonTerms as $term) {
            if (strpos($term, $searchTerm) !== false && !in_array($term, $suggestions)) {
                $suggestions[] = $term;
            }
        }
        
        return $this->successResponse([
            'suggestions' => array_slice($suggestions, 0, 10),
            'ml_enhanced' => true,
            'predicted_category' => $mlPrediction['predicted_category']
        ]);
    }
    
    private function predictCategory() {
        $searchTerm = $_GET['term'] ?? $_POST['term'] ?? '';
        
        if (empty($searchTerm)) {
            return $this->errorResponse('Search term is required');
        }
        
        $mlPrediction = $this->getMLPrediction($searchTerm);
        
        return $this->successResponse([
            'search_term' => $searchTerm,
            'prediction' => $mlPrediction
        ]);
    }
    
    private function formatArtwork($row) {
        return [
            'id' => intval($row['id']),
            'title' => $row['title'],
            'description' => $row['description'],
            'price' => floatval($row['price']),
            'image_url' => $row['image_url'],
            'category_id' => intval($row['category_id']),
            'category_name' => $row['category_name'],
            'availability' => $row['availability'],
            'created_at' => $row['created_at']
        ];
    }
    
    private function successResponse($data) {
        return json_encode([
            'status' => 'success',
            'data' => $data,
            'timestamp' => date('c')
        ]);
    }
    
    private function errorResponse($message) {
        return json_encode([
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c')
        ]);
    }
}

// Handle the request
try {
    $api = new EnhancedSearchAPI();
    echo $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
