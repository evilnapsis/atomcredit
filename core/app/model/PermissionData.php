<?php
#[\AllowDynamicProperties]
class PermissionData {
    public static $tablename = "permission";

    public $id;
    public $rol_id;
    public $view_name;
    public $can_view;
    public $can_add;
    public $can_edit;
    public $can_delete;
    public $created_at;

    public function __construct() {
        $this->rol_id = null;
        $this->view_name = "";
        $this->can_view = 1;
        $this->can_add = 0;
        $this->can_edit = 0;
        $this->can_delete = 0;
        $this->created_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (rol_id, view_name, can_view, can_add, can_edit, can_delete, created_at) " .
            "VALUES (:rol_id, :view_name, :can_view, :can_add, :can_edit, :can_delete, NOW())"
        );
        $stmt->execute([
            'rol_id' => (int)$this->rol_id,
            'view_name' => $this->view_name,
            'can_view' => $this->can_view ? 1 : 0,
            'can_add' => $this->can_add ? 1 : 0,
            'can_edit' => $this->can_edit ? 1 : 0,
            'can_delete' => $this->can_delete ? 1 : 0
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public static function getPermission($rol_id, string $view_name): ?self {
        $stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE rol_id = :rol_id AND view_name = :view_name LIMIT 1");
        $stmt->execute(['rol_id' => (int)$rol_id, 'view_name' => $view_name]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        return $stmt->fetch() ?: null;
    }
}
