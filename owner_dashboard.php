<?php
require_once 'config.php';

if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'owner') {
    header('Location: user_login.php');
    exit;
}

$message = '';

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
        $message = "<div class='alert alert-error'>กรุณาใส่ลิงก์แผนที่ให้ถูกต้อง</div>";
    } elseif (!empty($name) && !empty($district_id) && !empty($phone) && !empty($lat) && !empty($lng)) {
        $sql = "INSERT INTO accommodations (name, district_id, address, price, phone, description, amenities, map_url, lat, lng, owner_id, status) 
                VALUES (:name, :district_id, :address, :price, :phone, :description, :amenities, :map_url, :lat, :lng, :owner_id, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $name, 'district_id' => $district_id,
            'address' => $address, 'price' => $price,
            'phone' => $phone, 'description' => $description, 'amenities' => $amenities, 'map_url' => $map_url ?: null,
            'lat' => $lat, 'lng' => $lng, 'owner_id' => $_SESSION['user_id']
        ]);
        $message = "<div class='alert alert-success'>ส่งที่พักเข้าระบบแล้ว รอแอดมินตรวจสอบและอนุมัติก่อนจะขึ้นหน้าเว็บ</div>";
    } else {
        $message = "<div class='alert alert-error'>กรุณากรอกช่องข้อมูลที่จำเป็นให้ครบถ้วน</div>";
    }
}

$districts = $pdo->query("SELECT * FROM districts ORDER BY name_th ASC")->fetchAll();

$stmt = $pdo->prepare("SELECT a.*, d.name_th as district_name FROM accommodations a 
                        LEFT JOIN districts d ON a.district_id = d.id
                        WHERE a.owner_id = :owner_id ORDER BY a.id DESC");
$stmt->execute(['owner_id' => $_SESSION['user_id']]);
$my_listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดเจ้าของที่พัก - BKK STAYPOINT</title>
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
            <span style="color: #64748B; font-size: 0.9rem;">เจ้าของที่พัก: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="index.php" class="btn btn-secondary-outline">กลับไปหน้าค้นหา</a>
            <a href="logout.php" class="btn btn-secondary-outline">ออกจากระบบ</a>
        </div>
    </div>
</nav>

<main class="container" style="margin-top: 40px; margin-bottom: 60px;">

    <div class="section-header">
        <h2>แดชบอร์ดเจ้าของที่พัก</h2>
        <p style="color: #64748B; margin-top: 4px;">เพิ่มที่พักของคุณเข้าระบบ — ที่พักที่เพิ่มใหม่ต้องรอแอดมินอนุมัติก่อนจึงจะแสดงบนหน้าเว็บ</p>
        <div class="divider"></div>
    </div>

    <div class="admin-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 32px;">

        <section class="card" style="padding: 30px; height: fit-content;">
            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 20px;">เพิ่มที่พักของคุณ</h3>

            <?php echo $message; ?>

            <form method="POST" action="owner_dashboard.php" style="display: flex; flex-direction: column; gap: 18px;">
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

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">ส่งเพื่อรออนุมัติ</button>
            </form>
        </section>

        <section class="card" style="padding: 30px;">
            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 20px;">ที่พักของฉัน</h3>
            <?php if (empty($my_listings)): ?>
                <p style="color: #64748B;">ยังไม่มีที่พักในระบบ ลองเพิ่มรายการแรกของคุณดูสิ</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($my_listings as $item): 
                        $imgs_stmt = $pdo->prepare("SELECT * FROM accommodation_images WHERE accommodation_id = :id ORDER BY sort_order ASC");
                        $imgs_stmt->execute(['id' => $item['id']]);
                        $imgs = $imgs_stmt->fetchAll();
                    ?>
                        <div style="border: 1px solid var(--color-border); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px;">
                                <div>
                                    <h4 style="font-weight: 600; color: var(--color-neutral);"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p style="color: #64748B; font-size: 0.9rem;">เขต<?php echo htmlspecialchars($item['district_name'] ?? 'ไม่ระบุ'); ?> · ฿<?php echo number_format($item['price']); ?>/คืน</p>
                                </div>
                                <?php if ($item['status'] === 'approved'): ?>
                                    <span class="badge" style="background-color: #DCFCE7; color: #15803D; white-space: nowrap;">อนุมัติแล้ว</span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #FEF3C7; color: #B45309; white-space: nowrap;">รออนุมัติ</span>
                                <?php endif; ?>
                            </div>

                            <div class="image-manager" id="imgmgr-<?php echo $item['id']; ?>">
                                <h4>รูปภาพ (<?php echo count($imgs); ?> รูป)</h4>
                                <div class="image-grid" id="thumbs-<?php echo $item['id']; ?>">
                                    <?php foreach ($imgs as $img): ?>
                                        <div class="image-thumb" id="thumb-<?php echo $img['id']; ?>">
                                            <img src="uploads/<?php echo htmlspecialchars($img['filename']); ?>" alt="">
                                            <button class="image-thumb-delete" onclick="deleteImage(<?php echo $img['id']; ?>, <?php echo $item['id']; ?>)" title="ลบรูปนี้">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <label class="upload-area" for="upload-<?php echo $item['id']; ?>">
                                    <input type="file" id="upload-<?php echo $item['id']; ?>" multiple accept="image/jpeg,image/png,image/webp"
                                           onchange="uploadImages(this, <?php echo $item['id']; ?>)">
                                    📷 คลิกหรือลากรูปมาวางที่นี่ (JPG, PNG, WebP, ไม่เกิน 5MB/รูป)
                                </label>
                                <div id="upload-status-<?php echo $item['id']; ?>" style="margin-top:8px; font-size:0.85rem; color:#64748B;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
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
                    <button class="image-thumb-delete" onclick="deleteImage(${img.id}, ${accommodationId})" title="ลบรูปนี้">×</button>
                </div>`);
        });

        status.textContent = data.saved.length
            ? `อัปโหลดสำเร็จ ${data.saved.length} รูป` + (data.errors.length ? ` / ล้มเหลว: ${data.errors.join(', ')}` : '')
            : `ล้มเหลว: ${data.errors.join(', ')}`;
        input.value = '';
    }

    async function deleteImage(imageId, accommodationId) {
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