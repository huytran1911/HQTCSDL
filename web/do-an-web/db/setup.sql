CREATE DATABASE IF NOT EXISTS laptop_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laptop_shop;


CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12, 2) NOT NULL,
    image VARCHAR(255),
    specs TEXT,
    stock INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);


CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'bank_transfer', 'momo') NOT NULL DEFAULT 'cash',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);



CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    review TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE payment_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'bank_transfer', 'momo') NOT NULL,
    card_number VARCHAR(255),
    card_holder VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);



INSERT INTO users (name, email, password, role) 
VALUES ('Admin', 'admin@example.com', '$2y$10$DV0oxrUbGFCsm5M4gDYAzuKrmD7z1QA6QKfKXAKgOiHDsD732yBJm', 'admin');


INSERT INTO categories (name, description) VALUES 
('Dell', 'Laptop Dell'),
('HP', 'Laptop HP'),
('Lenovo', 'Laptop Lenovo'),
('Apple', 'Laptop Apple');
INSERT INTO products (category_id, name, description, price, image, specs, stock) VALUES
(1, 'Dell XPS 13', 'Laptop cao cấp siêu mỏng nhẹ, hiệu năng mạnh mẽ', 25000000, 'dell_xps_13.jpg', '{"CPU":"Intel Core i5-1135G7","RAM":"8GB LPDDR4x","Ổ cứng":"256GB SSD NVMe","Màn hình":"13.4\" FHD+ (1920 x 1200)","Card đồ họa":"Intel Iris Xe Graphics","Pin":"52WHr","Trọng lượng":"1.2kg"}', 10),
(1, 'Dell Inspiron 15', 'Laptop đa năng phù hợp học tập và làm việc', 19000000, 'dell_inspiron_15.jpg', '{"CPU":"Intel Core i7-1165G7","RAM":"16GB DDR4","Ổ cứng":"512GB SSD NVMe","Màn hình":"15.6\" FHD (1920 x 1080)","Card đồ họa":"Intel Iris Xe Graphics","Pin":"41WHr","Trọng lượng":"1.7kg"}', 15),
(1, 'Dell G15', 'Laptop gaming mạnh mẽ', 29000000, 'dell-gaming-g15-5515-r7-5800h-8gb-512gb-4gb-rtx3050-120hz-600x600.jpg', '{"CPU":"Intel Core i7-12700H","RAM":"16GB DDR5","Ổ cứng":"512GB SSD NVMe","Màn hình":"15.6\" FHD 165Hz","Card đồ họa":"NVIDIA RTX 3060 6GB","Pin":"86WHr","Trọng lượng":"2.5kg"}', 8),
(2, 'HP Spectre', 'Laptop cao cấp thiết kế sang trọng', 30000000, 'hp_spectre.jpg', '{"CPU":"Intel Core i7-1165G7","RAM":"16GB LPDDR4x","Ổ cứng":"512GB SSD NVMe","Màn hình":"13.5\" 3K2K OLED Touch","Card đồ họa":"Intel Iris Xe Graphics","Pin":"66WHr","Trọng lượng":"1.3kg"}', 7),
(2, 'HP Pavilion 15', 'Laptop phổ thông hiệu năng tốt', 18000000, '46267_laptop_hp_pavilion_15_eg3092tu_8c5l3pa__anphatpc_34.jpg', '{"CPU":"AMD Ryzen 5 5600H","RAM":"8GB DDR4","Ổ cứng":"512GB SSD NVMe","Màn hình":"15.6\" FHD IPS","Card đồ họa":"AMD Radeon Graphics","Pin":"41WHr","Trọng lượng":"1.75kg"}', 12),
(2, 'HP Victus 15', 'Laptop gaming giá rẻ', 22000000, 'hp_victus_15.jpg', '{"CPU":"Intel Core i5-12500H","RAM":"16GB DDR4","Ổ cứng":"512GB SSD NVMe","Màn hình":"15.6\" FHD 144Hz","Card đồ họa":"NVIDIA RTX 3050 4GB","Pin":"70WHr","Trọng lượng":"2.29kg"}', 9),
(3, 'Lenovo Legion 5 Pro', 'Laptop gaming cao cấp màn hình 16"', 35000000, 'lenovo_legion_5_pro.jpg', '{"CPU":"AMD Ryzen 7 6800H","RAM":"16GB DDR5","Ổ cứng":"1TB SSD NVMe","Màn hình":"16\" WQXGA 165Hz","Card đồ họa":"NVIDIA RTX 3070 8GB","Pin":"80WHr","Trọng lượng":"2.49kg"}', 6),
(3, 'Lenovo IdeaPad 5', 'Laptop văn phòng thiết kế đẹp', 17000000, 'lenovo_ideapad_5.jpg', '{"CPU":"AMD Ryzen 5 5600H","RAM":"8GB DDR4","Ổ cứng":"512GB SSD NVMe","Màn hình":"15.6\" FHD IPS","Card đồ họa":"AMD Radeon Graphics","Pin":"57WHr","Trọng lượng":"1.66kg"}', 14),
(3, 'Lenovo LOQ', 'Laptop gaming tầm trung', 21000000, 'lenovo_loq.jpg', '{"CPU":"Intel Core i5-12450H","RAM":"16GB DDR4","Ổ cứng":"512GB SSD NVMe","Màn hình":"15.6\" FHD 144Hz","Card đồ họa":"NVIDIA RTX 3050 6GB","Pin":"60WHr","Trọng lượng":"2.4kg"}', 10),
(4, 'Apple MacBook Air M2', 'Laptop mỏng nhẹ hiệu năng cao', 28000000, 'apple_macbook_air_m2.jpg', '{"CPU":"Apple M2 8-core","RAM":"8GB Unified Memory","Ổ cứng":"256GB SSD","Màn hình":"13.6\" Liquid Retina","Card đồ họa":"Apple M2 8-core GPU","Pin":"52.6WHr","Trọng lượng":"1.24kg"}', 8),
(4, 'Apple MacBook Air M1', 'Laptop phổ thông hiệu năng tốt', 22000000, 'apple_macbook_air_m1.jpg', '{"CPU":"Apple M1 8-core","RAM":"8GB Unified Memory","Ổ cứng":"256GB SSD","Màn hình":"13.3\" Retina","Card đồ họa":"Apple M1 7-core GPU","Pin":"49.9WHr","Trọng lượng":"1.29kg"}', 11),
(4, 'Apple MacBook Pro', 'Laptop chuyên nghiệp cho công việc', 45000000, 'apple_macbook_pro.jpg', '{"CPU":"Apple M2 Pro 10-core","RAM":"16GB Unified Memory","Ổ cứng":"512GB SSD","Màn hình":"14.2\" Liquid Retina XDR","Card đồ họa":"Apple M2 Pro 16-core GPU","Pin":"70WHr","Trọng lượng":"1.6kg"}', 5); 



