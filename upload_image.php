<?php
require_once 'config.php';
header('Content-Type: application/json');

// เช็คสิทธิ์ก่อน — ต้องเป็น admin หรือ owner ที่ล็อกอินอยู่
$is_admin = !empty($_SESSION['admin_logged_in']);
$is_owner = !empty($_SESSION['user_id']) && $_SESSION['user_role'] === 'owner';

if (!$is_admin && !$is_owner) {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์อัปโหลดรูป']);
    exit;
}

$accommodation_id = intval($_POST['accommodation_id'] ?? 0);
if (!$accommodation_id) {
    echo json_encode(['error' => 'ไม่ได้ระบุ accommodation_id']);
    exit;
}

// ถ้าเป็น owner ต้องเช็คว่าที่พักนี้เป็นของตัวเองจริงๆ
if ($is_owner && !$is_admin) {
    $chk = $pdo->prepare("SELECT id FROM accommodations WHERE id = :id AND owner_id = :owner_id");
    $chk->execute(['id' => $accommodation_id, 'owner_id' => $_SESSION['user_id']]);
    if (!$chk->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'ที่พักนี้ไม่ใช่ของคุณ']);
        exit;
    }
}

if (empty($_FILES['images'])) {
    echo json_encode(['error' => 'ไม่พบไฟล์ที่อัปโหลด']);
    exit;
}

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB ต่อรูป

// หา sort_order ปัจจุบันของที่พักนี้ เพื่อต่อลำดับต่อได้
$sort_stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM accommodation_images WHERE accommodation_id = :id");
$sort_stmt->execute(['id' => $accommodation_id]);
$next_order = intval($sort_stmt->fetchColumn());

// resize รูปให้กว้างไม่เกิน 1200px แล้วบันทึกเป็น JPEG quality 82
// ลดจาก 5-10MB เหลือแค่ 200-400KB ทำให้อัปโหลดเร็วขึ้นมาก
function resizeAndSave(string $src, string $mime, string $dest): bool {
    $orig = match($mime) {
        'image/jpeg' => imagecreatefromjpeg($src),
        'image/png'  => imagecreatefrompng($src),
        'image/webp' => imagecreatefromwebp($src),
        default      => false,
    };
    if (!$orig) return false;

    $ow = imagesx($orig);
    $oh = imagesy($orig);
    $max = 1200;

    if ($ow <= $max && $oh <= $max) {
        // รูปเล็กกว่า 1200px อยู่แล้ว ไม่ต้อง resize แค่ compress
        $nw = $ow;
        $nh = $oh;
    } elseif ($ow >= $oh) {
        $nw = $max;
        $nh = intval($oh * $max / $ow);
    } else {
        $nh = $max;
        $nw = intval($ow * $max / $oh);
    }

    $canvas = imagecreatetruecolor($nw, $nh);

    // รักษา alpha channel สำหรับ PNG ก่อน resize
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);

    imagecopyresampled($canvas, $orig, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
    $ok = imagejpeg($canvas, $dest, 82); // quality 82 — คมชัด แต่ไฟล์เล็ก

    imagedestroy($orig);
    imagedestroy($canvas);
    return $ok;
}

$saved = [];
$errors = [];

// รองรับทั้ง input เดี่ยวและ multiple — normalize ให้เป็น array เดียวกัน
$files = $_FILES['images'];
$count = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $count; $i++) {
    $name    = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
    $type    = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];
    $tmp     = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $size    = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];
    $err     = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];

    if ($err !== UPLOAD_ERR_OK) {
        $errors[] = "$name: upload error ($err)";
        continue;
    }
    if ($size > $max_size) {
        $errors[] = "$name: ไฟล์ใหญ่เกิน 5MB";
        continue;
    }

    // ตรวจ MIME จากไฟล์จริง ไม่ใช่จาก browser (ป้องกัน bypass ง่ายๆ)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_type = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    if (!in_array($real_type, $allowed_types)) {
        $errors[] = "$name: รองรับแค่ JPG, PNG, WebP";
        continue;
    }

    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$real_type];
    $filename = uniqid('img_', true) . '.jpg'; // บันทึกเป็น jpg เสมอ หลัง resize
    $dest = $upload_dir . $filename;

    // resize + compress ด้วย GD ก่อนบันทึก ลดขนาดจาก 5MB+ เหลือแค่ 200-400KB
    if (!resizeAndSave($tmp, $real_type, $dest)) {
        $errors[] = "$name: บันทึกไฟล์ไม่สำเร็จ";
        continue;
    }

    $stmt = $pdo->prepare("INSERT INTO accommodation_images (accommodation_id, filename, sort_order) VALUES (:aid, :fn, :so)");
    $stmt->execute(['aid' => $accommodation_id, 'fn' => $filename, 'so' => $next_order]);
    $next_order++;

    $saved[] = ['id' => $pdo->lastInsertId(), 'filename' => $filename];
}

echo json_encode(['saved' => $saved, 'errors' => $errors]);