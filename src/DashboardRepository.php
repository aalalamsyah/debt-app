<?php
declare(strict_types=1);

/** Query agregasi khusus dashboard. Tidak mengubah aturan transaksi/FIFO. */
final class DashboardRepository
{
    public function __construct(private PDO $pdo) {}

    /** Ringkasan periode: hutang baru, pembayaran valid, saldo pada akhir periode, dan member bersaldo. */
    public function getSummary(string $from, string $to): array
    {
        $sql = <<<'SQL'
            WITH debt_period AS (
                SELECT COALESCE(SUM(amount), 0) AS total
                FROM debts WHERE debt_date >= CAST(:debt_from AS date) AND debt_date <= CAST(:debt_to AS date)
            ), payment_period AS (
                SELECT COALESCE(SUM(amount), 0) AS total
                FROM payments
                WHERE created_at::date >= CAST(:payment_from AS date)
                  AND created_at::date <= CAST(:payment_to AS date)
                  AND cancelled_at IS NULL
            ), debt_until AS (
                SELECT member_id, SUM(amount) AS total
                FROM debts WHERE debt_date <= CAST(:balance_debt_to AS date)
                GROUP BY member_id
            ), payment_until AS (
                SELECT member_id, SUM(amount) AS total
                FROM payments
                WHERE created_at::date <= CAST(:balance_payment_to AS date)
                  AND (cancelled_at IS NULL OR cancelled_at::date > CAST(:balance_payment_to AS date))
                GROUP BY member_id
            ), balances AS (
                SELECT m.id,
                       COALESCE(d.total, 0) - COALESCE(p.total, 0) AS balance
                FROM members m
                LEFT JOIN debt_until d ON d.member_id = m.id
                LEFT JOIN payment_until p ON p.member_id = m.id
            )
            SELECT (SELECT total FROM debt_period) AS debt_period,
                   (SELECT total FROM payment_period) AS payment_period,
                   COALESCE((SELECT SUM(balance) FROM balances WHERE balance > 0), 0) AS outstanding,
                   (SELECT COUNT(*) FROM balances WHERE balance > 0) AS debtors,
                   (SELECT COUNT(*) FROM members) AS members
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['debt_from' => $from, 'debt_to' => $to, 'payment_from' => $from, 'payment_to' => $to, 'balance_debt_to' => $to, 'balance_payment_to' => $to]);
        $row = $stmt->fetch() ?: [];
        return [
            'debt_period' => (int)($row['debt_period'] ?? 0),
            'payment_period' => (int)($row['payment_period'] ?? 0),
            'outstanding' => (int)($row['outstanding'] ?? 0),
            'debtors' => (int)($row['debtors'] ?? 0),
            'members' => (int)($row['members'] ?? 0),
        ];
    }

    /** Deret tren harian hingga 62 hari; lebih panjang dikelompokkan per bulan. */
    public function getTrend(string $from, string $to): array
    {
        $days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
        if ($days <= 62) {
            $sql = <<<'SQL'
                WITH calendar AS (
                    SELECT generate_series(CAST(:cal_from AS date), CAST(:cal_to AS date), interval '1 day')::date AS period
                ), d AS (
                    SELECT debt_date AS period, SUM(amount) AS total FROM debts
                    WHERE debt_date >= CAST(:debt_from AS date) AND debt_date <= CAST(:debt_to AS date)
                    GROUP BY debt_date
                ), p AS (
                    SELECT created_at::date AS period, SUM(amount) AS total FROM payments
                    WHERE created_at::date >= CAST(:payment_from AS date) AND created_at::date <= CAST(:payment_to AS date)
                      AND cancelled_at IS NULL
                    GROUP BY created_at::date
                )
                SELECT to_char(c.period, 'DD Mon') AS label,
                       COALESCE(d.total, 0) AS debt, COALESCE(p.total, 0) AS payment
                FROM calendar c LEFT JOIN d USING (period) LEFT JOIN p USING (period)
                ORDER BY c.period
            SQL;
        } else {
            $sql = <<<'SQL'
                WITH calendar AS (
                    SELECT generate_series(date_trunc('month', CAST(:cal_from AS date)), date_trunc('month', CAST(:cal_to AS date)), interval '1 month')::date AS period
                ), d AS (
                    SELECT date_trunc('month', debt_date)::date AS period, SUM(amount) AS total FROM debts
                    WHERE debt_date >= CAST(:debt_from AS date) AND debt_date <= CAST(:debt_to AS date)
                    GROUP BY 1
                ), p AS (
                    SELECT date_trunc('month', created_at)::date AS period, SUM(amount) AS total FROM payments
                    WHERE created_at::date >= CAST(:payment_from AS date) AND created_at::date <= CAST(:payment_to AS date)
                      AND cancelled_at IS NULL
                    GROUP BY 1
                )
                SELECT to_char(c.period, 'Mon YYYY') AS label,
                       COALESCE(d.total, 0) AS debt, COALESCE(p.total, 0) AS payment
                FROM calendar c LEFT JOIN d USING (period) LEFT JOIN p USING (period)
                ORDER BY c.period
            SQL;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cal_from' => $from, 'cal_to' => $to, 'debt_from' => $from, 'debt_to' => $to, 'payment_from' => $from, 'payment_to' => $to]);
        return array_map(static fn(array $r): array => [
            'label' => (string)$r['label'], 'debt' => (int)$r['debt'], 'payment' => (int)$r['payment'],
        ], $stmt->fetchAll());
    }

    /** Member dengan saldo terbesar pada tanggal akhir periode. */
    public function getTopDebtors(string $to, int $limit = 5): array
    {
        $sql = <<<'SQL'
            WITH d AS (
                SELECT member_id, SUM(amount) AS total FROM debts
                WHERE debt_date <= CAST(:debt_to AS date) GROUP BY member_id
            ), p AS (
                SELECT member_id, SUM(amount) AS total FROM payments
                WHERE created_at::date <= CAST(:payment_to AS date)
                  AND (cancelled_at IS NULL OR cancelled_at::date > CAST(:payment_to AS date))
                GROUP BY member_id
            )
            SELECT m.id, m.name, COALESCE(d.total, 0) AS total_debt,
                   COALESCE(p.total, 0) AS total_paid,
                   GREATEST(COALESCE(d.total, 0) - COALESCE(p.total, 0), 0) AS balance
            FROM members m LEFT JOIN d ON d.member_id = m.id LEFT JOIN p ON p.member_id = m.id
            WHERE COALESCE(d.total, 0) - COALESCE(p.total, 0) > 0
            ORDER BY balance DESC, m.name ASC LIMIT :limit
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':debt_to', $to);
        $stmt->bindValue(':payment_to', $to);
        $stmt->bindValue(':limit', max(1, min($limit, 20)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Aktivitas baru dari hutang dan pembayaran, termasuk pembayaran yang dibatalkan. */
    public function getRecentActivities(int $limit = 8): array
    {
        $sql = <<<'SQL'
            SELECT * FROM (
                SELECT d.created_at AS happened_at, 'debt' AS kind, m.id AS member_id,
                       m.name AS member_name, d.amount, d.note, d.recorded_by, NULL::timestamptz AS cancelled_at
                FROM debts d JOIN members m ON m.id = d.member_id
                UNION ALL
                SELECT p.created_at AS happened_at, 'payment' AS kind, m.id AS member_id,
                       m.name AS member_name, p.amount, p.note, p.recorded_by, p.cancelled_at
                FROM payments p JOIN members m ON m.id = p.member_id
            ) activity
            ORDER BY happened_at DESC LIMIT :limit
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', max(1, min($limit, 20)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
