<?php
#[\AllowDynamicProperties]
class RolData {
    public static $tablename = "rol";

    public $id;
    public $name;
    public $created_at;

    public function __construct() {
        $this->name = "";
        $this->created_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public static function getById($id): ?self {
        $stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function getAll(): array {
        $stmt = self::db()->query("SELECT * FROM " . self::$tablename . " ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }
}
