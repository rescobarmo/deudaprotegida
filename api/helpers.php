<?php
declare(strict_types=1);

function monto_buckets(): array
{
    return [
        'hasta_5m' => ['label' => 'Hasta $5M'],
        '5m_10m' => ['label' => '$5M – $10M'],
        '10m_20m' => ['label' => '$10M – $20M'],
        '20m_30m' => ['label' => '$20M – $30M'],
        '30m_50m' => ['label' => '$30M – $50M'],
        'mas_50m' => ['label' => 'Más de $50M'],
        'sin_monto' => ['label' => 'Sin monto'],
    ];
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400, ?array $details = null): never
{
    $error = ['message' => $message];
    if ($details !== null) {
        $error['details'] = $details;
    }
    json_response(['ok' => false, 'error' => $error], $status);
}

function param_string(string $key, ?string $default = null, int $maxLength = 200): ?string
{
    if (!array_key_exists($key, $_GET)) {
        return $default;
    }
    $value = $_GET[$key];
    if (!is_string($value)) {
        fail("Parámetro inválido: {$key}.", 422);
    }
    $value = trim($value);
    if ($value === '') {
        return $default;
    }
    if (mb_strlen($value) > $maxLength) {
        fail("El parámetro {$key} excede el largo permitido.", 422);
    }
    return $value;
}

function param_int(string $key, int $default, int $min, int $max): int
{
    if (!array_key_exists($key, $_GET) || $_GET[$key] === '') {
        return $default;
    }
    $value = filter_var($_GET[$key], FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        fail("El parámetro {$key} debe estar entre {$min} y {$max}.", 422);
    }
    return (int) $value;
}

function param_bool(string $key): bool
{
    if (!array_key_exists($key, $_GET)) {
        return false;
    }
    return filter_var($_GET[$key], FILTER_VALIDATE_BOOLEAN);
}

function assert_date(string $value, string $label): DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        fail("{$label} no es una fecha válida (YYYY-MM-DD).", 422);
    }
    return $date;
}

function quote_table(string $table): string
{
    return '`' . str_replace('`', '', $table) . '`';
}

function resolve_leads_table(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $candidates = [];
    foreach (app_config()['table_candidates'] as $candidate) {
        if (is_string($candidate) && preg_match('/^[A-Za-z0-9_]+$/', $candidate)) {
            $candidates[] = $candidate;
        }
    }
    $candidates = array_values(array_unique($candidates));
    if ($candidates === []) {
        throw new RuntimeException('No hay una tabla de leads configurada.');
    }

    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $stmt = db()->prepare(
        "SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name IN ({$placeholders})"
    );
    $stmt->execute($candidates);
    $found = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $found, true)) {
            $resolved = $candidate;
            return $resolved;
        }
    }

    throw new RuntimeException('No se encontró la tabla de leads en la base de datos configurada.');
}

function monto_normalized_expr(string $alias = 'd'): string
{
    return "TRIM(BOTH '|' FROM REGEXP_REPLACE(REPLACE({$alias}.monto, '.', ''), '[^0-9]+', '|'))";
}

function monto_min_expr(string $alias = 'd'): string
{
    $normalized = monto_normalized_expr($alias);
    return "CAST(NULLIF(SUBSTRING_INDEX({$normalized}, '|', 1), '') AS UNSIGNED)";
}

function monto_max_expr(string $alias = 'd'): string
{
    $normalized = monto_normalized_expr($alias);
    return "CAST(NULLIF(SUBSTRING_INDEX({$normalized}, '|', -1), '') AS UNSIGNED)";
}

function monto_bucket_expr(string $alias = 't'): string
{
    return "CASE
        WHEN {$alias}.monto_max IS NULL OR {$alias}.monto_max = 0 THEN 'sin_monto'
        WHEN {$alias}.monto_max <= 5000000 THEN 'hasta_5m'
        WHEN {$alias}.monto_max <= 10000000 THEN '5m_10m'
        WHEN {$alias}.monto_max <= 20000000 THEN '10m_20m'
        WHEN {$alias}.monto_max <= 30000000 THEN '20m_30m'
        WHEN {$alias}.monto_max <= 50000000 THEN '30m_50m'
        ELSE 'mas_50m'
    END";
}

function monto_bucket_condition(string $bucket, string $alias = 't'): string
{
    return match ($bucket) {
        'all' => '1=1',
        'sin_monto' => "({$alias}.monto_max IS NULL OR {$alias}.monto_max = 0)",
        'hasta_5m' => "({$alias}.monto_max > 0 AND {$alias}.monto_max <= 5000000)",
        '5m_10m' => "({$alias}.monto_max > 5000000 AND {$alias}.monto_max <= 10000000)",
        '10m_20m' => "({$alias}.monto_max > 10000000 AND {$alias}.monto_max <= 20000000)",
        '20m_30m' => "({$alias}.monto_max > 20000000 AND {$alias}.monto_max <= 30000000)",
        '30m_50m' => "({$alias}.monto_max > 30000000 AND {$alias}.monto_max <= 50000000)",
        'mas_50m' => "({$alias}.monto_max > 50000000)",
        default => throw new InvalidArgumentException('Rango de monto inválido.'),
    };
}

function source_sql(?string $from = null, ?string $to = null): array
{
    $table = quote_table(resolve_leads_table());
    $where = [];
    $params = [];

    if ($from !== null && $to !== null) {
        $where[] = 'd.fecha BETWEEN :src_from AND :src_to';
        $params[':src_from'] = $from;
        $params[':src_to'] = $to;
    }

    $sql = "SELECT
                   TRIM(d.id) AS id,
                   TRIM(d.nombre) AS nombre,
                   TRIM(d.numero) AS numero,
                   TRIM(d.monto) AS monto,
                   TRIM(d.otro) AS otro,
                   d.fecha,
                   " . monto_min_expr('d') . " AS monto_min,
                   " . monto_max_expr('d') . " AS monto_max
            FROM {$table} d";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    return ['sql' => $sql, 'params' => $params];
}

function resolve_range(string $bucket, ?string $from, ?string $to, bool $all): array
{
    $today = new DateTimeImmutable('today');

    if ($all) {
        if ($bucket === 'all') {
            $row = db_one(
                'SELECT MIN(fecha) AS min_fecha, MAX(fecha) AS max_fecha FROM ' . quote_table(resolve_leads_table())
            );
        } else {
            $source = source_sql();
            $condition = monto_bucket_condition($bucket);
            $row = db_one(
                "SELECT MIN(t.fecha) AS min_fecha, MAX(t.fecha) AS max_fecha
                 FROM ({$source['sql']}) t
                 WHERE {$condition}",
                $source['params']
            );
        }

        if ($row === null || $row['min_fecha'] === null || $row['max_fecha'] === null) {
            $fromDate = $today;
            $toDate = $today;
        } else {
            $fromDate = assert_date((string) $row['min_fecha'], 'Fecha mínima');
            $toDate = assert_date((string) $row['max_fecha'], 'Fecha máxima');
        }

        $days = (int) $fromDate->diff($toDate)->days + 1;
        return [
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
            'days' => $days,
            'previous_from' => null,
            'previous_to' => null,
            'is_all' => true,
        ];
    }

    if (($from === null) !== ($to === null)) {
        fail('Debes indicar la fecha desde y la fecha hasta.', 422);
    }

    if ($from === null && $to === null) {
        $fromDate = $today->modify('-29 days');
        $toDate = $today;
    } else {
        $fromDate = assert_date((string) $from, 'Fecha desde');
        $toDate = assert_date((string) $to, 'Fecha hasta');
    }

    if ($fromDate > $toDate) {
        fail('La fecha desde no puede ser mayor que la fecha hasta.', 422);
    }

    $days = (int) $fromDate->diff($toDate)->days + 1;
    $previousTo = $fromDate->modify('-1 day');
    $previousFrom = $previousTo->modify('-' . ($days - 1) . ' days');

    return [
        'from' => $fromDate->format('Y-m-d'),
        'to' => $toDate->format('Y-m-d'),
        'days' => $days,
        'previous_from' => $previousFrom->format('Y-m-d'),
        'previous_to' => $previousTo->format('Y-m-d'),
        'is_all' => false,
    ];
}

function like_escape(string $value): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
}

function leads_query_parts(string $bucket, string $from, string $to, string $q = '', string $rut = ''): array
{
    $source = source_sql($from, $to);
    $where = [monto_bucket_condition($bucket)];
    $params = $source['params'];

    if ($q !== '') {
        $where[] = "t.nombre LIKE :q ESCAPE '!'";
        $params[':q'] = '%' . like_escape($q) . '%';
    }

    if ($rut !== '') {
        $rutDigits = preg_replace('/[^0-9kK]/', '', $rut);
        $where[] = "(t.id LIKE :rut ESCAPE '!'
                     OR REPLACE(REPLACE(REPLACE(t.id, '.', ''), '-', ''), ' ', '') LIKE :rut_digits ESCAPE '!')";
        $params[':rut'] = '%' . like_escape($rut) . '%';
        $params[':rut_digits'] = '%' . like_escape((string) $rutDigits) . '%';
    }

    return [
        'source' => $source['sql'],
        'where' => implode(' AND ', $where),
        'params' => $params,
    ];
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
