<?php
#[\AllowDynamicProperties]
class LoanProductData {
    public static $tablename = "loan_product";

    public $id;
    public $name;
    public $description;
    public $interest_rate;
    public $interest_period;
    public $min_amount;
    public $max_amount;
    public $min_term;
    public $max_term;
    public $frequency;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $this->name = "";
        $this->description = "";
        $this->interest_rate = 5.00;
        $this->interest_period = 1;
        $this->min_amount = 0.00;
        $this->max_amount = 0.00;
        $this->min_term = 1;
        $this->max_term = 12;
        $this->frequency = 4;
        $this->is_active = 1;
        $this->created_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (name, description, interest_rate, interest_period, min_amount, max_amount, min_term, max_term, frequency, is_active, created_at) " .
            "VALUES (:name, :description, :interest_rate, :interest_period, :min_amount, :max_amount, :min_term, :max_term, :frequency, :is_active, NOW())"
        );
        $stmt->execute([
            'name' => $this->name,
            'description' => $this->description,
            'interest_rate' => (float)$this->interest_rate,
            'interest_period' => (int)$this->interest_period,
            'min_amount' => (float)$this->min_amount,
            'max_amount' => (float)$this->max_amount,
            'min_term' => (int)$this->min_term,
            'max_term' => (int)$this->max_term,
            'frequency' => (int)$this->frequency,
            'is_active' => $this->is_active ? 1 : 0
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public function update(): bool {
        $stmt = self::db()->prepare(
            "UPDATE " . self::$tablename . " SET name = :name, description = :description, interest_rate = :interest_rate, " .
            "interest_period = :interest_period, min_amount = :min_amount, max_amount = :max_amount, min_term = :min_term, " .
            "max_term = :max_term, frequency = :frequency, is_active = :is_active WHERE id = :id"
        );
        return $stmt->execute([
            'name' => $this->name,
            'description' => $this->description,
            'interest_rate' => (float)$this->interest_rate,
            'interest_period' => (int)$this->interest_period,
            'min_amount' => (float)$this->min_amount,
            'max_amount' => (float)$this->max_amount,
            'min_term' => (int)$this->min_term,
            'max_term' => (int)$this->max_term,
            'frequency' => (int)$this->frequency,
            'is_active' => $this->is_active ? 1 : 0,
            'id' => (int)$this->id
        ]);
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
        $stmt = self::db()->query("SELECT * FROM " . self::$tablename . " WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getFrequencyLabel($f): string {
        $labels = [1 => 'Diario', 2 => 'Semanal', 3 => 'Quincenal', 4 => 'Mensual'];
        return $labels[(int)$f] ?? 'N/A';
    }

    public static function delById($id): bool {
        $stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }
}
