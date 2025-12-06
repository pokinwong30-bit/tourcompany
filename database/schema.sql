-- SQL schema
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(190) NOT NULL,
  role ENUM('director','manager','officer','non-admin') NOT NULL DEFAULT 'non-admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตัวอย่างข้อมูลเริ่มต้น (ถ้าต้องการตั้ง Director คนแรกแบบ manual)
-- ตั้งรหัสผ่านด้วย PHP: password_hash('yourpassword', PASSWORD_DEFAULT)
-- INSERT INTO users (email, password, full_name, role)
-- VALUES ('director@example.com', '$2y$10$xxxxxxxx...', 'Director Seed', 'director');

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ต้นทุนต่อโปรแกรมทัวร์ (รองรับ JSON payload จากฟอร์มคำนวณในหน้า tours_create.php)
CREATE TABLE IF NOT EXISTS tour_costs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tour_id INT NOT NULL UNIQUE,
  pax INT NOT NULL DEFAULT 0,
  profit_percent DECIMAL(10,2) NOT NULL DEFAULT 0,
  day_count INT NOT NULL DEFAULT 1,
  base_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  manual_profit DECIMAL(12,2) NOT NULL DEFAULT 0,
  percent_profit_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  selling_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  per_pax DECIMAL(12,2) NOT NULL DEFAULT 0,
  per_day DECIMAL(12,2) NOT NULL DEFAULT 0,
  payload JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
