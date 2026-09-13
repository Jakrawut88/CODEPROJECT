CREATE DATABASE IF NOT EXISTS bangkok_stay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bangkok_stay;

-- ตารางเขตในกรุงเทพฯ
CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางสมาชิกเว็บ (ทั้งผู้ใช้ทั่วไป และเจ้าของที่พัก)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','owner') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางที่พัก ผูกกับเขตด้วย district_id
CREATE TABLE IF NOT EXISTS accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    district_id INT,
    address TEXT,
    price INT DEFAULT 0,
    phone VARCHAR(30) DEFAULT NULL,
    description TEXT,
    amenities TEXT,
    map_url VARCHAR(2048) DEFAULT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    owner_id INT DEFAULT NULL,             -- ใครเป็นคนเพิ่ม ถ้าแอดมินเพิ่มเองจะเป็น NULL
    status ENUM('pending','approved') NOT NULL DEFAULT 'approved', -- ที่พักที่เจ้าของเพิ่มเองต้องรออนุมัติก่อน
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางแอดมิน สำหรับ login เข้าหลังบ้าน
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางรูปภาพของที่พัก (หลายรูปต่อที่พักได้)
CREATE TABLE IF NOT EXISTS accommodation_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accommodation_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seed เขตตัวอย่าง
INSERT INTO districts (name_th) VALUES 
('ปทุมวัน'), ('วัฒนา'), ('จตุจักร'), ('บางรัก'), ('ห้วยขวาง'), 
('พญาไท'), ('ราชเทวี'), ('คลองเตย'), ('บางนา'), ('พระนคร');

-- seed ที่พักตัวอย่าง
INSERT INTO accommodations (name, district_id, address, price, lat, lng) VALUES 
('Siam Cozy Room', 1, 'ซอยจุฬา 12 แขวงวังใหม่ เขตปทุมวัน กรุงเทพฯ', 1500, 13.7441, 100.5284),
('Sukhumvit Modern Condo', 2, 'สุขุมวิท 21 แขวงคลองเตยเหนือ เขตวัฒนา กรุงเทพฯ', 2200, 13.7402, 100.5604),
('Ladprao Eco Stay', 3, 'ใกล้ MRT จตุจักร แขวงจตุจักร เขตจตุจักร กรุงเทพฯ', 850, 13.8052, 100.5548),
('Silom Boutique Hotel', 4, 'ถนนสีลม แขวงสุริยวงศ์ เขตบางรัก กรุงเทพฯ', 1800, 13.7285, 100.5312);