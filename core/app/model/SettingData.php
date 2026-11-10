<?php
#[\AllowDynamicProperties]
class SettingData {
    public static $tablename = "setting";

    public $id;
    public $setting_key;
    public $setting_value;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $this->setting_key = "";
        $this->setting_value = "";
        $this->created_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public static function getValue(string $key, string $default = ''): string {
        $stmt = self::db()->prepare("SELECT setting_value FROM " . self::$tablename . " WHERE setting_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (string)$val : $default;
    }

    public static function updateValue(string $key, ?string $value): bool {
        $stmt = self::db()->prepare("SELECT id FROM " . self::$tablename . " WHERE setting_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = self::db()->prepare("UPDATE " . self::$tablename . " SET setting_value = :v WHERE setting_key = :k");
            return $stmt->execute(['v' => $value, 'k' => $key]);
        } else {
            $stmt = self::db()->prepare("INSERT INTO " . self::$tablename . " (setting_key, setting_value, created_at) VALUES (:k, :v, NOW())");
            return $stmt->execute(['k' => $key, 'v' => $value]);
        }
    }

    public static function currency(): string {
        return self::getValue("currency", "Q");
    }

    public static function getAll(): array {
        $stmt = self::db()->query("SELECT * FROM " . self::$tablename . " ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }
}
