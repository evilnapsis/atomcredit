<?php
#[\AllowDynamicProperties]
class LoanData {
    public static $tablename = "loan";

    public $id;
    public $client_id;
    public $loan_product_id;
    public $user_id;
    public $amount;
    public $interest_rate;
    public $term;
    public $frequency;
    public $installment_amount;
    public $total_interest;
    public $total_amount;
    public $disbursement_date;
    public $start_date;
    public $end_date;
    public $status;
    public $notes;
    public $is_active;
    public $created_at;
    public $updated_at;

    // Joined fields and relationships
    public $client_name;
    public $client_lastname;
    public $product_name;
    public $user_name;
    public $client = null;
    public $loan_product = null;
    public $user = null;

    public function __construct() {
        $this->client_id = null;
        $this->loan_product_id = null;
        $this->user_id = null;
        $this->amount = 0.00;
        $this->interest_rate = 0.00;
        $this->term = 1;
        $this->frequency = 4;
        $this->installment_amount = 0.00;
        $this->total_interest = 0.00;
        $this->total_amount = 0.00;
        $this->disbursement_date = date("Y-m-d");
        $this->start_date = date("Y-m-d");
        $this->end_date = date("Y-m-d");
        $this->status = "active";
        $this->notes = "";
        $this->is_active = 1;
        $this->created_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
        $this->client_name = "";
        $this->client_lastname = "";
        $this->product_name = "";
        $this->user_name = "";
    }

    private static function db(): \PDO {
        return Database::getPdo();
    }

    public function calculateTotals(): void {
        $rate = (float)$this->interest_rate / 100;
        $this->total_interest = round((float)$this->amount * $rate * (int)$this->term, 2);
        $this->total_amount = round((float)$this->amount + $this->total_interest, 2);
        $this->installment_amount = round($this->total_amount / (int)$this->term, 2);
    }

    public function generateSchedule(): void {
        $principal_per = round((float)$this->amount / (int)$this->term, 2);
        $interest_per = round((float)$this->total_interest / (int)$this->term, 2);
        $balance = (float)$this->total_amount;
        $due_date = new DateTime($this->start_date);

        for ($i = 1; $i <= (int)$this->term; $i++) {
            $inst_total = round($principal_per + $interest_per, 2);
            $balance = round($balance - $inst_total, 2);

            $s = new LoanScheduleData();
            $s->loan_id = $this->id;
            $s->installment_number = $i;
            $s->due_date = $due_date->format('Y-m-d');
            $s->principal = $principal_per;
            $s->interest = $interest_per;
            $s->amount = $inst_total;
            $s->balance = max(0.00, $balance);
            $s->status = 'pending';
            $s->add();

            switch ((int)$this->frequency) {
                case 1: $due_date->modify('+1 day'); break;
                case 2: $due_date->modify('+1 week'); break;
                case 3: $due_date->modify('+15 days'); break;
                case 4: $due_date->modify('+1 month'); break;
            }
        }
    }

    public function add(): int {
        $this->calculateTotals();

        // Calculate end_date
        $end = new DateTime($this->start_date);
        switch ((int)$this->frequency) {
            case 1: $end->modify('+' . $this->term . ' days'); break;
            case 2: $end->modify('+' . $this->term . ' weeks'); break;
            case 3: $end->modify('+' . ($this->term * 15) . ' days'); break;
            case 4: $end->modify('+' . $this->term . ' months'); break;
        }
        $this->end_date = $end->format('Y-m-d');

        $stmt = self::db()->prepare(
            "INSERT INTO " . self::$tablename . " (client_id, loan_product_id, user_id, amount, interest_rate, term, frequency, installment_amount, total_interest, total_amount, disbursement_date, start_date, end_date, status, notes, is_active, created_at) " .
            "VALUES (:client_id, :loan_product_id, :user_id, :amount, :interest_rate, :term, :frequency, :installment_amount, :total_interest, :total_amount, :disbursement_date, :start_date, :end_date, :status, :notes, :is_active, NOW())"
        );
        $stmt->execute([
            'client_id' => (int)$this->client_id,
            'loan_product_id' => (int)$this->loan_product_id,
            'user_id' => (int)$this->user_id,
            'amount' => (float)$this->amount,
            'interest_rate' => (float)$this->interest_rate,
            'term' => (int)$this->term,
            'frequency' => (int)$this->frequency,
            'installment_amount' => (float)$this->installment_amount,
            'total_interest' => (float)$this->total_interest,
            'total_amount' => (float)$this->total_amount,
            'disbursement_date' => $this->disbursement_date,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status ?: 'active',
            'notes' => $this->notes,
            'is_active' => $this->is_active ? 1 : 0
        ]);
        $this->id = (int)self::db()->lastInsertId();

        $this->generateSchedule();

        return $this->id;
    }

    public function updateStatus(string $status): bool {
        $this->status = $status;
        $stmt = self::db()->prepare("UPDATE " . self::$tablename . " SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => (int)$this->id]);
    }

    public static function getById($id): ?self {
        $stmt = self::db()->prepare(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "WHERE l.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => (int)$id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $loan = $stmt->fetch() ?: null;
        if ($loan) {
            $loan->client = ClientData::getById($loan->client_id);
            $loan->loan_product = LoanProductData::getById($loan->loan_product_id);
        }
        return $loan;
    }

    public static function getAll(): array {
        $stmt = self::db()->query(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "ORDER BY l.created_at DESC"
        );
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getByStatus(string $status): array {
        $stmt = self::db()->prepare(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "WHERE l.status = :status " .
            "ORDER BY l.created_at DESC"
        );
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getByClientId(int $client_id): array {
        $stmt = self::db()->prepare(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "WHERE l.client_id = :client_id " .
            "ORDER BY l.created_at DESC"
        );
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getAllByUserId(int $user_id): array {
        $stmt = self::db()->prepare(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "WHERE l.user_id = :user_id " .
            "ORDER BY l.created_at DESC"
        );
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getByStatusAndUserId(string $status, int $user_id): array {
        $stmt = self::db()->prepare(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "WHERE l.status = :status AND l.user_id = :user_id " .
            "ORDER BY l.created_at DESC"
        );
        $stmt->execute(['status' => $status, 'user_id' => $user_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getByClientIdAndUserId(int $client_id, int $user_id): array {
        $stmt = self::db()->prepare(
            "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
            "FROM " . self::$tablename . " l " .
            "INNER JOIN client c ON l.client_id = c.id " .
            "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
            "INNER JOIN `user` u ON l.user_id = u.id " .
            "WHERE l.client_id = :client_id AND l.user_id = :user_id " .
            "ORDER BY l.created_at DESC"
        );
        $stmt->execute(['client_id' => $client_id, 'user_id' => $user_id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getByDateRange(string $start_date, string $end_date, string $status = 'all', int $client_id = 0): array {
        $sql = "SELECT l.*, c.name AS client_name, c.lastname AS client_lastname, c.document AS client_document, " .
               "c.phone AS client_phone, lp.name AS product_name, CONCAT(u.name, ' ', u.lastname) AS user_name " .
               "FROM " . self::$tablename . " l " .
               "INNER JOIN client c ON l.client_id = c.id " .
               "INNER JOIN loan_product lp ON l.loan_product_id = lp.id " .
               "INNER JOIN `user` u ON l.user_id = u.id " .
               "WHERE DATE(l.created_at) BETWEEN :start_date AND :end_date ";
        $params = [
            'start_date' => $start_date,
            'end_date'   => $end_date
        ];

        if ($status !== 'all' && $status !== '') {
            $sql .= " AND l.status = :status ";
            $params['status'] = $status;
        }

        if ($client_id > 0) {
            $sql .= " AND l.client_id = :client_id ";
            $params['client_id'] = $client_id;
        }

        $sql .= " ORDER BY l.created_at DESC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
    }

    public static function getFrequencyLabel($f): string {
        $labels = [1 => 'Diario', 2 => 'Semanal', 3 => 'Quincenal', 4 => 'Mensual'];
        return $labels[(int)$f] ?? 'N/A';
    }

    public static function getStatusLabel(string $s): string {
        $labels = [
            'pending' => 'Pendiente',
            'active' => 'Activo',
            'paid' => 'Pagado',
            'overdue' => 'En Mora',
            'cancelled' => 'Cancelado'
        ];
        return $labels[$s] ?? $s;
    }

    public static function getStatusBadgeClass(string $s): string {
        $classes = [
            'pending' => 'bg-warning text-dark',
            'active' => 'bg-success',
            'paid' => 'bg-primary',
            'overdue' => 'bg-danger',
            'cancelled' => 'bg-secondary'
        ];
        return $classes[$s] ?? 'bg-secondary';
    }

    public static function delById($id): bool {
        $stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }
}
