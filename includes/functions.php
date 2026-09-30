<?php
/**
 * CivicFind – includes/functions.php
 * Shared helper utilities.
 */

/* ── HTML escaping ─────────────────────────────────────── */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ── Internal URL paths ────────────────────────────────── */
function app_base_path(): string {
    static $basePath = null;
    if ($basePath !== null) return $basePath;

    $configured = trim(env_value('APP_BASE_PATH'));
    if ($configured !== '') {
        $basePath = '/' . trim($configured, '/');
        return $basePath === '/' ? '' : $basePath;
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
    foreach (['/admin', '/auth', '/user'] as $section) {
        if ($directory === $section) {
            $directory = '';
            break;
        }
        if (str_ends_with($directory, $section)) {
            $directory = substr($directory, 0, -strlen($section));
            break;
        }
    }

    $basePath = ($directory === '' || $directory === '.' || $directory === '/') ? '' : $directory;
    return $basePath;
}

function app_url(string $path = '/'): string {
    $path = '/' . ltrim($path, '/');
    return app_base_path() . $path;
}

/* ── CSRF protection ───────────────────────────────────── */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

/* ── Flash messages ────────────────────────────────────── */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $t = e($f['type']);
        $m = e($f['message']);
        $html .= "<div class=\"flash flash-{$t}\"><span>{$m}</span><button class=\"flash-close\" onclick=\"this.parentElement.remove()\">&times;</button></div>";
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ── Relative time ─────────────────────────────────────── */
function time_ago(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 1) return $diff->d . ' days ago';
    if ($diff->d === 1) return 'Yesterday';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/* ── Image upload ──────────────────────────────────────── */
function upload_item_image(array $file): string|false {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) return false;

    $ext  = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    };
    $name = uniqid('item_', true) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return 'uploads/' . $name;
}

/* ── Badges ────────────────────────────────────────────── */
function type_badge(string $type): string {
    $cls = $type === 'found' ? 'badge-found' : 'badge-lost';
    return '<span class="badge ' . $cls . '">' . strtoupper(e($type)) . '</span>';
}

function status_badge(string $status): string {
    $map = [
        'active'   => 'badge-active',
        'claimed'  => 'badge-claimed',
        'returned' => 'badge-found',
        'closed'   => 'badge-closed',
        'pending'  => 'badge-pending',
        'approved' => 'badge-found',
        'rejected' => 'badge-lost',
    ];
    $cls = $map[$status] ?? 'badge-active';
    return '<span class="badge ' . $cls . '">' . strtoupper(e($status)) . '</span>';
}

/* ── Text helpers ──────────────────────────────────────── */
function truncate_text(string $text, int $length = 100): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

/* ── Categories ────────────────────────────────────────── */
function get_categories(PDO $db): array {
    return $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

/* ── Pagination helper ─────────────────────────────────── */
function paginate(int $total, int $per_page, int $current): array {
    $pages = max(1, (int) ceil($total / $per_page));
    $current = max(1, min($current, $pages));
    $offset  = ($current - 1) * $per_page;
    return [
        'total'    => $total,
        'per_page' => $per_page,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => $offset,
    ];
}

/* ── Category icon mapping ─────────────────────────────── */
function category_icon(string $name): string {
    $map = [
        'Electronics'    => '📱',
        'Documents'      => '📄',
        'Wallet / Money' => '👛',
        'Keys'           => '🔑',
        'Clothing'       => '👕',
        'Bags'           => '👜',
        'Jewelry'        => '💍',
        'Books'          => '📚',
        'Accessories'    => '🎒',
        'Other'          => '📦',
    ];
    return $map[$name] ?? '📦';
}
