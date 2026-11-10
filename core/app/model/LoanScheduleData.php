<?php
#[\AllowDynamicProperties]
class LoanScheduleData {
    public static $tablename = "loan_schedule";

    public $id;
    public $loan_id;
    public $installment_number;
    public $due_date;
    public $principal;
    public $interest;
    public $amount;
    public $paid_amount;
    public $balance;
    public $status;
    public $created_at;
    public $updated_at;

    // Propiedades de navegación y relaciones con préstamo/cliente
    public $loan_user_id;
    public $loan_amount;
    public $loan_total;
    public $client_id;
    public $client_name;
    public $client_lastname;
    public $client_phone;
    public $client_document;

    public function __construct() {
        $this->loan_id = null;
        $this->installment_number = 1;
        $this->due_date = date("Y-m-d");
        $this->principal = 0.00;
        $this->interest = 0.00;
        $this->amount = 0.00;
        $this->paid_amount = 0.00;
        $this->balance = 0.00;
        $this->status = "pending";
        $this->created_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (loan_id, installment_number, due_date, principal, interest, amount, paid_amount, balance, status, created_at) " .
            "VALUES (:loan_id, :installment_number, :due_date, :principal, :interest, :amount, :paid_amount, :balance, :status, NOW())"
        );
        $stmt->execute([
            'loan_id' => (int)$this->loan_id,
            'installment_number' => (int)$this->installment_number,
            'due_date' => $this->due_date,
            'principal' => (float)$this->principal,
            'interest' => (float)$this->interest,
            'amount' => (float)$this->amount,
            'paid_amount' => (float)$this->paid_amount,
            'balance' => (float)$this->balance,
            'status' => $this->status
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public function update(): bool {
        $stmt = self::db()->prepare(
            "UPDATE " . self::$tablename . " SET paid_amount = :paid_amount, balance = :balance, status = :status WHERE id = :id"
        );
        return $stmt->execute([
            'paid_amount' => (float)$this->paid_amount,
            'balance' => (float)$this->balance,
            'status' => $this->status,
            'id' => (int)$this->id
        ]);
    }

    public static function getById($id): ?self {
        $stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function getByLoanId($loan_id): array {
        $stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE loan_id = :loan_id ORDER BY installment_number ASC");
        $stmt->execute(['loan_id' => (int)$loan_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getNextPending($loan_id): ?self {
        $stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE loan_id = :loan_id AND status IN ('pending', 'partial', 'overdue') ORDER BY installment_number ASC LIMIT 1");
        $stmt->execute(['loan_id' => (int)$loan_id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function updateOverdue(): void {
        self::db()->exec("UPDATE " . self::$tablename . " SET status = 'overdue' WHERE status IN ('pending', 'partial') AND due_date < CURDATE()");
    }

    public function getOverdueDays(): int {
        if ($this->due_date >= date('Y-m-d') || $this->status === 'paid') {
            return 0;
        }
        $due = new \DateTime($this->due_date);
        $today = new \DateTime(date('Y-m-d'));
        return $today > $due ? (int)$today->diff($due)->days : 0;
    }

    public static function getTodayScheduled(?int $user_id = null): array {
        $sql = "SELECT s.*, l.id AS loan_id, l.user_id AS loan_user_id, l.amount AS loan_amount, l.total_amount AS loan_total, " .
               "c.id AS client_id, c.name AS client_name, c.lastname AS client_lastname, c.phone AS client_phone, c.document AS client_document " .
               "FROM " . self::$tablename . " s " .
               "INNER JOIN loan l ON s.loan_id = l.id " .
               "INNER JOIN client c ON l.client_id = c.id " .
               "WHERE s.due_date = CURDATE() AND s.status IN ('pending', 'partial') AND l.status NOT IN ('cancelled', 'paid') ";
        $params = [];
        if ($user_id !== null && $user_id > 0) {
            $sql .= " AND l.user_id = :user_id ";
            $params['user_id'] = $user_id;
        }
        $sql .= " ORDER BY c.name ASC, s.installment_number ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getOverdueScheduled(?int $user_id = null): array {
        $sql = "SELECT s.*, l.id AS loan_id, l.user_id AS loan_user_id, l.amount AS loan_amount, l.total_amount AS loan_total, " .
               "c.id AS client_id, c.name AS client_name, c.lastname AS client_lastname, c.phone AS client_phone, c.document AS client_document " .
               "FROM " . self::$tablename . " s " .
               "INNER JOIN loan l ON s.loan_id = l.id " .
               "INNER JOIN client c ON l.client_id = c.id " .
               "WHERE s.due_date < CURDATE() AND s.status IN ('pending', 'partial', 'overdue') AND l.status NOT IN ('cancelled', 'paid') ";
        $params = [];
        if ($user_id !== null && $user_id > 0) {
            $sql .= " AND l.user_id = :user_id ";
            $params['user_id'] = $user_id;
        }
        $sql .= " ORDER BY s.due_date ASC, c.name ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getStatusLabel(string $s): string {
        $labels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagada',
            'partial' => 'Parcial',
            'overdue' => 'Vencida'
        ];
        return $labels[$s] ?? $s;
    }

    public static function getStatusBadgeClass(string $s): string {
        $classes = [
            'pending' => 'bg-secondary',
            'paid' => 'bg-success',
            'partial' => 'bg-warning text-dark',
            'overdue' => 'bg-danger'
        ];
        return $classes[$s] ?? 'bg-secondary';
    }
}
