<?php
#[\AllowDynamicProperties]
class ClientData {
    public static $tablename = "client";

    public $id;
    public $name;
    public $lastname;
    public $document;
    public $phone;
    public $email;
    public $address;
    public $occupation;
    public $employer;
    public $monthly_income;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $this->name = "";
        $this->lastname = "";
        $this->document = "";
        $this->phone = "";
        $this->email = "";
        $this->address = "";
        $this->occupation = "";
        $this->employer = "";
        $this->monthly_income = 0.00;
        $this->is_active = 1;
        $this->created_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (name, lastname, document, phone, email, address, occupation, employer, monthly_income, is_active, created_at) " .
            "VALUES (:name, :lastname, :document, :phone, :email, :address, :occupation, :employer, :monthly_income, :is_active, NOW())"
        );
        $stmt->execute([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'document' => $this->document,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'monthly_income' => (float)$this->monthly_income,
            'is_active' => $this->is_active ? 1 : 0
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public function update(): bool {
        $stmt = self::db()->prepare(
            "UPDATE " . self::$tablename . " SET name = :name, lastname = :lastname, document = :document, " .
            "phone = :phone, email = :email, address = :address, occupation = :occupation, employer = :employer, " .
            "monthly_income = :monthly_income, is_active = :is_active WHERE id = :id"
        );
        return $stmt->execute([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'document' => $this->document,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'monthly_income' => (float)$this->monthly_income,
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
        $stmt = self::db()->query("SELECT * FROM " . self::$tablename . " ORDER BY id DESC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getAllActive(): array {
        $stmt = self::db()->query("SELECT * FROM " . self::$tablename . " WHERE is_active = 1 ORDER BY name ASC, lastname ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getByAgentUserId(int $user_id): array {
        $stmt = self::db()->prepare(
            "SELECT DISTINCT c.* FROM " . self::$tablename . " c " .
            "INNER JOIN loan l ON l.client_id = c.id " .
            "WHERE l.user_id = :user_id " .
            "ORDER BY c.name ASC, c.lastname ASC"
        );
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function countActive(): int {
        $stmt = self::db()->query("SELECT COUNT(*) FROM " . self::$tablename . " WHERE is_active = 1");
        return (int)$stmt->fetchColumn();
    }

    public static function delById($id): bool {
        $stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }
}
