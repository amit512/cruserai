<?php
// app/Product.php
declare(strict_types=1);

class Product {
    public static function create(array $data): int {
        $stmt = Database::pdo()->prepare("
            INSERT INTO products (seller_id, name, description, price, stock, image, category)
            VALUES (:seller_id, :name, :description, :price, :stock, :image, :category)
        ");
        $stmt->execute([
            ':seller_id' => $data['seller_id'],
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':stock' => $data['stock'],
            ':image' => $data['image'] ?? null,
            ':category' => $data['category'] ?? 'general',
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $stmt = Database::pdo()->prepare("
            UPDATE products SET name=:name, description=:description, price=:price, stock=:stock,
                image=:image, category=:category
            WHERE id=:id AND seller_id=:seller_id
        ");
        $stmt->execute([
            ':id' => $id,
            ':seller_id' => $data['seller_id'],
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':stock' => $data['stock'],
            ':image' => $data['image'] ?? null,
            ':category' => $data['category'] ?? 'general',
        ]);
    }

    public static function delete(int $id, int $seller_id): void {
        $stmt = Database::pdo()->prepare("DELETE FROM products WHERE id=? AND seller_id=?");
        $stmt->execute([$id, $seller_id]);
    }

    public static function allActive(int $limit = null, int $offset = 0): array {
        $sql = "SELECT p.*, u.name as seller_name FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE p.is_active = 1
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = Database::pdo()->query($sql);
        return $stmt->fetchAll();
    }

    public static function byCategory(string $category, int $limit = null): array {
        $sql = "SELECT p.*, u.name as seller_name FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE p.category = ? AND p.is_active = 1 
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    public static function search(string $query, int $limit = null): array {
        $searchTerm = "%$query%";
        $sql = "SELECT p.*, u.name as seller_name FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.is_active = 1 
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    public static function featured(int $limit = 6): array {
        $sql = "SELECT p.*, u.name as seller_name FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE p.is_active = 1 
                ORDER BY RAND() 
                LIMIT $limit";
        
        $stmt = Database::pdo()->query($sql);
        return $stmt->fetchAll();
    }

    public static function bySeller(int $seller_id): array {
        $stmt = Database::pdo()->prepare("SELECT * FROM products WHERE seller_id=? AND is_active = 1 ORDER BY created_at DESC");
        $stmt->execute([$seller_id]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = Database::pdo()->prepare("SELECT p.*, u.name as seller_name FROM products p 
                                        JOIN users u ON p.seller_id = u.id 
                                        WHERE p.id=? AND p.is_active = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getCategories(): array {
        try {
            // Try to get categories from the new product_categories table
            $stmt = Database::pdo()->query("
                SELECT pc.slug as category, COUNT(p.id) as count 
                FROM product_categories pc 
                LEFT JOIN products p ON pc.slug = p.category AND p.is_active = 1
                WHERE pc.is_active = 1 
                GROUP BY pc.slug, pc.name 
                ORDER BY count DESC, pc.name ASC
            ");
            $categories = $stmt->fetchAll();
            
            if (!empty($categories)) {
                return $categories;
            }
        } catch (Exception $e) {
            // Fallback to old method if table doesn't exist
        }
        
        // Fallback: get categories from products table
        try {
            $stmt = Database::pdo()->query("
                SELECT category, COUNT(*) as count 
                FROM products 
                WHERE is_active = 1 
                GROUP BY category 
                ORDER BY count DESC
                
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // Final fallback: return default categories
            return [
                ['category' => 'general', 'count' => self::getTotalCount()]
            ];
        }
    }

    public static function getTotalCount(): int {
        try {
            $stmt = Database::pdo()->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
        } catch (Exception $e) {
            $stmt = Database::pdo()->query("SELECT COUNT(*) FROM products");
        }
        return (int) $stmt->fetchColumn();
    }

    public static function getRandomProducts(int $limit = 4): array {
        $sql = "SELECT p.*, u.name as seller_name FROM products p 
                JOIN users u ON p.seller_id = u.id 
                WHERE p.is_active = 1 
                ORDER BY RAND() 
                LIMIT $limit";
        
        $stmt = Database::pdo()->query($sql);
        return $stmt->fetchAll();
    }

    public static function getCategoryInfo(string $categorySlug): ?array {
        try {
            $stmt = Database::pdo()->prepare("
                SELECT * FROM product_categories 
                WHERE slug = ? AND is_active = 1
            ");
            $stmt->execute([$categorySlug]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getAllCategories(): array {
        try {
            $stmt = Database::pdo()->query("
                SELECT * FROM product_categories 
                WHERE is_active = 1 
                ORDER BY name ASC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // Return default categories if table doesn't exist
            return [
                ['slug' => 'general', 'name' => 'General', 'icon' => 'star'],
                ['slug' => 'jewelry', 'name' => 'Handmade Jewelry', 'icon' => 'gem'],
                ['slug' => 'home-decor', 'name' => 'Home Decor', 'icon' => 'home'],
                ['slug' => 'clothing', 'name' => 'Clothing & Accessories', 'icon' => 'tshirt'],
                ['slug' => 'art', 'name' => 'Art & Paintings', 'icon' => 'palette'],
                ['slug' => 'pottery', 'name' => 'Pottery & Ceramics', 'icon' => 'circle'],
                ['slug' => 'textiles', 'name' => 'Textiles & Fabrics', 'icon' => 'cut'],
                ['slug' => 'woodwork', 'name' => 'Woodwork & Furniture', 'icon' => 'tree'],
                ['slug' => 'metalwork', 'name' => 'Metalwork & Sculptures', 'icon' => 'hammer'],
                ['slug' => 'leather', 'name' => 'Leather Goods', 'icon' => 'briefcase'],
                ['slug' => 'candles', 'name' => 'Candles & Soaps', 'icon' => 'fire']
            ];
        }
    }

    /**
     * Ensure product_reviews table exists (id, product_id, user_id, rating, comment, images, created_at)
     */
    public static function ensureProductReviewsSchema(): void {
        try {
            Database::pdo()->exec("CREATE TABLE IF NOT EXISTS product_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                user_id INT NOT NULL,
                rating TINYINT NOT NULL,
                comment TEXT NULL,
                images JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product_id (product_id),
                INDEX idx_user_product (user_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Exception $e) {
            // ignore
        }
    }

    public static function getRatingSummary(int $productId): array {
        self::ensureProductReviewsSchema();
        try {
            $stmt = Database::pdo()->prepare("SELECT COUNT(*) as count, COALESCE(AVG(rating),0) as avg FROM product_reviews WHERE product_id = ?");
            $stmt->execute([$productId]);
            $row = $stmt->fetch() ?: ['count' => 0, 'avg' => 0];
            return ['count' => (int)$row['count'], 'avg' => round((float)$row['avg'], 2)];
        } catch (Exception $e) {
            return ['count' => 0, 'avg' => 0.0];
        }
    }

    public static function getProductReviews(int $productId, int $limit = 50, int $offset = 0): array {
        self::ensureProductReviewsSchema();
        try {
            $stmt = Database::pdo()->prepare("SELECT pr.*, u.name AS customer_name FROM product_reviews pr JOIN users u ON u.id = pr.user_id WHERE pr.product_id = ? ORDER BY pr.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$productId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
