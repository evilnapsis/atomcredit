<?php
#[\AllowDynamicProperties]
class PaymentMethodData {
    public static $tablename = "payment_method";

    public $id;
    public $name;
    public $is_active;
    public $created_at;

    public function __construct() {
        $this->name = "";
        $this->is_active = 1;
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

    public static function getAllActive(): array {
        $stmt = self::db()->query("SELECT * FROM " . self::$tablename . " WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }
}
