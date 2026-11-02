<?php
declare(strict_types=1);

/** Semua query & aturan hutang/pelunasan ada di sini. */
final class DebtRepository
{
    public function __construct(private PDO $pdo) {}

    public function members(): array
    {
        return $this->pdo->query("
            SELECT m.*,
              COALESCE((SELECT SUM(amount) FROM debts WHERE member_id = m.id), 0) AS total_debt,
              COALESCE((SELECT SUM(amount) FROM payments WHERE member_id = m.id AND cancelled_at IS NULL), 0) AS total_paid,
              (SELECT MAX(debt_date) FROM debts WHERE member_id = m.id) AS last_debt
            FROM members m ORDER BY m.name")->fetchAll();
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
            (SELECT COALESCE(SUM(amount), 0) FROM debts WHERE member_id = :m) -
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
            $used = min($pool, (int)$d['amount']);
            $pool -= $used;
            $d['paid'] = $used;
            $d['sisa'] = (int)$d['amount'] - $used;
            $d['status'] = $d['sisa'] === 0 ? 'lunas' : ($used > 0 ? 'sebagian' : 'belum');
        }
        return $rows;
    }
}
