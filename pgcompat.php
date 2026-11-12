<?php
/**
 * Couche de compatibilité : permet au code écrit pour mysqli de fonctionner avec PostgreSQL (PDO).
 * Utilisée pour l'hébergement sur Vercel avec une base Neon. En local / sur ByetHost, mysqli est utilisé directement.
 *
 * Traductions automatiques du SQL MySQL : CURDATE(), FIELD(), LIKE (insensible à la casse),
 * chaînes échappées avec addslashes(), INSERT IGNORE, SHOW TABLES LIKE.
 */

if (!defined('MYSQLI_ASSOC')) define('MYSQLI_ASSOC', 1);
if (!defined('MYSQLI_NUM'))   define('MYSQLI_NUM', 2);
if (!defined('MYSQLI_BOTH'))  define('MYSQLI_BOTH', 3);

class PgCompatConn
{
    public PDO $pdo;
    public $connect_error = null;
    public $error = '';
    public $insert_id = 0;
    public $affected_rows = 0;
    private array $pending = [];

    public function __construct(string $url)
    {
        $p = parse_url($url);
        parse_str($p['query'] ?? '', $q);
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $p['host'], $p['port'] ?? 5432, ltrim($p['path'] ?? '/postgres', '/'), $q['sslmode'] ?? 'require');
        // Neon : indique le serveur visé, pour les bibliothèques PostgreSQL sans SNI.
        if (preg_match('/^(ep-[a-z0-9-]+?)(-pooler)?\./', $p['host'], $m)) {
            $dsn .= ";options='endpoint={$m[1]}'";
        }
        try {
            $this->pdo = new PDO($dsn, urldecode($p['user'] ?? ''), urldecode($p['pass'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => true,   // compatible avec le pooler de connexions Neon
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $this->connect_error = $e->getMessage();
            error_log('Connexion PostgreSQL impossible : ' . preg_replace('/password=\S+/', 'password=***', $e->getMessage()));
        }
    }

    public static function translate(string $sql): string
    {
        // Chaînes échappées avec addslashes() : \' → '' , \" → " , \\ → \
        $sql = preg_replace_callback('/\\\\([\'"\\\\])/', fn($m) => $m[1] === "'" ? "''" : $m[1], $sql);
        $sql = str_replace('`', '"', $sql);
        $sql = preg_replace('/\bCURDATE\(\)/i', 'CURRENT_DATE', $sql);
        $sql = preg_replace('/\bNOW\(\)/i', 'CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace_callback('/\bFIELD\(\s*([^,]+?)\s*,([^)]*)\)/i',
            fn($m) => "array_position(ARRAY[{$m[2]}]::text[], ({$m[1]})::text)", $sql);
        $sql = preg_replace('/\bLIKE\b/', 'ILIKE', $sql);
        if (preg_match('/^\s*INSERT\s+IGNORE\s+/i', $sql)) {
            $sql = preg_replace('/^\s*INSERT\s+IGNORE\s+/i', 'INSERT ', $sql) . ' ON CONFLICT DO NOTHING';
        }
        if (preg_match("/^\s*SHOW\s+TABLES\s+ILIKE\s+'([^']+)'/i", $sql, $m)) {
            $sql = "SELECT tablename FROM pg_tables WHERE schemaname = current_schema() AND tablename = '{$m[1]}'";
        }
        return $sql;
    }

    public function query(string $sql)
    {
        try {
            $st = $this->pdo->query(self::translate($sql));
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('SQL: ' . $this->error);
            return false;
        }
        $this->affected_rows = $st->rowCount();
        if ($st->columnCount() > 0) return new PgCompatResult($st->fetchAll(PDO::FETCH_ASSOC));
        $this->updateInsertId($sql);
        return true;
    }

    public function prepare(string $sql)
    {
        return new PgCompatStmt($this, self::translate($sql), $sql);
    }

    public function updateInsertId(string $sql): void
    {
        if (preg_match('/^\s*INSERT\s+(IGNORE\s+)?INTO\s+[`"]?(\w+)/i', $sql, $m)) {
            try { $this->insert_id = (int) $this->pdo->query("SELECT currval(pg_get_serial_sequence('{$m[2]}', 'id'))")->fetchColumn(); }
            catch (PDOException $e) { /* table sans séquence */ }
        }
    }

    public function multi_query(string $sql): bool
    {
        try { $this->pdo->exec($sql); return true; }
        catch (PDOException $e) { $this->error = $e->getMessage(); error_log('SQL: ' . $this->error); return false; }
    }
    public function more_results(): bool { return false; }
    public function next_result(): bool { return false; }
    public function set_charset($c): bool { return true; }
    public function real_escape_string(string $s): string { return addslashes($s); }
    public function close(): bool { return true; }
}

class PgCompatResult
{
    public int $num_rows;
    private int $i = 0;
    public function __construct(private array $rows) { $this->num_rows = count($rows); }
    public function fetch_assoc() { return $this->rows[$this->i++] ?? null; }
    public function fetch_row() { $r = $this->rows[$this->i++] ?? null; return $r === null ? null : array_values($r); }
    public function fetch_array($mode = MYSQLI_BOTH)
    {
        $r = $this->rows[$this->i++] ?? null;
        if ($r === null) return null;
        if ($mode === MYSQLI_ASSOC) return $r;
        if ($mode === MYSQLI_NUM) return array_values($r);
        return $r + array_values($r);
    }
    public function fetch_all($mode = MYSQLI_NUM)
    {
        return $mode === MYSQLI_ASSOC ? $this->rows : array_map('array_values', $this->rows);
    }
    public function free(): void {}
    public function close(): void {}
}

class PgCompatStmt
{
    public int $num_rows = 0;
    public $insert_id = 0;
    public $error = '';
    private array $refs = [];
    private ?PDOStatement $st = null;
    private ?array $rows = null;

    public function __construct(private PgCompatConn $conn, private string $sql, private string $orig) {}

    public function bind_param(string $types, &...$vars): bool
    {
        $this->refs = [];
        foreach ($vars as $k => &$v) $this->refs[$k] = &$v;
        return true;
    }

    public function execute(): bool
    {
        try {
            $this->st = $this->conn->pdo->prepare($this->sql);
            $i = 1;
            foreach ($this->refs as &$v) {
                $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $this->st->bindValue($i++, is_float($v) ? (string) $v : $v, $type);
            }
            $this->st->execute();
        } catch (PDOException $e) {
            $this->error = $this->conn->error = $e->getMessage();
            error_log('SQL: ' . $this->error);
            return false;
        }
        $this->rows = $this->st->columnCount() > 0 ? $this->st->fetchAll(PDO::FETCH_ASSOC) : null;
        $this->num_rows = $this->rows ? count($this->rows) : 0;
        if ($this->rows === null) { $this->conn->updateInsertId($this->orig); $this->insert_id = $this->conn->insert_id; }
        return true;
    }

    public function get_result() { return new PgCompatResult($this->rows ?? []); }
    public function store_result(): bool { return true; }
    public function close(): bool { return true; }
}

/** Sessions PHP enregistrées dans la base (les fonctions serverless n'ont pas de disque partagé). */
class PgSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo) {}
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }
    public function read($id): string|false
    {
        $st = $this->pdo->prepare('SELECT data FROM php_sessions WHERE id = ?');
        $st->execute([$id]);
        return (string) ($st->fetchColumn() ?: '');
    }
    public function write($id, $data): bool
    {
        $st = $this->pdo->prepare('INSERT INTO php_sessions (id, data, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, updated_at = CURRENT_TIMESTAMP');
        return $st->execute([$id, $data]);
    }
    public function destroy($id): bool { return $this->pdo->prepare('DELETE FROM php_sessions WHERE id = ?')->execute([$id]); }
    public function gc($max): int|false
    {
        $st = $this->pdo->prepare("DELETE FROM php_sessions WHERE updated_at < CURRENT_TIMESTAMP - make_interval(secs => ?)");
        $st->execute([$max]);
        return $st->rowCount();
    }
}
