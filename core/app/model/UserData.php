<?php
#[\AllowDynamicProperties]
class UserData {
    public static $tablename = "user";

    public $id;
    public $username;
    public $name;
    public $lastname;
    public $email;
    public $password;
    public $is_active;
    public $rol_id;
    public $created_at;
    public $updated_at;

    // Navigation property
    public $rol = null;

    public function __construct() {
        $this->username = "";
        $this->name = "";
        $this->lastname = "";
        $this->email = "";
        $this->password = "";
        $this->is_active = 1;
        $this->rol_id = 1;
        $this->created_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO `" . self::$tablename . "` (username, name, lastname, email, password, is_active, rol_id, created_at) " .
            "VALUES (:username, :name, :lastname, :email, :password, :is_active, :rol_id, NOW())"
        );
        $stmt->execute([
            'username' => $this->username,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => $this->is_active ? 1 : 0,
            'rol_id' => (int)$this->rol_id
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public function update(): bool {
        if (!empty($this->password)) {
            $stmt = self::db()->prepare(
                "UPDATE `" . self::$tablename . "` SET username = :username, name = :name, lastname = :lastname, " .
                "email = :email, password = :password, is_active = :is_active, rol_id = :rol_id WHERE id = :id"
            );
            return $stmt->execute([
                'username' => $this->username,
                'name' => $this->name,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'password' => $this->password,
                'is_active' => $this->is_active ? 1 : 0,
                'rol_id' => (int)$this->rol_id,
                'id' => (int)$this->id
            ]);
        } else {
            $stmt = self::db()->prepare(
                "UPDATE `" . self::$tablename . "` SET username = :username, name = :name, lastname = :lastname, " .
                "email = :email, is_active = :is_active, rol_id = :rol_id WHERE id = :id"
            );
            return $stmt->execute([
                'username' => $this->username,
                'name' => $this->name,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'is_active' => $this->is_active ? 1 : 0,
                'rol_id' => (int)$this->rol_id,
                'id' => (int)$this->id
            ]);
        }
    }

    public static function getById($id): ?self {
        $stmt = self::db()->prepare("SELECT * FROM `" . self::$tablename . "` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            $user->rol = RolData::getById($user->rol_id);
        }
        return $user;
    }

    public static function getByLogin(string $login): ?self {
        $stmt = self::db()->prepare("SELECT * FROM `" . self::$tablename . "` WHERE username = :l1 OR email = :l2 LIMIT 1");
        $stmt->execute(['l1' => $login, 'l2' => $login]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            $user->rol = RolData::getById($user->rol_id);
        }
        return $user;
    }

    public static function getByEmail(string $email): ?self {
        $stmt = self::db()->prepare("SELECT * FROM `" . self::$tablename . "` WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function getAll(): array {
        $stmt = self::db()->query("SELECT * FROM `" . self::$tablename . "` ORDER BY id ASC");
        $users = $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        foreach ($users as $u) {
            $u->rol = RolData::getById($u->rol_id);
        }
        return $users;
    }

    public static function getAllActive(): array {
        $stmt = self::db()->query("SELECT * FROM `" . self::$tablename . "` WHERE is_active = 1 ORDER BY name ASC, lastname ASC");
        $users = $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        foreach ($users as $u) {
            $u->rol = RolData::getById($u->rol_id);
        }
        return $users;
    }

    public static function delById($id): bool {
        $stmt = self::db()->prepare("DELETE FROM `" . self::$tablename . "` WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }
}
