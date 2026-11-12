<?php
// Photos des chambres : dossier uploads/chambres quand il est accessible en écriture (local, ByetHost),
// sinon dans la base de données (hébergement serverless, ex. Vercel).
const MEDIA_DIR = 'uploads/chambres';

function media_in_db(): bool
{
    global $conn;
    if (!($conn instanceof PgCompatConn)) return false;
    if (getenv('VERCEL')) return true;   // disque en lecture seule sur Vercel
    return !(is_dir(MEDIA_DIR) ? is_writable(MEDIA_DIR) : is_writable(dirname(MEDIA_DIR)));
}

function media_save(string $tmp, string $name): bool
{
    global $conn;
    if (!media_in_db()) {
        if (!is_dir(MEDIA_DIR)) @mkdir(MEDIA_DIR, 0755, true);
        return move_uploaded_file($tmp, MEDIA_DIR . '/' . $name);
    }
    if (!is_uploaded_file($tmp) || filesize($tmp) > 3 * 1024 * 1024) return false;
    $mime = mime_content_type($tmp) ?: 'image/jpeg';
    $st = $conn->pdo->prepare('INSERT INTO images (name, mime, data) VALUES (?, ?, ?) ON CONFLICT (name) DO NOTHING');
    return $st->execute([$name, $mime, base64_encode(file_get_contents($tmp))]);
}

function media_url(?string $name): ?string
{
    global $conn;
    if (!$name) return null;
    if (is_file(MEDIA_DIR . '/' . $name)) return MEDIA_DIR . '/' . $name;
    if ($conn instanceof PgCompatConn) {
        $st = $conn->pdo->prepare('SELECT 1 FROM images WHERE name = ?');
        $st->execute([$name]);
        if ($st->fetchColumn()) return 'image.php?f=' . rawurlencode($name);
    }
    return null;
}

function media_delete(?string $name): void
{
    global $conn;
    if (!$name) return;
    if (is_file(MEDIA_DIR . '/' . $name)) { @unlink(MEDIA_DIR . '/' . $name); return; }
    if ($conn instanceof PgCompatConn) $conn->pdo->prepare('DELETE FROM images WHERE name = ?')->execute([$name]);
}
