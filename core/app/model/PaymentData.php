<?php
#[\AllowDynamicProperties]
class PaymentData {
    public static $tablename = "payment";

    public $id;
    public $loan_id;
    public $loan_schedule_id;
    public $user_id;
    public $payment_method_id;
    public $amount;
    public $late_fee;
    public $notes;
    public $created_at;

    // Joined / Navigation fields
    public $payment_method_name;
    public $user_name;

    public function __construct() {
        $this->loan_id = null;
        $this->loan_schedule_id = null;
        $this->user_id = null;
        $this->payment_method_id = 1;
        $this->amount = 0.00;
        $this->late_fee = 0.00;
        $this->notes = "";
        $this->created_at = date("Y-m-d H:i:s");
        $this->payment_method_name = "";
        $this->user_name = "";
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function add(): int {
        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (loan_id, loan_schedule_id, user_id, payment_method_id, amount, late_fee, notes, created_at) " .
            "VALUES (:loan_id, :loan_schedule_id, :user_id, :payment_method_id, :amount, :late_fee, :notes, NOW())"
        );
        $stmt->execute([
            'loan_id' => (int)$this->loan_id,
            'loan_schedule_id' => $this->loan_schedule_id ? (int)$this->loan_schedule_id : null,
            'user_id' => (int)$this->user_id,
            'payment_method_id' => (int)$this->payment_method_id,
            'amount' => (float)$this->amount,
            'late_fee' => (float)$this->late_fee,
            'notes' => $this->notes
        ]);
        $this->id = (int)self::db()->lastInsertId();
        return $this->id;
    }

    public static function getById($id): ?self {
        $stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function getByLoanId($loan_id): array {
        $stmt = self::db()->prepare(
            "SELECT p.*, pm.name AS payment_method_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " p " .
            "LEFT JOIN payment_method pm ON p.payment_method_id = pm.id " .
            "LEFT JOIN `user` u ON p.user_id = u.id " .
            "WHERE p.loan_id = :loan_id ORDER BY p.created_at DESC"
        );
        $stmt->execute(['loan_id' => (int)$loan_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function sumByLoanId($loan_id): float {
        $stmt = self::db()->prepare("SELECT COALESCE(SUM(amount), 0) FROM " . self::$tablename . " WHERE loan_id = :loan_id");
        $stmt->execute(['loan_id' => (int)$loan_id]);
        return (float)$stmt->fetchColumn();
    }

    public static function getDailyPaymentsLast30Days(?int $user_id = null): array {
        $sql = "SELECT DATE(p.created_at) AS pay_date, COALESCE(SUM(p.amount), 0) AS total " .
               "FROM " . self::$tablename . " p ";
        $params = [];
        if ($user_id !== null && $user_id > 0) {
            $sql .= "INNER JOIN loan l ON p.loan_id = l.id WHERE l.user_id = :user_id AND ";
            $params['user_id'] = $user_id;
        } else {
            $sql .= "WHERE ";
        }
        $sql .= "p.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) " .
                "GROUP BY DATE(p.created_at) ORDER BY pay_date ASC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['pay_date']] = (float)$r['total'];
        }

        $labels = [];
        $data   = [];
        $total  = 0.00;

        $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

        for ($i = 29; $i >= 0; $i--) {
            $d = new \DateTime();
            $d->modify("-{$i} days");
            $dateStr = $d->format('Y-m-d');
            $val = $map[$dateStr] ?? 0.00;

            $dayNum = $d->format('j');
            $mNum = (int)$d->format('n');
            $label = $dayNum . ' ' . ($meses[$mNum] ?? '');

            $labels[] = $label;
            $data[]   = $val;
            $total   += $val;
        }

        return [
            'labels' => $labels,
            'data'   => $data,
            'total'  => $total
        ];
    }

    public static function getByDateRange(string $start_date, string $end_date, int $payment_method_id = 0): array {
        $sql = "SELECT p.*, pm.name AS payment_method_name, CONCAT(u.name, ' ', u.lastname) AS user_name, " .
               "l.id AS loan_id, l.total_amount AS loan_total, " .
               "c.name AS client_name, c.lastname AS client_lastname, c.document AS client_document " .
               "FROM " . self::$tablename . " p " .
               "INNER JOIN loan l ON p.loan_id = l.id " .
               "INNER JOIN client c ON l.client_id = c.id " .
               "LEFT JOIN payment_method pm ON p.payment_method_id = pm.id " .
               "LEFT JOIN `user` u ON p.user_id = u.id " .
               "WHERE DATE(p.created_at) BETWEEN :start_date AND :end_date ";
        $params = [
            'start_date' => $start_date,
            'end_date'   => $end_date
        ];

        if ($payment_method_id > 0) {
            $sql .= " AND p.payment_method_id = :pm_id ";
            $params['pm_id'] = $payment_method_id;
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }
}

// Alias de compatibilidad
if (!class_exists('LoanPaymentData', false)) {
    class_alias('PaymentData', 'LoanPaymentData');
}
