<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$search = trim((string)($_GET['search'] ?? ''));

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT id, name FROM foods WHERE name LIKE :q ORDER BY name ASC LIMIT 50");
        $stmt->execute([':q' => "%{$search}%"]);
    } else {
        $stmt = $pdo->query("SELECT id, name FROM foods ORDER BY name ASC LIMIT 200");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_ok([
        'foods' => array_map(fn($r) => ['id' => (int)$r['id'], 'name' => (string)$r['name']], $rows),
    ]);

} catch (\Throwable $e) {
    json_error(500, 'Could not fetch foods');
}

