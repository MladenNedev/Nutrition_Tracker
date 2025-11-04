-- ====================
-- Users
-- ====================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================
-- Meals
-- ====================
CREATE TABLE IF NOT EXISTS meals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ====================
-- Foods
-- ====================
CREATE TABLE IF NOT EXISTS foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
);

-- ====================
-- Nutrients
-- ====================
CREATE TABLE IF NOT EXISTS nutrients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  unit VARCHAR(20) NOT NULL
);

-- ====================
-- Food ↔ Nutrients (per 100g composition)
-- ====================
CREATE TABLE IF NOT EXISTS food_nutrients (
  food_id INT NOT NULL,
  nutrient_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,  -- precise to 2 decimals
  PRIMARY KEY (food_id, nutrient_id),
  FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
  FOREIGN KEY (nutrient_id) REFERENCES nutrients(id) ON DELETE CASCADE
);

-- ====================
-- Meal Items (foods eaten per meal)
-- ====================
CREATE TABLE IF NOT EXISTS meal_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meal_id INT NOT NULL,
  food_id INT NOT NULL,
  quantity_g DECIMAL(10,2) NOT NULL,  -- grams eaten, 2 decimals
  FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE CASCADE,
  FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);
    


