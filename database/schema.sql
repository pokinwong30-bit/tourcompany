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
