-- sql/dragonstone_schema_and_seed.sql
-- Drops and creates database for a fresh start. WARNING: will delete existing dragonstone DB.
DROP DATABASE IF EXISTS dragonstone;
CREATE DATABASE dragonstone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dragonstone;

-- =========================================
-- 1. ROLES
-- =========================================
CREATE TABLE roles (
                       role_id INT AUTO_INCREMENT PRIMARY KEY,
                       role_key VARCHAR(32) UNIQUE NOT NULL,
                       name VARCHAR(64) NOT NULL
) ENGINE=InnoDB;

INSERT INTO roles (role_key, name) VALUES
                                       ('admin','Admin'),
                                       ('staff','Staff'),
                                       ('customer','Customer');

-- =========================================
-- 2. USERS
-- =========================================
CREATE TABLE users (
                       user_id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(150) NOT NULL,
                       email VARCHAR(255) NOT NULL UNIQUE,
                       password_hash VARCHAR(255) NOT NULL,
                       role_key VARCHAR(32) NOT NULL DEFAULT 'customer',
                       eco_points INT DEFAULT 0,
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                       last_login DATETIME DEFAULT NULL,
                       FOREIGN KEY (role_key) REFERENCES roles(role_key)
) ENGINE=InnoDB;

-- Placeholder admin (password must be reset via create_admin.php or update_users_password.php)
INSERT INTO users (name, email, password_hash, role_key, eco_points) VALUES
    ('Aegon T.','admin@dragonstone.local','REPLACE_WITH_HASH_BY_RUNNING_src/create_admin.php','admin',1000);

-- =========================================
-- 3. CATEGORIES
-- =========================================
CREATE TABLE categories (
                            category_id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            slug VARCHAR(255) NOT NULL UNIQUE,
                            tile_image VARCHAR(255),
                            description TEXT,
                            parent_id INT DEFAULT NULL,
                            FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================
-- 4. PRODUCTS
-- =========================================
CREATE TABLE products (
                          product_id INT AUTO_INCREMENT PRIMARY KEY,
                          sku VARCHAR(64) UNIQUE,
                          name VARCHAR(255) NOT NULL,
                          description TEXT,
                          category_id INT,
                          price DECIMAL(10,2) NOT NULL,
                          currency CHAR(3) DEFAULT 'ZAR',
                          stock INT DEFAULT 0,
                          image_filename VARCHAR(255) DEFAULT NULL,
                          points_on_purchase INT DEFAULT 0,
                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                          carbon_score DECIMAL(6,2) DEFAULT 0.00,
                          FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================
-- 5. PRODUCT FOOTPRINT
-- =========================================
CREATE TABLE product_footprint (
                                   id INT AUTO_INCREMENT PRIMARY KEY,
                                   product_id INT UNIQUE NOT NULL,
                                   kg_co2e_per_unit DECIMAL(10,4) DEFAULT 0,
                                   shipping_kg_co2e_estimate DECIMAL(10,4) DEFAULT 0,
                                   breakdown_json JSON DEFAULT NULL,
                                   FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- 6. CARTS AND CART ITEMS
-- =========================================
CREATE TABLE carts (
                       cart_id INT AUTO_INCREMENT PRIMARY KEY,
                       user_id INT NOT NULL,
                       updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                       FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            cart_id INT NOT NULL,
                            product_id INT NOT NULL,
                            quantity INT DEFAULT 1,
                            FOREIGN KEY (cart_id) REFERENCES carts(cart_id) ON DELETE CASCADE,
                            FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- =========================================
-- 7. REWARDS & REDEMPTIONS (moved up for FK order)
-- =========================================
CREATE TABLE rewards (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         name VARCHAR(100) NOT NULL,
                         description TEXT,
                         points_required INT NOT NULL,
                         reward_type ENUM('discount', 'voucher', 'gift') DEFAULT 'discount',
                         value DECIMAL(6,2) DEFAULT 0.00,
                         image VARCHAR(255),
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE redemptions (
                             id INT AUTO_INCREMENT PRIMARY KEY,
                             user_id INT NOT NULL,
                             reward_id INT NOT NULL,
                             redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                             status ENUM('pending','approved','rejected') DEFAULT 'pending',
                             FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
                             FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- 8. ORDERS (now safely references rewards)
-- =========================================
CREATE TABLE orders (
                        order_id INT AUTO_INCREMENT PRIMARY KEY,
                        order_number VARCHAR(64) UNIQUE NOT NULL,
                        user_id INT NOT NULL,
                        total_amount DECIMAL(12,2),
                        currency CHAR(3) DEFAULT 'ZAR',
                        status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
                        items_snapshot JSON,
                        payment_provider VARCHAR(32),
                        payment_provider_id VARCHAR(255),
                        tracking_number VARCHAR(100) NULL,
                        discount_applied DECIMAL(6,2) DEFAULT 0.00,
                        eco_reward_used INT DEFAULT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(user_id),
                        FOREIGN KEY (eco_reward_used) REFERENCES rewards(id)
) ENGINE=InnoDB;

-- =========================================
-- 9. ECOPOINTS LEDGER
-- =========================================
CREATE TABLE ecopoints_ledger (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  user_id INT NOT NULL,
                                  points_change INT NOT NULL,
                                  reason VARCHAR(255),
                                  related_order_id INT DEFAULT NULL,
                                  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY (user_id) REFERENCES users(user_id),
                                  FOREIGN KEY (related_order_id) REFERENCES orders(order_id)
) ENGINE=InnoDB;

-- =========================================
-- 10. COMMUNITY TABLES
-- =========================================
CREATE TABLE community_posts (
                                 post_id INT AUTO_INCREMENT PRIMARY KEY,
                                 user_id INT NOT NULL,
                                 title VARCHAR(255) NOT NULL,
                                 body TEXT NOT NULL,
                                 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                 points_awarded INT DEFAULT 0,
                                 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE community_comments (
                                    comment_id INT AUTO_INCREMENT PRIMARY KEY,
                                    post_id INT NOT NULL,
                                    user_id INT NOT NULL,
                                    body TEXT,
                                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
                                    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE community_likes (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 post_id INT NOT NULL,
                                 user_id INT NOT NULL,
                                 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                 UNIQUE KEY ux_post_user (post_id, user_id),
                                 FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
                                 FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE moderation_flags (
                                  flag_id INT AUTO_INCREMENT PRIMARY KEY,
                                  item_type VARCHAR(32),
                                  item_id INT,
                                  flagged_by INT,
                                  reason TEXT,
                                  status VARCHAR(32) DEFAULT 'pending',
                                  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY (flagged_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =========================================
-- 11. SAMPLE DATA
-- =========================================
INSERT INTO categories (name, slug, tile_image, description) VALUES
                                                                 ('Cleaning & Household Supplies','cleaning','cleaning-tile.jpg','Compostable cleaning pods, refillable bottles, and natural household items.'),
                                                                 ('Kitchen & Dining','kitchen','kitchen-tile.jpg','Sustainable kitchenware and dining essentials.'),
                                                                 ('Home Décor & Living','home','home-tile.jpg','Eco-friendly decor, candles, and upcycled pieces.'),
                                                                 ('Bathroom & Personal Care','bathroom','bathroom-tile.jpg','Refillable and plastic-free personal care.'),
                                                                 ('Lifestyle & Wellness','lifestyle','lifestyle-tile.jpg','Reusable bottles, yoga mats and wellbeing kits.'),
                                                                 ('Kids & Pets','kids','kids-tile.jpg','FSC wood toys, organic baby items, and pet care.'),
                                                                 ('Outdoor & Garden','garden','garden-tile.jpg','Compost bins, seed kits, and solar garden lights.');

INSERT INTO products (sku, name, description, category_id, price, currency, stock, image_filename, points_on_purchase) VALUES
-- Bathroom & Personal Care (category_id = 4)
('BATH-001','Bamboo Toothbrushes and Holders','Eco-friendly bamboo toothbrushes with biodegradable holders.',4,59.99,'ZAR',100,'Bamboo toothbrushes and holders.jpg',10),
('BATH-002','Compostable Floss and Packaging','Natural dental floss made from corn fiber with compostable packaging.',4,49.50,'ZAR',80,'Compostable floss and packaging.jpg',8),
('BATH-003','Natural Loofahs and Exfoliating Mitts','Plastic-free loofahs and mitts for gentle exfoliation.',4,69.99,'ZAR',70,'Natural loofahs and exfoliating mitts.jpg',10),
('BATH-004','Plastic-Free Deodorant and Skincare Products','Natural, plastic-free deodorants and skincare essentials.',4,119.00,'ZAR',60,'Plastic-free deodorant and skincare products.jpg',12),
('BATH-005','Refillable Shampoo and Conditioner Bottles','Glass and aluminum refillable bottles for sustainable hair care.',4,99.00,'ZAR',90,'Refillable shampoo and conditioner bottles.jpg',10),

-- Cleaning & Household Supplies (category_id = 1)
('CLEAN-001','Biodegradable Trash Bags','Durable trash bags made from compostable plant starch.',1,89.00,'ZAR',120,'Biodegradable trash bags.jpg',10),
('CLEAN-002','Compostable Cleaning Pods (Multi-Surface)','Plant-based compostable pods for multi-surface cleaning. 30-pack.',1,99.99,'ZAR',50,'Compostable cleaning pods (multi-surface).jpg',15),
('CLEAN-003','Compostable Dish Sponges','Natural cellulose-based dish sponges, 3-pack.',1,59.99,'ZAR',100,'Compostable dish sponges.jpg',8),
('CLEAN-004','Plant-Based Laundry Detergent Sheets','Concentrated laundry sheets with zero plastic packaging.',1,109.00,'ZAR',60,'Plant-based laundry detergent sheets.jpg',12),
('CLEAN-005','Refillable Glass Spray Bottles','Tempered glass spray bottle with sustainable trigger.',1,89.99,'ZAR',120,'Refillable glass spray bottles.jpg',8),
('CLEAN-006','Wool Dryer Balls','Reusable dryer balls to soften laundry without chemicals.',1,79.00,'ZAR',40,'Wool dryer balls.jpg',10),

-- Home Décor & Living (category_id = 3)
('HOME-001','Eco-Friendly Paint and Finishes','Low-VOC, non-toxic paints and sustainable finishes.',3,199.00,'ZAR',40,'Eco-friendly paint and finishes(sustainable).jpg',20),
('HOME-002','Natural Air Purifying Charcoal Bags','Bamboo charcoal bags to purify indoor air naturally.',3,89.00,'ZAR',100,'Natural air purifying charcoal bags.jpg',10),
('HOME-003','Recycled Glass Vases and Candle Holders','Elegant home décor crafted from 100% recycled glass.',3,149.00,'ZAR',50,'Recycled glass vases and candle holders.jpg',15),
('HOME-004','Soy Wax Candles with Natural Essential Oils','Hand-poured soy candles with relaxing scents.',3,129.00,'ZAR',70,'Soy wax candles with natural essential oils.jpg',12),
('HOME-005','Upcycled Wood Wall Art and Shelving','Beautiful wooden décor made from reclaimed timber.',3,299.00,'ZAR',30,'Upcycled wood wall art and shelving.jpg',25),

-- Kids & Pets (category_id = 6)
('KIDS-001','Eco-Friendly Pet Biodegradable Waste Bags','Compostable waste bags for responsible pet owners.',6,59.00,'ZAR',150,'Eco-friendly pet biodegradable waste bags.jpg',8),
('KIDS-002','Natural Pet Grooming Foam','Plant-based grooming foam safe for pets and the planet.',6,109.00,'ZAR',70,'Natural pet grooming foam.jpg',12),
('KIDS-003','Organic Cotton Baby Clothes and Blankets','Soft, breathable organic cotton essentials for babies.',6,249.00,'ZAR',40,'Organic cotton baby clothes and blankets.jpg',20),
('KIDS-004','Reusable Cloth Diapers','Washable, eco-friendly cloth diapers with adjustable sizes.',6,199.00,'ZAR',60,'Reusable cloth diapers.jpg',15),
('KIDS-005','Wooden Toys made from FSC-Certified Wood','Educational wooden toys crafted from responsibly sourced wood.',6,179.00,'ZAR',50,'Wooden toys made from FSC-certified wood.jpg',15),

-- Kitchen & Dining (category_id = 2)
('KITCH-001','Bamboo Kitchen Utensils and Cutting Boards','Sustainably harvested bamboo utensils and boards.',2,149.50,'ZAR',80,'Bamboo kitchen utensils and cutting boards.jpg',15),
('KITCH-002','Beeswax Food Wraps','Reusable beeswax wraps to replace cling film.',2,129.00,'ZAR',60,'Beeswax food wraps.jpg',12),
('KITCH-003','Recycled Glass Storage Jars','Stylish and airtight recycled glass jars for food storage.',2,99.00,'ZAR',100,'Recycled glass storage jars.jpg',10),
('KITCH-004','Reusable Silicone Food Storage Bags','BPA-free, leak-proof silicone bags for snacks and meals.',2,149.00,'ZAR',70,'Reusable silicone food storage bags.jpg',12),
('KITCH-005','Stainless Steel Straws and Straw Cleaners','Durable stainless steel straws with cleaning brushes.',2,79.00,'ZAR',120,'Stainless steel straws and straw cleaners.jpg',8),

-- Lifestyle & Wellness (category_id = 5)
('LIFE-001','Eco-Friendly Yoga Mats and Accessories','Biodegradable yoga mats made from natural rubber.',5,299.00,'ZAR',50,'Eco-friendly yoga mats and accessories.jpg',25),
('LIFE-002','Mindfulness Kit with Sustainably Sourced Incense','Mindfulness bundle including incense, journal, and guide.',5,249.00,'ZAR',40,'Mindfulness kit with sustainably sourced incense.jpg',20),
('LIFE-003','Organic Cotton Bathrobes','Soft, organic cotton bathrobes for ultimate comfort.',5,399.00,'ZAR',30,'Organic cotton bathrobes.jpg',30),
('LIFE-004','Organic Cotton Throw Blankets and Cushion Covers','Luxurious cotton throws and cushion covers.',5,349.00,'ZAR',40,'Organic cotton throw blankets and cushion covers.jpg',25),
('LIFE-005','Organic Cotton Towels','High-quality, absorbent organic cotton towels.',5,279.00,'ZAR',60,'Organic cotton towels.jpg',20),
('LIFE-006','Reusable Water Bottles (Recycled Materials)','Durable bottles made from 100% recycled stainless steel.',5,199.00,'ZAR',100,'Reusable water bottles(recycled materials).jpg',15),
('LIFE-007','Sustainable Journals','Eco-friendly notebooks made from recycled paper.',5,99.00,'ZAR',150,'Sustainable journals.jpg',8),

-- Outdoor & Garden (category_id = 7)
('GARD-001','Compost Bins and Worm Farms','Durable compost bins and home-friendly worm farms.',7,349.00,'ZAR',40,'Compost bins and worm farms.jpg',25),
('GARD-002','Indoor Plants with Biodegradable Pots','Eco pots with thriving indoor plants for air purification.',7,179.00,'ZAR',70,'Indoor plants with biodegradable pots.jpg',15),
('GARD-003','Organic Fertilizers and Pest Repellents','Natural, chemical-free fertilizers and repellents.',7,99.00,'ZAR',80,'Organic fertilizers and pest repellents.jpg',10),
('GARD-004','Recycled Plastic Planters','Durable outdoor planters made from recycled plastics.',7,149.00,'ZAR',60,'Recycled plastic planters.jpg',12),
('GARD-005','Rainwater Harvesting Kits','Compact kits for home rainwater collection and reuse.',7,499.00,'ZAR',20,'Rainwater harvesting kits.jpg',35),
('GARD-006','Seed Starter Kits with Heirloom Seeds','Heirloom vegetable seed kits for beginner gardeners.',7,129.00,'ZAR',90,'Seed starter kits with heirloom seeds.jpg',10),
('GARD-007','Solar-Powered Garden Lights','Energy-saving lights for pathways and gardens.',7,199.00,'ZAR',100,'Solar-powered garden lights.jpg',15),
('GARD-008','Solar-Powered Lanterns and Chargers','Portable solar lanterns with phone charging capability.',7,249.00,'ZAR',50,'Solar-powered lanterns and chargers.jpg',20);

INSERT INTO product_footprint (product_id, kg_co2e_per_unit, shipping_kg_co2e_estimate, breakdown_json) VALUES
-- Bathroom & Personal Care
((SELECT product_id FROM products WHERE sku='BATH-001'), 1.2000, 0.1000, JSON_OBJECT('bamboo_processing',0.7,'packaging',0.3,'transport',0.2)),
((SELECT product_id FROM products WHERE sku='BATH-002'), 0.9500, 0.0800, JSON_OBJECT('floss_material',0.5,'packaging',0.25,'distribution',0.28)),
((SELECT product_id FROM products WHERE sku='BATH-003'), 0.8000, 0.0700, JSON_OBJECT('natural_loofah',0.4,'drying',0.2,'packaging',0.27)),
((SELECT product_id FROM products WHERE sku='BATH-004'), 1.6000, 0.1200, JSON_OBJECT('formulation',1.0,'packaging',0.4,'transport',0.32)),
((SELECT product_id FROM products WHERE sku='BATH-005'), 1.1000, 0.0900, JSON_OBJECT('glass_bottle',0.7,'refill_system',0.25,'transport',0.24)),

-- Cleaning & Household Supplies
((SELECT product_id FROM products WHERE sku='CLEAN-001'), 0.8500, 0.0600, JSON_OBJECT('bioplastic',0.45,'packaging',0.2,'distribution',0.26)),
((SELECT product_id FROM products WHERE sku='CLEAN-002'), 0.9000, 0.0700, JSON_OBJECT('plant_extract',0.55,'packaging',0.25,'transport',0.27)),
((SELECT product_id FROM products WHERE sku='CLEAN-003'), 0.7000, 0.0500, JSON_OBJECT('cellulose',0.35,'packaging',0.2,'shipping',0.20)),
((SELECT product_id FROM products WHERE sku='CLEAN-004'), 1.0000, 0.0800, JSON_OBJECT('production',0.6,'packaging',0.25,'transport',0.23)),
((SELECT product_id FROM products WHERE sku='CLEAN-005'), 1.2000, 0.0900, JSON_OBJECT('glass_production',0.7,'trigger_mechanism',0.3,'distribution',0.29)),
((SELECT product_id FROM products WHERE sku='CLEAN-006'), 0.9500, 0.0600, JSON_OBJECT('wool_processing',0.5,'packaging',0.25,'transport',0.26)),

-- Home Décor & Living
((SELECT product_id FROM products WHERE sku='HOME-001'), 2.8000, 0.2000, JSON_OBJECT('paint_production',1.8,'packaging',0.6,'distribution',0.6)),
((SELECT product_id FROM products WHERE sku='HOME-002'), 1.7000, 0.1200, JSON_OBJECT('charcoal_activation',1.0,'bag_production',0.3,'shipping',0.52)),
((SELECT product_id FROM products WHERE sku='HOME-003'), 2.3000, 0.1800, JSON_OBJECT('glass_recycling',1.5,'finishing',0.5,'transport',0.48)),
((SELECT product_id FROM products WHERE sku='HOME-004'), 1.9000, 0.1500, JSON_OBJECT('wax_processing',1.2,'packaging',0.4,'transport',0.45)),
((SELECT product_id FROM products WHERE sku='HOME-005'), 2.6000, 0.2200, JSON_OBJECT('wood_processing',1.7,'finishing',0.5,'transport',0.62)),

-- Kids & Pets
((SELECT product_id FROM products WHERE sku='KIDS-001'), 0.8500, 0.0600, JSON_OBJECT('bioplastic',0.4,'packaging',0.2,'transport',0.25)),
((SELECT product_id FROM products WHERE sku='KIDS-002'), 1.1000, 0.0700, JSON_OBJECT('formulation',0.6,'bottle',0.3,'transport',0.27)),
((SELECT product_id FROM products WHERE sku='KIDS-003'), 1.8000, 0.1200, JSON_OBJECT('cotton_farming',1.0,'weaving',0.4,'packaging',0.52)),
((SELECT product_id FROM products WHERE sku='KIDS-004'), 1.5000, 0.1100, JSON_OBJECT('fabric_production',0.9,'snaps_and_elastic',0.3,'transport',0.41)),
((SELECT product_id FROM products WHERE sku='KIDS-005'), 1.4000, 0.0900, JSON_OBJECT('wood_crafting',0.8,'paint_finish',0.3,'distribution',0.39)),

-- Kitchen & Dining
((SELECT product_id FROM products WHERE sku='KITCH-001'), 1.8000, 0.1400, JSON_OBJECT('bamboo_processing',1.0,'finishing',0.4,'packaging',0.54)),
((SELECT product_id FROM products WHERE sku='KITCH-002'), 1.4000, 0.1000, JSON_OBJECT('wax_production',0.8,'fabric_base',0.3,'distribution',0.40)),
((SELECT product_id FROM products WHERE sku='KITCH-003'), 1.6000, 0.1200, JSON_OBJECT('glass_recycling',1.0,'finishing',0.4,'transport',0.32)),
((SELECT product_id FROM products WHERE sku='KITCH-004'), 1.3000, 0.1000, JSON_OBJECT('silicone_production',0.8,'packaging',0.3,'shipping',0.30)),
((SELECT product_id FROM products WHERE sku='KITCH-005'), 0.9000, 0.0700, JSON_OBJECT('steel_production',0.5,'cleaning_brush',0.2,'transport',0.27)),

-- Lifestyle & Wellness
((SELECT product_id FROM products WHERE sku='LIFE-001'), 2.5000, 0.2000, JSON_OBJECT('rubber_material',1.6,'production',0.6,'transport',0.52)),
((SELECT product_id FROM products WHERE sku='LIFE-002'), 1.9000, 0.1500, JSON_OBJECT('incense',0.7,'packaging',0.3,'printing',0.45)),
((SELECT product_id FROM products WHERE sku='LIFE-003'), 2.2000, 0.1800, JSON_OBJECT('cotton_production',1.3,'finishing',0.6,'distribution',0.48)),
((SELECT product_id FROM products WHERE sku='LIFE-004'), 2.0000, 0.1600, JSON_OBJECT('cotton_spinning',1.1,'weaving',0.6,'transport',0.46)),
((SELECT product_id FROM products WHERE sku='LIFE-005'), 1.7000, 0.1400, JSON_OBJECT('cotton_processing',0.9,'finishing',0.5,'packaging',0.44)),
((SELECT product_id FROM products WHERE sku='LIFE-006'), 1.3000, 0.1000, JSON_OBJECT('metal_recycling',0.8,'finishing',0.3,'shipping',0.30)),
((SELECT product_id FROM products WHERE sku='LIFE-007'), 1.0000, 0.0900, JSON_OBJECT('paper_recycling',0.5,'binding',0.3,'packaging',0.29)),

-- Outdoor & Garden
((SELECT product_id FROM products WHERE sku='GARD-001'), 2.9000, 0.2500, JSON_OBJECT('plastic_processing',1.8,'assembly',0.7,'transport',0.65)),
((SELECT product_id FROM products WHERE sku='GARD-002'), 1.4000, 0.1200, JSON_OBJECT('pot_production',0.6,'plant_growth',0.4,'distribution',0.42)),
((SELECT product_id FROM products WHERE sku='GARD-003'), 1.6000, 0.1300, JSON_OBJECT('organic_matter',0.9,'packaging',0.4,'shipping',0.43)),
((SELECT product_id FROM products WHERE sku='GARD-004'), 1.8000, 0.1500, JSON_OBJECT('plastic_recycling',1.0,'molding',0.5,'transport',0.45)),
((SELECT product_id FROM products WHERE sku='GARD-005'), 3.1000, 0.2800, JSON_OBJECT('metal_production',2.0,'assembly',0.8,'distribution',0.58)),
((SELECT product_id FROM products WHERE sku='GARD-006'), 1.3000, 0.1000, JSON_OBJECT('seed_packaging',0.6,'soil',0.4,'transport',0.35)),
((SELECT product_id FROM products WHERE sku='GARD-007'), 1.7000, 0.1200, JSON_OBJECT('solar_components',1.0,'assembly',0.5,'transport',0.42)),
((SELECT product_id FROM products WHERE sku='GARD-008'), 1.9000, 0.1500, JSON_OBJECT('solar_cell',1.2,'battery',0.4,'distribution',0.45));

INSERT INTO community_posts (user_id, title, body) VALUES
                                                       ((SELECT user_id FROM users LIMIT 1), 'How to make compost at home', 'Short DIY on starting a worm farm in small spaces.'),
((SELECT user_id FROM users LIMIT 1), 'Sustainable kitchen swaps', 'My favorite swaps to get rid of single-use plastics in the kitchen.');

INSERT INTO ecopoints_ledger (user_id, points_change, reason) VALUES
                                                                  ((SELECT user_id FROM users LIMIT 1), 100, 'Welcome bonus'),
((SELECT user_id FROM users LIMIT 1), 20, 'Community post bonus');

-- END OF FILE.
