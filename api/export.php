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

$parts = leads_query_parts($bucket, $range['from'], $range['to'], $q, $id);
$countRow = db_one(
    "SELECT COUNT(*) AS total
     FROM ({$parts['source']}) t
     WHERE {$parts['where']}",
    $parts['params']
);
$total = (int) ($countRow['total'] ?? 0);
if ($total > 50000) {
    fail('El resultado supera 50.000 registros. Refina los filtros antes de exportar.', 422);
}

$bucketExpression = monto_bucket_expr('t');
$rows = db_all(
    "SELECT t.id, t.nombre, t.numero, t.monto, t.judicializado, t.ingresos, t.fecha,
            {$bucketExpression} AS monto_bucket
     FROM ({$parts['source']}) t
     WHERE {$parts['where']}
     ORDER BY t.fecha DESC, t.id ASC",
    $parts['params']
);

$filename = 'leads_deudaprotegida_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'wb');
if ($output === false) {
    throw new RuntimeException('No se pudo generar la exportación.');
}

fwrite($output, "\xEF\xBB\xBF");

$buckets = monto_buckets();
$header = ['ID', 'Nombre', 'WhatsApp', 'Monto', 'Rango', 'Judicializado', 'Ingresos', 'Fecha'];
fputcsv($output, $header, ';', '"', '');

$sanitize = static function (?string $value): string {
    $value = (string) $value;
    if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
        return "'" . $value;
    }
    return $value;
};

foreach ($rows as $row) {
    $bucketKey = (string) $row['monto_bucket'];
    fputcsv($output, [
        $sanitize((string) (int) $row['id']),
        $sanitize(trim((string) $row['nombre'])),
        $sanitize(trim((string) $row['numero'])),
        $sanitize(trim((string) $row['monto'])),
        $buckets[$bucketKey]['label'] ?? 'Sin monto',
        $sanitize(trim((string) $row['judicializado'])),
        $sanitize(trim((string) $row['ingresos'])),
        $sanitize(trim((string) $row['fecha'])),
    ], ';', '"', '');
}

fclose($output);
exit;
