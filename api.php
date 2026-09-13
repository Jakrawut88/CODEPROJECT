<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// เอาไว้ยิงมาทำ dropdown ฝั่งหน้าบ้าน
if ($action == 'get_districts') {
    $stmt = $pdo->query("SELECT * FROM districts ORDER BY name_th ASC");
    echo json_encode($stmt->fetchAll());
    exit;
}

// เอาไว้ตั้งขอบเขตต่ำสุด-สูงสุดของสไลเดอร์ราคา
if ($action == 'get_price_range') {
    $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM accommodations WHERE status = 'approved'");
    $range = $stmt->fetch();
    echo json_encode([
        'min' => $range['min_price'] !== null ? intval($range['min_price']) : 0,
        'max' => $range['max_price'] !== null ? intval($range['max_price']) : 10000,
    ]);
    exit;
}

// filter ที่พักตามที่ user เลือก (เขต, ช่วงราคา, หรือตำแหน่งปัจจุบัน — ใช้ร่วมกันได้)
$district_id = isset($_GET['district_id']) ? $_GET['district_id'] : '';
$user_lat = isset($_GET['lat']) ? $_GET['lat'] : '';
$user_lng = isset($_GET['lng']) ? $_GET['lng'] : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : null;

try {
    $where = ["a.status = 'approved'"];
    $params = [];

    if ($district_id != '') {
        $where[] = "a.district_id = :district_id";
        $params['district_id'] = $district_id;
    }
    if ($min_price !== null) {
        $where[] = "a.price >= :min_price";
        $params['min_price'] = $min_price;
    }
    if ($max_price !== null) {
        $where[] = "a.price <= :max_price";
        $params['max_price'] = $max_price;
    }

    $where_sql = implode(' AND ', $where);

    if ($user_lat != '' && $user_lng != '') {
        // haversine formula หาระยะทางจากพิกัด user, กรองเอาแค่ในรัศมี 20 กม.
        $params['lat1'] = $user_lat;
        $params['lng1'] = $user_lng;
        $params['lat2'] = $user_lat;

        $sql = "SELECT a.*, d.name_th as district_name,
                (SELECT filename FROM accommodation_images WHERE accommodation_id = a.id ORDER BY sort_order ASC LIMIT 1) AS cover_image,
                (6371 * acos(cos(radians(:lat1)) * cos(radians(a.lat)) * cos(radians(a.lng) - radians(:lng1)) + sin(radians(:lat2)) * sin(radians(a.lat)))) AS distance 
                FROM accommodations a
                LEFT JOIN districts d ON a.district_id = d.id
                WHERE $where_sql
                HAVING distance <= 20 
                ORDER BY distance ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

    } else {
        $sql = "SELECT a.*, d.name_th as district_name,
                (SELECT filename FROM accommodation_images WHERE accommodation_id = a.id ORDER BY sort_order ASC LIMIT 1) AS cover_image
                FROM accommodations a 
                LEFT JOIN districts d ON a.district_id = d.id 
                WHERE $where_sql ORDER BY a.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    // ส่ง error กลับเป็น JSON แทนที่จะพังเงียบๆ จนหน้าเว็บค้างที่ loading
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>