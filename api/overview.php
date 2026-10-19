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

$granularity = param_string('granularity', 'auto') ?? 'auto';
if (!in_array($granularity, ['auto', 'day', 'month'], true)) {
    fail('Granularidad inválida.', 422);
}
if ($granularity === 'auto') {
    $granularity = $range['days'] > 120 ? 'month' : 'day';
}
if ($granularity === 'day' && $range['days'] > 730) {
    $granularity = 'month';
}

$today = new DateTimeImmutable('today');
$todayString = $today->format('Y-m-d');
$monthStart = $today->format('Y-m-01');
$monthEnd = $today->format('Y-m-t');

$condition = monto_bucket_condition($bucket);
$table = quote_table(resolve_leads_table());
$periodSource = source_sql($range['from'], $range['to']);
$previousTotal = null;
$calendarParams = [
    ':today' => $todayString,
    ':month_start' => $monthStart,
    ':month_end' => $monthEnd,
];

if ($bucket === 'all') {
    $historical = db_one("SELECT COUNT(*) AS total, COUNT(DISTINCT id) AS clientes FROM {$table}")
        ?? ['total' => 0, 'clientes' => 0];

    $period = db_one(
        "SELECT COUNT(*) AS total, COUNT(DISTINCT id) AS clientes
         FROM {$table}
         WHERE fecha BETWEEN :period_from AND :period_to",
        [':period_from' => $range['from'], ':period_to' => $range['to']]
    ) ?? ['total' => 0, 'clientes' => 0];

    if (!$range['is_all']) {
        $previous = db_one(
            "SELECT COUNT(*) AS total
             FROM {$table}
             WHERE fecha BETWEEN :previous_from AND :previous_to",
            [':previous_from' => $range['previous_from'], ':previous_to' => $range['previous_to']]
        );
        $previousTotal = (int) ($previous['total'] ?? 0);
    }

    $calendar = db_one(
        "SELECT
            COALESCE(SUM(CASE WHEN fecha = :today THEN 1 ELSE 0 END), 0) AS hoy,
            COALESCE(SUM(CASE WHEN fecha BETWEEN :month_start AND :month_end THEN 1 ELSE 0 END), 0) AS mes
         FROM {$table}",
        $calendarParams
    ) ?? ['hoy' => 0, 'mes' => 0];
} else {
    $historicalSource = source_sql();
    $historical = db_one(
        "SELECT COUNT(*) AS total, COUNT(DISTINCT t.id) AS clientes
         FROM ({$historicalSource['sql']}) t
         WHERE {$condition}",
        $historicalSource['params']
    ) ?? ['total' => 0, 'clientes' => 0];

    $period = db_one(
        "SELECT COUNT(*) AS total, COUNT(DISTINCT t.id) AS clientes
         FROM ({$periodSource['sql']}) t
         WHERE {$condition}",
        $periodSource['params']
    ) ?? ['total' => 0, 'clientes' => 0];

    if (!$range['is_all']) {
        $previousSource = source_sql($range['previous_from'], $range['previous_to']);
        $previous = db_one(
            "SELECT COUNT(*) AS total
             FROM ({$previousSource['sql']}) t
             WHERE {$condition}",
            $previousSource['params']
        );
        $previousTotal = (int) ($previous['total'] ?? 0);
    }

    $calendar = db_one(
        "SELECT
            COALESCE(SUM(CASE WHEN t.fecha = :today THEN 1 ELSE 0 END), 0) AS hoy,
            COALESCE(SUM(CASE WHEN t.fecha BETWEEN :month_start AND :month_end THEN 1 ELSE 0 END), 0) AS mes
         FROM ({$historicalSource['sql']}) t
         WHERE {$condition}",
        array_merge($historicalSource['params'], $calendarParams)
    ) ?? ['hoy' => 0, 'mes' => 0];
}

$bucketExpression = monto_bucket_expr('t');
$distributionRows = db_all(
    "SELECT b.bucket, COUNT(*) AS total
     FROM (
        SELECT {$bucketExpression} AS bucket
        FROM ({$periodSource['sql']}) t
        WHERE {$condition}
     ) b
     GROUP BY b.bucket",
    $periodSource['params']
);

$distributionMap = [];
foreach ($distributionRows as $row) {
    $distributionMap[(string) $row['bucket']] = (int) $row['total'];
}

$distribution = [];
$principal = null;
$periodTotal = (int) ($period['total'] ?? 0);
foreach (monto_buckets() as $key => $meta) {
    $count = $distributionMap[$key] ?? 0;
    if ($count === 0) {
        continue;
    }
    $item = [
        'key' => $key,
        'label' => $meta['label'],
        'total' => $count,
        'pct' => $periodTotal > 0 ? round(($count / $periodTotal) * 100, 1) : 0,
    ];
    $distribution[] = $item;
    if ($principal === null || $count > $principal['total']) {
        $principal = $item;
    }
}

$periodKeyExpression = $granularity === 'day'
    ? "DATE_FORMAT(t.fecha, '%Y-%m-%d')"
    : "DATE_FORMAT(t.fecha, '%Y-%m-01')";

if ($bucket === 'all') {
    $trendRows = db_all(
        "SELECT {$periodKeyExpression} AS periodo, COUNT(*) AS total, COUNT(DISTINCT t.id) AS clientes
         FROM {$table} t
         WHERE t.fecha BETWEEN :trend_from AND :trend_to
         GROUP BY periodo
         ORDER BY periodo",
        [':trend_from' => $range['from'], ':trend_to' => $range['to']]
    );
} else {
    $trendRows = db_all(
        "SELECT {$periodKeyExpression} AS periodo, COUNT(*) AS total, COUNT(DISTINCT t.id) AS clientes
         FROM ({$periodSource['sql']}) t
         WHERE {$condition}
         GROUP BY periodo
         ORDER BY periodo",
        $periodSource['params']
    );
}

$trendMap = [];
foreach ($trendRows as $row) {
    $trendMap[(string) $row['periodo']] = [
        'total' => (int) $row['total'],
        'clientes' => (int) $row['clientes'],
    ];
}

$series = [];
$fromDate = new DateTimeImmutable($range['from'] . ' 12:00:00');
$toDate = new DateTimeImmutable($range['to'] . ' 12:00:00');

if ($granularity === 'day') {
    $cursor = $fromDate;
    $last = $toDate;
} else {
    $cursor = $fromDate->modify('first day of this month');
    $last = $toDate->modify('last day of this month');
}

$guard = 0;
while ($cursor <= $last && $guard < 2000) {
    $key = $cursor->format($granularity === 'day' ? 'Y-m-d' : 'Y-m-01');
    $series[] = [
        'period' => $key,
        'total' => $trendMap[$key]['total'] ?? 0,
        'clientes' => $trendMap[$key]['clientes'] ?? 0,
    ];
    $cursor = $cursor->modify($granularity === 'day' ? '+1 day' : '+1 month');
    $guard++;
}

$comparisonAvailable = !$range['is_all'] && $previousTotal !== null;
$variation = null;
if ($comparisonAvailable) {
    if ($previousTotal === 0) {
        $variation = $periodTotal > 0 ? null : 0.0;
    } else {
        $variation = round((($periodTotal - $previousTotal) / $previousTotal) * 100, 1);
    }
}

$montoOptions = [];
foreach (monto_buckets() as $key => $meta) {
    $montoOptions[] = ['key' => $key, 'label' => $meta['label']];
}

json_response([
    'ok' => true,
    'data' => [
        'kpis' => [
            'total_historico' => (int) ($historical['total'] ?? 0),
            'clientes_historicos' => (int) ($historical['clientes'] ?? 0),
            'periodo' => $periodTotal,
            'clientes_periodo' => (int) ($period['clientes'] ?? 0),
            'periodo_anterior' => $previousTotal,
            'comparison_available' => $comparisonAvailable,
            'variacion_pct' => $variation,
            'hoy' => (int) ($calendar['hoy'] ?? 0),
            'mes' => (int) ($calendar['mes'] ?? 0),
            'promedio_diario' => $range['days'] > 0 ? round($periodTotal / $range['days'], 2) : 0,
            'monto_principal' => $principal,
        ],
        'trend' => [
            'granularity' => $granularity,
            'series' => $series,
            'total' => $periodTotal,
        ],
        'distribution' => $distribution,
        'filters' => [
            'monto' => $bucket,
            'montos' => $montoOptions,
        ],
        'range' => [
            'from' => $range['from'],
            'to' => $range['to'],
            'days' => $range['days'],
            'previous_from' => $range['previous_from'],
            'previous_to' => $range['previous_to'],
            'mode' => $range['is_all'] ? 'all' : 'custom',
            'timezone' => app_config()['timezone'],
        ],
        'generated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ],
]);
