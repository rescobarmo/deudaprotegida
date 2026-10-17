SET @desde = DATE_SUB(CURDATE(), INTERVAL 29 DAY);
SET @hasta = CURDATE();

SELECT
  COUNT(*) AS total_registros,
  COUNT(DISTINCT id) AS clientes_unicos,
  SUM(fecha = CURDATE()) AS leads_hoy,
  SUM(DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS leads_mes,
  MIN(fecha) AS primera_fecha,
  MAX(fecha) AS ultima_fecha,
  ROUND(COUNT(*) / GREATEST(DATEDIFF(MAX(fecha), MIN(fecha)) + 1, 1), 2) AS promedio_diario
FROM datos_deudaprotegina;

SELECT
  fecha,
  COUNT(*) AS leads,
  COUNT(DISTINCT id) AS clientes_unicos
FROM datos_deudaprotegina
WHERE fecha BETWEEN @desde AND @hasta
GROUP BY fecha
ORDER BY fecha;

WITH RECURSIVE fechas AS (
  SELECT @desde AS fecha
  UNION ALL
  SELECT DATE_ADD(fecha, INTERVAL 1 DAY)
  FROM fechas
  WHERE fecha < @hasta
),
agregado AS (
  SELECT
    fecha,
    COUNT(*) AS leads,
    COUNT(DISTINCT id) AS clientes_unicos
  FROM datos_deudaprotegina
  WHERE fecha BETWEEN @desde AND @hasta
  GROUP BY fecha
)
SELECT
  f.fecha,
  COALESCE(a.leads, 0) AS leads,
  COALESCE(a.clientes_unicos, 0) AS clientes_unicos
FROM fechas f
LEFT JOIN agregado a ON a.fecha = f.fecha
ORDER BY f.fecha;

WITH diario AS (
  SELECT
    fecha,
    COUNT(*) AS leads
  FROM datos_deudaprotegina
  WHERE fecha BETWEEN @desde AND @hasta
  GROUP BY fecha
)
SELECT
  fecha,
  leads,
  SUM(leads) OVER (ORDER BY fecha ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS acumulado
FROM diario
ORDER BY fecha;

SELECT
  DATE_FORMAT(fecha, '%Y-%m-01') AS mes,
  COUNT(*) AS leads,
  COUNT(DISTINCT id) AS clientes_unicos
FROM datos_deudaprotegina
WHERE fecha BETWEEN @desde AND @hasta
GROUP BY DATE_FORMAT(fecha, '%Y-%m-01')
ORDER BY mes;

WITH mensual AS (
  SELECT
    DATE_FORMAT(fecha, '%Y-%m-01') AS mes,
    COUNT(*) AS leads
  FROM datos_deudaprotegina
  GROUP BY DATE_FORMAT(fecha, '%Y-%m-01')
)
SELECT
  mes,
  leads,
  LAG(leads) OVER (ORDER BY mes) AS mes_anterior,
  ROUND(
    100 * (leads - LAG(leads) OVER (ORDER BY mes)) / NULLIF(LAG(leads) OVER (ORDER BY mes), 0),
    1
  ) AS variacion_pct
FROM mensual
ORDER BY mes;

WITH base AS (
  SELECT
    id,
    fecha,
    TRIM(BOTH '|' FROM REGEXP_REPLACE(REPLACE(monto, '.', ''), '[^0-9]+', '|')) AS monto_normalizado
  FROM datos_deudaprotegina
  WHERE fecha BETWEEN @desde AND @hasta
),
rangos AS (
  SELECT
    id,
    CASE
      WHEN monto_normalizado = '' THEN 'sin_monto'
      WHEN CAST(SUBSTRING_INDEX(monto_normalizado, '|', -1) AS UNSIGNED) <= 5000000 THEN 'hasta_5m'
      WHEN CAST(SUBSTRING_INDEX(monto_normalizado, '|', -1) AS UNSIGNED) <= 10000000 THEN '5m_10m'
      WHEN CAST(SUBSTRING_INDEX(monto_normalizado, '|', -1) AS UNSIGNED) <= 20000000 THEN '10m_20m'
      WHEN CAST(SUBSTRING_INDEX(monto_normalizado, '|', -1) AS UNSIGNED) <= 30000000 THEN '20m_30m'
      WHEN CAST(SUBSTRING_INDEX(monto_normalizado, '|', -1) AS UNSIGNED) <= 50000000 THEN '30m_50m'
      ELSE 'mas_50m'
    END AS rango
  FROM base
)
SELECT
  rango,
  COUNT(*) AS leads,
  COUNT(DISTINCT id) AS clientes_unicos,
  ROUND(100 * COUNT(*) / SUM(COUNT(*)) OVER (), 1) AS participacion_pct
FROM rangos
GROUP BY rango
ORDER BY FIELD(rango, 'hasta_5m', '5m_10m', '10m_20m', '20m_30m', '30m_50m', 'mas_50m', 'sin_monto');

SELECT
  monto,
  COUNT(*) AS leads,
  COUNT(DISTINCT id) AS clientes_unicos
FROM datos_deudaprotegina
WHERE fecha BETWEEN @desde AND @hasta
GROUP BY monto
ORDER BY leads DESC, monto
LIMIT 25;

SELECT
  id,
  nombre,
  numero,
  monto,
  otro,
  fecha
FROM datos_deudaprotegina
WHERE fecha BETWEEN @desde AND @hasta
ORDER BY fecha DESC, id ASC
LIMIT 50;

SELECT
  id,
  nombre,
  numero,
  monto,
  fecha
FROM datos_deudaprotegina
WHERE TRIM(BOTH '|' FROM REGEXP_REPLACE(REPLACE(monto, '.', ''), '[^0-9]+', '|')) = ''
ORDER BY fecha DESC
LIMIT 50;

SELECT
  id,
  COUNT(*) AS registros,
  COUNT(DISTINCT fecha) AS fechas_distintas
FROM datos_deudaprotegina
GROUP BY id
HAVING COUNT(*) > 1
ORDER BY registros DESC;

SELECT
  id,
  nombre,
  numero,
  fecha
FROM datos_deudaprotegina
WHERE numero NOT REGEXP '^[0-9+() -]{8,25}$'
   OR numero IS NULL
ORDER BY fecha DESC
LIMIT 100;

SELECT
  SUM(otro IS NULL OR TRIM(otro) = '') AS sin_estado,
  COUNT(*) AS total,
  ROUND(100 * SUM(otro IS NULL OR TRIM(otro) = '') / COUNT(*), 1) AS sin_estado_pct
FROM datos_deudaprotegina;

SELECT
  id,
  nombre,
  fecha
FROM datos_deudaprotegina
WHERE fecha > CURDATE()
ORDER BY fecha;
