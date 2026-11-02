<?php
declare(strict_types=1);

/** Semua query & aturan hutang/pelunasan ada di sini. */
final class DebtRepository
{
    public function __construct(private PDO $pdo) {}


    /** Ringkasan seluruh data sepanjang waktu untuk halaman Data Member. */
    public function memberOverview(): array
    {
        $sql = "SELECT
            (SELECT COUNT(*) FROM members) AS total_members,
            (SELECT COALESCE(SUM(amount), 0) FROM debts WHERE cancelled_at IS NULL) AS total_debt,
            (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE cancelled_at IS NULL) AS total_paid,
            (SELECT COUNT(*) FROM (
                SELECT m.id
                FROM members m
                LEFT JOIN (SELECT member_id, SUM(amount) AS total_debt FROM debts WHERE cancelled_at IS NULL GROUP BY member_id) d ON d.member_id = m.id
                LEFT JOIN (SELECT member_id, SUM(amount) AS total_paid FROM payments WHERE cancelled_at IS NULL GROUP BY member_id) p ON p.member_id = m.id
                WHERE COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0) > 0
            ) debtors) AS debtors";
        return $this->pdo->query($sql)->fetch() ?: ['total_members'=>0,'total_debt'=>0,'total_paid'=>0,'debtors'=>0];
    }

    public function updateMember(int $id, string $name, ?string $phone): bool
    {
        $stmt = $this->pdo->prepare('UPDATE members SET name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $id]);
        return $stmt->rowCount() > 0;
    }

    /** Opsi ringan untuk dropdown transaksi; tidak memuat agregasi saldo. */
    public function members(): array
    {
        return $this->pdo->query("SELECT m.id, m.name, m.phone,
            COALESCE(d.total_debt, 0) AS total_debt,
            COALESCE(p.total_paid, 0) AS total_paid
            FROM members m
            LEFT JOIN (SELECT member_id, SUM(amount) AS total_debt FROM debts GROUP BY member_id) d ON d.member_id = m.id
            LEFT JOIN (SELECT member_id, SUM(amount) AS total_paid FROM payments WHERE cancelled_at IS NULL GROUP BY member_id) p ON p.member_id = m.id
            ORDER BY m.name")->fetchAll();
    }

    /** Ambil satu member untuk halaman detail. */
    public function findMember(int $id): ?array
    {
        $s = $this->pdo->prepare("SELECT m.*,
            COALESCE(d.total_debt, 0) AS total_debt,
            COALESCE(p.total_paid, 0) AS total_paid,
            d.last_debt
            FROM members m
            LEFT JOIN (
                SELECT member_id, SUM(amount) AS total_debt, MAX(debt_date) AS last_debt
                FROM debts WHERE cancelled_at IS NULL GROUP BY member_id
            ) d ON d.member_id = m.id
            LEFT JOIN (
                SELECT member_id, SUM(amount) AS total_paid
                FROM payments WHERE cancelled_at IS NULL GROUP BY member_id
            ) p ON p.member_id = m.id
            WHERE m.id = :id");
        $s->execute(['id' => $id]);
        $member = $s->fetch();
        return $member ?: null;
    }

    /** Server-side pagination untuk daftar member. */
    public function paginateMembers(string $search, string $status, string $sort, string $direction, int $limit, int $offset): array
    {
        $sortColumns = [
            'name' => 'm.name',
            'debt' => 'COALESCE(d.total_debt, 0)',
            'paid' => 'COALESCE(p.total_paid, 0)',
            'balance' => '(COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0))',
            'last_debt' => 'd.last_debt',
        ];
        $orderBy = $sortColumns[$sort] ?? $sortColumns['name'];
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = "(m.name ILIKE :search_name OR COALESCE(m.phone, '') ILIKE :search_phone)";
            $params['search_name'] = '%' . $search . '%';
            $params['search_phone'] = '%' . $search . '%';
        }
        if ($status === 'debt') {
            $where[] = '(COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0)) > 0';
        } elseif ($status === 'paid') {
            $where[] = '(COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0)) <= 0';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $baseSql = " FROM members m
            LEFT JOIN (SELECT member_id, SUM(amount) AS total_debt, MAX(debt_date) AS last_debt FROM debts WHERE cancelled_at IS NULL GROUP BY member_id) d ON d.member_id = m.id
            LEFT JOIN (SELECT member_id, SUM(amount) AS total_paid FROM payments WHERE cancelled_at IS NULL GROUP BY member_id) p ON p.member_id = m.id
            $whereSql";

        $count = $this->pdo->prepare('SELECT COUNT(*)' . $baseSql);
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $sql = "SELECT m.id, m.name, m.phone,
            COALESCE(d.total_debt, 0) AS total_debt,
            COALESCE(p.total_paid, 0) AS total_paid,
            d.last_debt" . $baseSql . " ORDER BY $orderBy $direction NULLS LAST, m.id ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /** Seluruh member sesuai filter untuk ekspor CSV (tanpa batas pagination). */
    public function exportMembers(string $search, string $status, string $sort, string $direction): array
    {
        $sortColumns = [
            'name' => 'm.name', 'debt' => 'COALESCE(d.total_debt, 0)',
            'paid' => 'COALESCE(p.total_paid, 0)',
            'balance' => '(COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0))',
            'last_debt' => 'd.last_debt',
        ];
        $orderBy = $sortColumns[$sort] ?? $sortColumns['name'];
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $where = []; $params = [];
        if ($search !== '') {
            $where[] = "(m.name ILIKE :search_name OR COALESCE(m.phone, '') ILIKE :search_phone)";
            $params['search_name'] = '%' . $search . '%';
            $params['search_phone'] = '%' . $search . '%';
        }
        if ($status === 'debt') $where[] = '(COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0)) > 0';
        elseif ($status === 'paid') $where[] = '(COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0)) <= 0';
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT m.id, m.name, m.phone, COALESCE(d.total_debt, 0) AS total_debt,
                       COALESCE(p.total_paid, 0) AS total_paid, d.last_debt,
                       (COALESCE(d.total_debt, 0) - COALESCE(p.total_paid, 0)) AS balance
                FROM members m
                LEFT JOIN (SELECT member_id, SUM(amount) AS total_debt, MAX(debt_date) AS last_debt FROM debts GROUP BY member_id) d ON d.member_id = m.id
                LEFT JOIN (SELECT member_id, SUM(amount) AS total_paid FROM payments WHERE cancelled_at IS NULL GROUP BY member_id) p ON p.member_id = m.id
                $whereSql ORDER BY $orderBy $direction NULLS LAST, m.id ASC";
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Transaksi hutang dan pembayaran dalam periode, termasuk pembayaran yang dibatalkan. */
    public function exportTransactions(string $from, string $to): array
    {
        $sql = <<<'SQL'
            SELECT * FROM (
                SELECT d.debt_date AS transaction_date, d.created_at, 'Hutang' AS kind,
                       m.name AS member_name, m.phone, d.amount, d.note, d.recorded_by,
                       CASE WHEN d.cancelled_at IS NULL THEN 'Berlaku' ELSE 'Dibatalkan' END AS status, d.cancelled_at
                FROM debts d JOIN members m ON m.id = d.member_id
                WHERE d.debt_date >= CAST(:debt_from AS date) AND d.debt_date <= CAST(:debt_to AS date)
                UNION ALL
                SELECT p.created_at::date AS transaction_date, p.created_at, 'Pembayaran' AS kind,
                       m.name AS member_name, m.phone, p.amount, p.note, p.recorded_by,
                       CASE WHEN p.cancelled_at IS NULL THEN 'Berlaku' ELSE 'Dibatalkan' END AS status,
                       p.cancelled_at
                FROM payments p JOIN members m ON m.id = p.member_id
                WHERE p.created_at::date >= CAST(:payment_from AS date) AND p.created_at::date <= CAST(:payment_to AS date)
            ) t ORDER BY created_at DESC
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['debt_from' => $from, 'debt_to' => $to, 'payment_from' => $from, 'payment_to' => $to]);
        return $stmt->fetchAll();
    }


    /** Daftar hutang global dengan filter, pagination, dan urutan aman. */
    public function paginateDebts(string $search, int $memberId, string $from, string $to, string $sort, string $dir, int $limit, int $offset, string $status = 'all'): array
    {
        $columns = ['date' => 'd.debt_date', 'amount' => 'd.amount', 'member' => 'm.name', 'created' => 'd.created_at'];
        $order = $columns[$sort] ?? $columns['date']; $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $where = ['d.debt_date >= CAST(:date_from AS date)', 'd.debt_date <= CAST(:date_to AS date)'];
        if ($status === 'valid') $where[] = 'd.cancelled_at IS NULL'; elseif ($status === 'cancelled') $where[] = 'd.cancelled_at IS NOT NULL';
        $params = ['date_from' => $from, 'date_to' => $to];
        if ($memberId > 0) { $where[] = 'd.member_id = :member_id'; $params['member_id'] = $memberId; }
        if ($search !== '') { $where[] = "(m.name ILIKE :search_name OR COALESCE(m.phone, '') ILIKE :search_phone OR COALESCE(d.note, '') ILIKE :search_note)"; $v = '%' . $search . '%'; $params += ['search_name'=>$v, 'search_phone'=>$v, 'search_note'=>$v]; }
        $base = ' FROM debts d JOIN members m ON m.id = d.member_id WHERE ' . implode(' AND ', $where);
        $st = $this->pdo->prepare('SELECT COUNT(*)' . $base); $st->execute($params); $total = (int)$st->fetchColumn();
        $sql = 'SELECT d.id, d.member_id, d.debt_date, d.created_at, d.amount, d.note, d.recorded_by, d.cancelled_at, d.cancel_reason, m.name AS member_name, m.phone' . $base . " ORDER BY $order $dir, d.id DESC LIMIT :lim OFFSET :off";
        $st = $this->pdo->prepare($sql); foreach ($params as $k=>$v) $st->bindValue(':'.$k, $v); $st->bindValue(':lim',$limit,PDO::PARAM_INT); $st->bindValue(':off',$offset,PDO::PARAM_INT); $st->execute();
        return ['rows'=>$st->fetchAll(), 'total'=>$total];
    }

    /** Daftar pembayaran global; transaksi batal tetap ditampilkan. */
    public function paginatePayments(string $search, int $memberId, string $from, string $to, string $status, string $sort, string $dir, int $limit, int $offset): array
    {
        $columns = ['date' => 'p.created_at', 'amount' => 'p.amount', 'member' => 'm.name', 'created' => 'p.created_at'];
        $order = $columns[$sort] ?? $columns['date']; $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $where = ['p.created_at::date >= CAST(:date_from AS date)', 'p.created_at::date <= CAST(:date_to AS date)'];
        $params = ['date_from'=>$from, 'date_to'=>$to];
        if ($memberId > 0) { $where[] = 'p.member_id = :member_id'; $params['member_id']=$memberId; }
        if ($status === 'valid') $where[] = 'p.cancelled_at IS NULL'; elseif ($status === 'cancelled') $where[] = 'p.cancelled_at IS NOT NULL';
        if ($search !== '') { $where[] = "(m.name ILIKE :search_name OR COALESCE(m.phone, '') ILIKE :search_phone OR COALESCE(p.note, '') ILIKE :search_note)"; $v='%'.$search.'%'; $params += ['search_name'=>$v,'search_phone'=>$v,'search_note'=>$v]; }
        $base = ' FROM payments p JOIN members m ON m.id = p.member_id WHERE ' . implode(' AND ', $where);
        $st=$this->pdo->prepare('SELECT COUNT(*)'.$base); $st->execute($params); $total=(int)$st->fetchColumn();
        $sql='SELECT p.id,p.member_id,p.created_at,p.amount,p.note,p.recorded_by,p.cancelled_at,m.name AS member_name,m.phone'.$base." ORDER BY $order $dir, p.id DESC LIMIT :lim OFFSET :off";
        $st=$this->pdo->prepare($sql); foreach($params as $k=>$v)$st->bindValue(':'.$k,$v); $st->bindValue(':lim',$limit,PDO::PARAM_INT); $st->bindValue(':off',$offset,PDO::PARAM_INT); $st->execute();
        return ['rows'=>$st->fetchAll(),'total'=>$total];
    }

    public function recorders(): array
    {
        return $this->pdo->query("SELECT recorded_by FROM (SELECT recorded_by FROM debts UNION SELECT recorded_by FROM payments) t ORDER BY 1")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function addMember(string $name, ?string $phone): void
    {
        $this->pdo->prepare('INSERT INTO members(name, phone) VALUES (?, ?)')->execute([$name, $phone]);
    }

    public function addDebt(int $memberId, int $amount, ?string $note, string $by, string $date): void
    {
        $this->pdo->prepare('INSERT INTO debts(member_id, amount, note, recorded_by, debt_date) VALUES (?, ?, ?, ?, ?)')
            ->execute([$memberId, $amount, $note, $by, $date]);
    }

    public function addPayment(int $memberId, int $amount, ?string $note, string $by): void
    {
        $this->pdo->prepare('INSERT INTO payments(member_id, amount, note, recorded_by) VALUES (?, ?, ?, ?)')
            ->execute([$memberId, $amount, $note, $by]);
    }

    /** Batalkan transaksi hutang tanpa menghapus riwayatnya. */
    public function cancelDebt(int $id, string $reason): bool
    {
        $stmt = $this->pdo->prepare('UPDATE debts SET cancelled_at = now(), cancel_reason = ? WHERE id = ? AND cancelled_at IS NULL');
        $stmt->execute([$reason, $id]);
        return $stmt->rowCount() > 0;
    }

    /** Batal lunas: pelunasan ditandai batal (tidak dihapus), status hutang dihitung ulang. */
    public function cancelPayment(int $id): void
    {
        $this->pdo->prepare('UPDATE payments SET cancelled_at = now() WHERE id = ? AND cancelled_at IS NULL')->execute([$id]);
    }

    public function payments(int $memberId): array
    {
        $s = $this->pdo->prepare('SELECT * FROM payments WHERE member_id = ? ORDER BY created_at DESC, id DESC');
        $s->execute([$memberId]);
        return $s->fetchAll();
    }

    public function remaining(int $memberId): int
    {
        $s = $this->pdo->prepare('SELECT
            (SELECT COALESCE(SUM(amount), 0) FROM debts WHERE member_id = :m AND cancelled_at IS NULL) -
            (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE member_id = :m AND cancelled_at IS NULL)');
        $s->execute(['m' => $memberId]);
        return (int)$s->fetchColumn();
    }

    /** Alokasi pelunasan ke hutang dari tanggal terlama (FIFO) + status tiap hutang. */
    public function debtsWithStatus(int $memberId): array
    {
        $s = $this->pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE member_id = ? AND cancelled_at IS NULL');
        $s->execute([$memberId]);
        $pool = (int)$s->fetchColumn();

        $s = $this->pdo->prepare('SELECT * FROM debts WHERE member_id = ? ORDER BY debt_date, created_at, id');
        $s->execute([$memberId]);
        $rows = $s->fetchAll();
        foreach ($rows as &$d) {
            if ($d['cancelled_at'] !== null) {
                $d['paid'] = 0; $d['sisa'] = (int)$d['amount']; $d['status'] = 'dibatalkan';
                continue;
            }
            $used = min($pool, (int)$d['amount']);
            $pool -= $used;
            $d['paid'] = $used;
            $d['sisa'] = (int)$d['amount'] - $used;
            $d['status'] = $d['sisa'] === 0 ? 'lunas' : ($used > 0 ? 'sebagian' : 'belum');
        }
        return $rows;
    }
}
