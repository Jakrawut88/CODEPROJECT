<?php
require_once 'config.php';

// ยังไม่ได้ล็อกอินก็เตะไปหน้า login ก่อน
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: user_login.php');
    exit;
}

$message_accommodation = '';
$message_district = '';

// เพิ่มเขตใหม่เข้าระบบ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_district') {
    $name_th = trim($_POST['name_th']);
    $name_en = trim($_POST['name_en']);

    if (!empty($name_th) && !empty($name_en)) {
        try {
            $sql = "INSERT INTO districts (name_th, name_en) VALUES (:name_th, :name_en)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['name_th' => $name_th, 'name_en' => $name_en]);
            $message_district = "<div class='alert alert-success'>บันทึกข้อมูลเขตใหม่เข้าสู่ระบบเรียบร้อยแล้ว</div>";
        } catch (PDOException $e) {
            $message_district = "<div class='alert alert-error'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
        }
    } else {
        $message_district = "<div class='alert alert-error'>กรุณากรอกข้อมูลเขตให้ครบถ้วน</div>";
    }
}

// เพิ่มที่พักใหม่ (มี district_id ผูกกับตารางเขต)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_accommodation') {
    $name = trim($_POST['name']);
    $district_id = $_POST['district_id'];
    $address = trim($_POST['address']);
    $price = intval($_POST['price']);
    $phone = trim($_POST['phone']);
    $description = trim($_POST['description']);
    $amenities = trim($_POST['amenities']);
    $map_url = trim($_POST['map_url']);
    $lat = trim($_POST['lat']);
    $lng = trim($_POST['lng']);

    if ($map_url !== '' && !filter_var($map_url, FILTER_VALIDATE_URL)) {
        $message_accommodation = "<div class='alert alert-error'>กรุณาใส่ลิงก์แผนที่ให้ถูกต้อง</div>";
    } elseif (!empty($name) && !empty($district_id) && !empty($phone) && !empty($lat) && !empty($lng)) {
        try {
            $sql = "INSERT INTO accommodations (name, district_id, address, price, phone, description, amenities, map_url, lat, lng) 
                    VALUES (:name, :district_id, :address, :price, :phone, :description, :amenities, :map_url, :lat, :lng)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $name, 'district_id' => $district_id, 
                'address' => $address, 'price' => $price, 
                'phone' => $phone, 'description' => $description, 'amenities' => $amenities, 'map_url' => $map_url ?: null,
                'lat' => $lat, 'lng' => $lng
            ]);
            $message_accommodation = "<div class='alert alert-success'>บันทึกข้อมูลที่พักใหม่เรียบร้อยแล้ว</div>";
        } catch (PDOException $e) {
            $message_accommodation = "<div class='alert alert-error'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
        }
    } else {
        $message_accommodation = "<div class='alert alert-error'>กรุณากรอกช่องข้อมูลที่จำเป็นให้ครบถ้วน</div>";
    }
}

// อนุมัติ/ปฏิเสธที่พักที่เจ้าของส่งเข้ามา
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'approve_accommodation') {
    $stmt = $pdo->prepare("UPDATE accommodations SET status = 'approved' WHERE id = :id");
    $stmt->execute(['id' => $_POST['accommodation_id']]);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reject_accommodation') {
    $stmt = $pdo->prepare("DELETE FROM accommodations WHERE id = :id AND status = 'pending'");
    $stmt->execute(['id' => $_POST['accommodation_id']]);
    header('Location: admin.php');
    exit;
}

// เอาลิสต์เขตมาใส่ dropdown
$districts = $pdo->query("SELECT * FROM districts ORDER BY name_th ASC")->fetchAll();

// ที่พักที่เจ้าของส่งเข้ามารออนุมัติ
$pending = $pdo->query("SELECT a.*, d.name_th as district_name, u.full_name as owner_name 
                         FROM accommodations a
                         LEFT JOIN districts d ON a.district_id = d.id
                         LEFT JOIN users u ON a.owner_id = u.id
                         WHERE a.status = 'pending' ORDER BY a.created_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลระบบ - BKK STAYPOINT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <svg class="icon-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>BKK STAYPOINT</span>
        </a>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="color: #64748B; font-size: 0.9rem;">สวัสดี, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="index.php" class="btn btn-secondary-outline">กลับไปหน้าค้นหา</a>
            <a href="logout.php" class="btn btn-secondary-outline">ออกจากระบบ</a>
        </div>
    </div>
</nav>

<main class="container" style="margin-top: 40px; margin-bottom: 60px;">
    
    <div class="section-header">
        <h2>แผงควบคุมหลังบ้านและการจัดการข้อมูล</h2>
        <p style="color: #64748B; margin-top: 4px;">เพิ่มข้อมูลพื้นที่เขต และอัปเดตรายชื่อที่พักใหม่เข้าสู่ระบบฐานข้อมูล</p>
        <div class="divider"></div>
    </div>

    <div class="admin-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 32px;">
        
        <section class="card" style="padding: 30px; height: fit-content;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: var(--color-neutral);">
                <svg style="width: 24px; height: 24px; color: var(--color-primary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></circle><circle cx="12" cy="10" r="3"></circle></svg>
                <h3 style="font-size: 1.3rem; font-weight: 700;">เพิ่มเขตพื้นที่ใหม่</h3>
            </div>
            
            <?php echo $message_district; ?>
            
            <form method="POST" action="admin.php" style="display: flex; flex-direction: column; gap: 18px;">
                <input type="hidden" name="action" value="add_district">
                
                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">ชื่อเขต (ภาษาไทย) *</label>
                    <input type="text" name="name_th" required placeholder="เช่น ดินแดง" class="form-control">
                </div>
                
                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">ชื่อเขต (ภาษาอังกฤษ) *</label>
                    <input type="text" name="name_en" required placeholder="เช่น Din Daeng" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">เพิ่มเขตพื้นที่เข้าสู่ระบบ</button>
            </form>
        </section>

        <section class="card" style="padding: 30px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: var(--color-neutral);">
                <svg style="width: 24px; height: 24px; color: var(--color-primary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                <h3 style="font-size: 1.3rem; font-weight: 700;">เพิ่มข้อมูลที่พักใหม่</h3>
            </div>
            
            <?php echo $message_accommodation; ?>
            
            <form method="POST" action="admin.php" style="display: flex; flex-direction: column; gap: 18px;">
                <input type="hidden" name="action" value="add_accommodation">
                
                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">ชื่อโรงแรม / ที่พัก *</label>
                    <input type="text" name="name" required placeholder="เช่น สยามบูทีค เรสซิเดนซ์" class="form-control">
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">เขตพื้นที่ตั้ง *</label>
                    <select name="district_id" required>
                        <option value="">เลือกเขตพื้นที่ที่ตั้ง...</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?php echo $d['id']; ?>">เขต<?php echo $d['name_th']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">ราคาค่าที่พักต่อคืน (บาท) *</label>
                    <input type="number" name="price" required placeholder="เช่น 1500" class="form-control">
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">ที่อยู่โดยละเอียด</label>
                    <textarea name="address" rows="3" placeholder="ระบุเลขที่ ถนน ซอย หรือจุดสังเกตสำคัญ" class="form-control" style="resize: none; font-family: inherit;"></textarea>
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">เบอร์โทรติดต่อ *</label>
                    <input type="tel" name="phone" required placeholder="เช่น 081-234-5678" class="form-control">
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">รายละเอียดที่พัก</label>
                    <textarea name="description" rows="4" placeholder="เช่น ห้องพักใกล้รถไฟฟ้า บรรยากาศเงียบสงบ มีพนักงานต้อนรับ 24 ชั่วโมง" class="form-control" style="resize: vertical; font-family: inherit;"></textarea>
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">สิ่งอำนวยความสะดวก</label>
                    <textarea name="amenities" rows="3" placeholder="เช่น Wi‑Fi ฟรี, เครื่องปรับอากาศ, ที่จอดรถ, เครื่องทำน้ำอุ่น" class="form-control" style="resize: vertical; font-family: inherit;"></textarea>
                </div>

                <div class="filter-group" style="width: 100%;">
                    <label style="margin-bottom: 6px;">ลิงก์ Google Maps</label>
                    <input type="url" name="map_url" placeholder="วางลิงก์จากปุ่ม แชร์ ใน Google Maps" class="form-control">
                    <small style="color: #64748B;">ผู้เข้าชมจะกดเปิดตำแหน่งนี้ใน Google Maps ได้</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="filter-group" style="width: 100%;">
                        <label style="margin-bottom: 6px;">ละติจูด (Latitude) *</label>
                        <input type="text" name="lat" required placeholder="เช่น 13.7441" class="form-control">
                    </div>
                    <div class="filter-group" style="width: 100%;">
                        <label style="margin-bottom: 6px;">ลองจิจูด (Longitude) *</label>
                        <input type="text" name="lng" required placeholder="เช่น 100.5284" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; background-color: var(--color-primary);">บันทึกข้อมูลที่พัก</button>
            </form>
        </section>

    </div>

    <?php if (!empty($pending)): ?>
    <div class="section-header">
        <h2>ที่พักรออนุมัติจากเจ้าของ (<?php echo count($pending); ?>)</h2>
        <div class="divider"></div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 40px;">
        <?php foreach ($pending as $item): 
            $imgs_stmt = $pdo->prepare("SELECT * FROM accommodation_images WHERE accommodation_id = :id ORDER BY sort_order ASC");
            $imgs_stmt->execute(['id' => $item['id']]);
            $imgs = $imgs_stmt->fetchAll();
        ?>
            <div class="card" style="padding: 20px 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h4 style="font-weight: 700; color: var(--color-neutral);"><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p style="color: #64748B; font-size: 0.9rem;">
                            เขต<?php echo htmlspecialchars($item['district_name'] ?? 'ไม่ระบุ'); ?> ·
                            ฿<?php echo number_format($item['price']); ?>/คืน ·
                            ส่งโดย: <?php echo htmlspecialchars($item['owner_name'] ?? 'ไม่ทราบ'); ?>
                        </p>
                        <p style="color: #94A3B8; font-size: 0.85rem; margin-top: 4px;"><?php echo htmlspecialchars($item['address']); ?></p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <form method="POST" action="admin.php">
                            <input type="hidden" name="action" value="approve_accommodation">
                            <input type="hidden" name="accommodation_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">อนุมัติ</button>
                        </form>
                        <form method="POST" action="admin.php" onsubmit="return confirm('ปฏิเสธและลบรายการนี้ทิ้งเลยใช่ไหม?');">
                            <input type="hidden" name="action" value="reject_accommodation">
                            <input type="hidden" name="accommodation_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-secondary" style="padding: 10px 18px;">ปฏิเสธ</button>
                        </form>
                    </div>
                </div>

                <div class="image-manager" id="imgmgr-<?php echo $item['id']; ?>">
                    <h4>รูปภาพ (<?php echo count($imgs); ?> รูป)</h4>
                    <div class="image-grid" id="thumbs-<?php echo $item['id']; ?>">
                        <?php foreach ($imgs as $img): ?>
                            <div class="image-thumb" id="thumb-<?php echo $img['id']; ?>">
                                <img src="uploads/<?php echo htmlspecialchars($img['filename']); ?>" alt="">
                                <button class="image-thumb-delete" onclick="deleteImage(<?php echo $img['id']; ?>)" title="ลบรูปนี้">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <label class="upload-area" for="upload-<?php echo $item['id']; ?>">
                        <input type="file" id="upload-<?php echo $item['id']; ?>" multiple accept="image/jpeg,image/png,image/webp"
                               onchange="uploadImages(this, <?php echo $item['id']; ?>)">
                        📷 คลิกหรือลากรูปมาวางที่นี่
                    </label>
                    <div id="upload-status-<?php echo $item['id']; ?>" style="margin-top:8px; font-size:0.85rem; color:#64748B;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<script>
    async function uploadImages(input, accommodationId) {
        const status = document.getElementById(`upload-status-${accommodationId}`);
        const thumbs = document.getElementById(`thumbs-${accommodationId}`);
        status.textContent = 'กำลังอัปโหลด...';

        const form = new FormData();
        form.append('accommodation_id', accommodationId);
        for (const file of input.files) form.append('images[]', file);

        const res = await fetch('upload_image.php', { method: 'POST', body: form });
        const data = await res.json();

        data.saved.forEach(img => {
            thumbs.insertAdjacentHTML('beforeend', `
                <div class="image-thumb" id="thumb-${img.id}">
                    <img src="uploads/${img.filename}" alt="">
                    <button class="image-thumb-delete" onclick="deleteImage(${img.id})" title="ลบรูปนี้">×</button>
                </div>`);
        });

        status.textContent = data.saved.length
            ? `อัปโหลดสำเร็จ ${data.saved.length} รูป` + (data.errors.length ? ` / ล้มเหลว: ${data.errors.join(', ')}` : '')
            : `ล้มเหลว: ${data.errors.join(', ')}`;
        input.value = '';
    }

    async function deleteImage(imageId) {
        if (!confirm('ลบรูปนี้ทิ้งเลยใช่ไหม?')) return;
        const form = new FormData();
        form.append('image_id', imageId);
        const res = await fetch('delete_image.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            document.getElementById(`thumb-${imageId}`)?.remove();
        } else {
            alert(data.error || 'ลบไม่สำเร็จ');
        }
    }
</script>
</body>
</html>