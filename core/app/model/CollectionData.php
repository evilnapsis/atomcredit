<?php
#[\AllowDynamicProperties]
class CollectionData {
    public static $tablename = "collection";

    public $id;
    public $loan_id;
    public $loan_schedule_id;
    public $user_id;
    public $action_type;
    public $result;
    public $next_action_date;
    public $notes;
    public $created_at;

    public $user_name;

    public function __construct() {
        $this->loan_id = null;
        $this->loan_schedule_id = null;
        $this->user_id = null;
        $this->action_type = "call";
        $this->result = "";
        $this->next_action_date = null;
        $this->notes = "";
        $this->created_at = date("Y-m-d H:i:s");
        $this->user_name = "";
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (loan_id, loan_schedule_id, user_id, action_type, result, next_action_date, notes, created_at) " .
            "VALUES (:loan_id, :loan_schedule_id, :user_id, :action_type, :result, :next_action_date, :notes, NOW())"
        );
        $stmt->execute([
            'loan_id' => (int)$this->loan_id,
            'loan_schedule_id' => $this->loan_schedule_id ? (int)$this->loan_schedule_id : null,
            'user_id' => (int)$this->user_id,
            'action_type' => $this->action_type,
            'result' => $this->result,
            'next_action_date' => $this->next_action_date ?: null,
            'notes' => $this->notes
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public static function getByLoanId($loan_id): array {
        $stmt = self::db()->prepare(
            "SELECT c.*, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " c " .
            "LEFT JOIN `user` u ON c.user_id = u.id " .
            "WHERE c.loan_id = :loan_id ORDER BY c.created_at DESC"
        );
        $stmt->execute(['loan_id' => (int)$loan_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }
}
