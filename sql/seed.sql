-- ==========================
-- RESET (order matters)
-- ==========================
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM meal_items;
DELETE FROM meals;
DELETE FROM food_nutrients;
DELETE FROM foods;
DELETE FROM nutrients;
DELETE FROM users;

ALTER TABLE meal_items AUTO_INCREMENT = 1;
ALTER TABLE meals AUTO_INCREMENT = 1;
ALTER TABLE food_nutrients AUTO_INCREMENT = 1;
ALTER TABLE foods AUTO_INCREMENT = 1;
ALTER TABLE nutrients AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================
-- USERS
-- ==========================
-- use the same bcrypt you used in the API:
-- password = "yourpassword"
INSERT INTO users (username, email, password) VALUES
('mladen', 'mladen@example.com', '$2y$10$9KH3XrhFlgiwjlFt3hkUZumDyfTckc9dbZZt7r7.wb7K4n7SNaa5a'),
('katya', 'katya@example.com', '$2y$10$9KH3XrhFlgiwjlFt3hkUZumDyfTckc9dbZZt7r7.wb7K4n7SNaa5a');

-- ==========================
-- NUTRIENTS (fixed IDs)
-- ==========================
-- 1 = Calories  (kcal)
-- 2 = Protein   (g)
-- 3 = Carbs     (g)
-- 4 = Fat       (g)
-- leave Fiber, Sodium for later
INSERT INTO nutrients (id, name, unit) VALUES
(1, 'Calories',      'kcal'),
(2, 'Protein',       'g'),
(3, 'Carbohydrates', 'g'),
(4, 'Fat',           'g'),
(5, 'Fiber',         'g'),
(6, 'Sodium',        'mg')
ON DUPLICATE KEY UPDATE name = VALUES(name), unit = VALUES(unit);

-- ==========================
-- FOODS
-- ==========================
INSERT INTO foods (id, name) VALUES
(1, 'Chicken breast, raw'),
(2, 'Oats, dry'),
(3, 'Apple, raw'),
(4, 'Rice, cooked'),
(5, 'Egg, whole')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ==========================
-- FOOD NUTRIENTS (per 100g)
-- ==========================
-- Chicken breast
INSERT INTO food_nutrients (food_id, nutrient_id, amount) VALUES
(1, 1, 165),   -- kcal
(1, 2, 31),    -- protein
(1, 3, 0),     -- carbs
(1, 4, 3.6),   -- fat
(1, 5, 0),     -- fiber
(1, 6, 74)     -- sodium
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Oats
INSERT INTO food_nutrients (food_id, nutrient_id, amount) VALUES
(2, 1, 389),
(2, 2, 17),
(2, 3, 66),
(2, 4, 7),
(2, 5, 10),
(2, 6, 2)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Apple
INSERT INTO food_nutrients (food_id, nutrient_id, amount) VALUES
(3, 1, 52),
(3, 2, 0.3),
(3, 3, 14),
(3, 4, 0.2),
(3, 5, 2.4),
(3, 6, 1)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Rice
INSERT INTO food_nutrients (food_id, nutrient_id, amount) VALUES
(4, 1, 130),
(4, 2, 2.7),
(4, 3, 28),
(4, 4, 0.3),
(4, 5, 0.4),
(4, 6, 1)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Egg
INSERT INTO food_nutrients (food_id, nutrient_id, amount) VALUES
(5, 1, 155),
(5, 2, 13),
(5, 3, 1.1),
(5, 4, 11),
(5, 5, 0),
(5, 6, 124)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- ==========================
-- MEALS for TODAY (2025-10-31)
-- ==========================
INSERT INTO meals (user_id, name, created_at) VALUES
(1, 'Breakfast', '2025-10-31 08:00:00'),
(1, 'Lunch',     '2025-10-31 13:00:00'),
(1, 'Dinner',    '2025-10-31 19:00:00'),
(1, 'Snacks',    '2025-10-31 16:00:00');

-- ==========================
-- MEAL ITEMS (what was eaten)
-- ==========================
-- Breakfast: 50g oats + 100g egg
INSERT INTO meal_items (meal_id, food_id, quantity_g) VALUES
(1, 2, 50.00),    -- 50g oats
(1, 5, 100.00);   -- 100g egg

-- Lunch: 200g chicken + 150g rice
INSERT INTO meal_items (meal_id, food_id, quantity_g) VALUES
(2, 1, 200.00),   -- 200g chicken
(2, 4, 150.00);   -- 150g rice

-- Dinner: 180g apple
INSERT INTO meal_items (meal_id, food_id, quantity_g) VALUES
(3, 3, 180.00);

-- Snacks: 100g apple
INSERT INTO meal_items (meal_id, food_id, quantity_g) VALUES
(4, 3, 100.00);
