<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$bucket = param_string('monto', 'all') ?? 'all';
if ($bucket !== 'all' && !array_key_exists($bucket, monto_buckets())) {
    fail('Filtro de monto inválido.', 422);
}

$from = param_string('from');
$to = param_string('to');
$all = param_bool('all');
$range = resolve_range($bucket, $from, $to, $all);

$q = mb_substr(param_string('q', '') ?? '', 0, 100);
$id = mb_substr(param_string('id', '') ?? '', 0, 30);
$page = param_int('page', 1, 1, 100000);
$perPage = param_int('per_page', 10, 1, 100);

$sort = param_string('sort', 'fecha') ?? 'fecha';
$direction = strtolower(param_string('dir', 'desc') ?? 'desc');

$sortColumns = [
    'fecha' => 't.fecha',
    'nombre' => 't.nombre',
    'id' => 't.id',
    'monto' => 't.monto_max',
    'judicializado' => 't.judicializado',
    'ingresos' => 't.ingresos',
];
if (!array_key_exists($sort, $sortColumns)) {
    fail('Criterio de orden inválido.', 422);
}
if (!in_array($direction, ['asc', 'desc'], true)) {
    fail('Dirección de orden inválida.', 422);
}

$parts = leads_query_parts($bucket, $range['from'], $range['to'], $q, $id);

$countRow = db_one(
    "SELECT COUNT(*) AS total
     FROM ({$parts['source']}) t
     WHERE {$parts['where']}",
    $parts['params']
);
$total = (int) ($countRow['total'] ?? 0);

$pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
if ($pages > 0) {
    $page = min($page, $pages);
}
$offset = ($page - 1) * $perPage;

$orderBy = $sortColumns[$sort] . ' ' . strtoupper($direction) . ', t.id ASC';
$bucketExpression = monto_bucket_expr('t');

$rows = db_all(
    "SELECT t.id, t.nombre, t.numero, t.monto, t.judicializado, t.ingresos, t.fecha, t.monto_min, t.monto_max,
            {$bucketExpression} AS monto_bucket
     FROM ({$parts['source']}) t
     WHERE {$parts['where']}
     ORDER BY {$orderBy}
     LIMIT {$perPage} OFFSET {$offset}",
    $parts['params']
);

$buckets = monto_buckets();
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['nombre'] = trim((string) $row['nombre']);
    $row['numero'] = trim((string) $row['numero']);
    $row['monto'] = trim((string) $row['monto']);
    $row['judicializado'] = trim((string) $row['judicializado']);
    $row['ingresos'] = trim((string) $row['ingresos']);
    $row['monto_min'] = $row['monto_min'] === null ? null : (int) $row['monto_min'];
    $row['monto_max'] = $row['monto_max'] === null ? null : (int) $row['monto_max'];
    $row['monto_bucket_label'] = $buckets[$row['monto_bucket']]['label'] ?? 'Sin monto';
}
unset($row);

json_response([
    'ok' => true,
    'data' => [
        'rows' => $rows,
        'meta' => [
            'total' => $total,
            'page' => $total === 0 ? 1 : $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'from' => $range['from'],
            'to' => $range['to'],
            'mode' => $range['is_all'] ? 'all' : 'custom',
            'sort' => $sort,
            'dir' => $direction,
            'q' => $q,
            'id' => $id,
            'monto' => $bucket,
        ],
    ],
]);
