<?php
class Category
{
    public static function all(): array
    {
        return Database::get()->query(
            'SELECT c.*, (SELECT COUNT(*) FROM menu_items m WHERE m.category_id = c.id) AS item_count
             FROM categories c ORDER BY c.sort_order, c.id')->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare(
            'SELECT c.*, (SELECT COUNT(*) FROM menu_items m WHERE m.category_id = c.id) AS item_count
             FROM categories c WHERE c.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function create(string $name, int $sort): void
    {
        Database::get()->prepare('INSERT INTO categories (name, sort_order) VALUES (?, ?)')->execute([$name, $sort]);
    }

    public static function update(int $id, string $name, int $sort): void
    {
        Database::get()->prepare('UPDATE categories SET name = ?, sort_order = ? WHERE id = ?')->execute([$name, $sort, $id]);
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }
}
