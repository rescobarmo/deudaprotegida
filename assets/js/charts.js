import { compactNumber, formatDayShort, formatMonthShort, formatNumber } from "./format.js";

export const BUCKET_COLORS = {
  hasta_5m: "#38bdf8",
  "5m_10m": "#2dd4bf",
  "10m_20m": "#818cf8",
  "20m_30m": "#a78bfa",
  "30m_50m": "#f59e0b",
  mas_50m: "#f97316",
  sin_monto: "#94a3b8",
};

let trendChart = null;
let distributionChart = null;

function destroyTrend() {
  if (trendChart) {
    try {
      trendChart.destroy();
    } catch {
      trendChart = null;
    }
    trendChart = null;
  }
}

function destroyDistribution() {
  if (distributionChart) {
    try {
      distributionChart.destroy();
    } catch {
      distributionChart = null;
    }
    distributionChart = null;
  }
}

export function destroyCharts() {
  destroyTrend();
  destroyDistribution();
}

function palette(dark) {
  return {
    axis: dark ? "#94a3b8" : "#64748b",
    grid: dark ? "#1e293b" : "#e2e8f0",
    strong: dark ? "#f8fafc" : "#0f172a",
    card: dark ? "#0f172a" : "#ffffff",
    tooltip: dark ? "dark" : "light",
    leads: dark ? "#2dd4bf" : "#0d9488",
    clients: dark ? "#a5b4fc" : "#6366f1",
  };
}

export function renderTrendChart(element, series, granularity, dark) {
  destroyTrend();
  if (!element || !Array.isArray(series) || series.length === 0) return;

  const colors = palette(dark);
  const categories = series.map((point) =>
    granularity === "month" ? formatMonthShort(point.period) : formatDayShort(point.period)
  );

  const options = {
    chart: {
      type: "line",
      height: 320,
      fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif",
      foreColor: colors.axis,
      background: "transparent",
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: {
        enabled: true,
        speed: 450,
        animateGradually: { enabled: true, delay: 40 },
        dynamicAnimation: { enabled: false },
      },
      parentHeightOffset: 0,
    },
    series: [
      {
        name: "Leads",
        type: "area",
        data: series.map((point) => point.total),
      },
      {
        name: "Clientes únicos",
        type: "line",
        data: series.map((point) => point.clientes),
      },
    ],
    colors: [colors.leads, colors.clients],
    stroke: {
      curve: "smooth",
      width: [2.5, 2],
      dashArray: [0, 5],
      lineCap: "round",
    },
    fill: {
      type: "gradient",
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.3,
        opacityTo: 0.02,
        stops: [0, 92],
      },
    },
    markers: {
      size: 0,
      strokeWidth: 2,
      hover: { size: 4 },
    },
    dataLabels: { enabled: false },
    grid: {
      borderColor: colors.grid,
      strokeDashArray: 4,
      padding: { top: 4, right: 12, bottom: 0, left: 8 },
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } },
    },
    xaxis: {
      categories,
      axisBorder: { show: false },
      axisTicks: { show: false },
      tickAmount: Math.min(8, Math.max(2, Math.floor(categories.length / 4))),
      labels: {
        rotate: 0,
        hideOverlappingLabels: true,
        trim: true,
        style: { fontSize: "10px", colors: colors.axis, fontWeight: 500 },
      },
      crosshairs: {
        show: true,
        width: 1,
        stroke: { color: colors.grid, width: 1, dashArray: 4 },
      },
      tooltip: { enabled: false },
    },
    yaxis: {
      min: 0,
      forceNiceScale: true,
      labels: {
        formatter: (value) => compactNumber(value),
        style: { fontSize: "10px", colors: colors.axis },
      },
    },
    legend: {
      show: true,
      position: "top",
      horizontalAlign: "right",
      offsetY: -4,
      fontSize: "11px",
      fontWeight: 500,
      labels: { colors: colors.axis },
      markers: { size: 5, strokeWidth: 0, shape: "circle", offsetY: 1 },
      itemMargin: { horizontal: 12 },
    },
    tooltip: {
      shared: true,
      intersect: false,
      theme: colors.tooltip,
      x: { show: true },
      y: {
        formatter: (value, context) => {
          const singular = Number(value) === 1;
          if (context?.seriesIndex === 1) {
            return `${formatNumber(value)} ${singular ? "cliente único" : "clientes únicos"}`;
          }
          return `${formatNumber(value)} ${singular ? "lead" : "leads"}`;
        },
      },
    },
    responsive: [
      {
        breakpoint: 560,
        options: {
          chart: { height: 280 },
          legend: {
            horizontalAlign: "left",
            offsetY: 0,
            itemMargin: { horizontal: 8 },
          },
          xaxis: {
            tickAmount: 4,
            labels: { style: { fontSize: "9px" } },
          },
        },
      },
    ],
  };

  trendChart = new window.ApexCharts(element, options);
  trendChart.render();
}

export function renderDistributionChart(element, distribution, dark) {
  destroyDistribution();
  if (!element || !Array.isArray(distribution) || distribution.length === 0) return;

  const colors = palette(dark);
  const labels = distribution.map((item) => item.label);
  const values = distribution.map((item) => item.total);
  const colorList = distribution.map((item) => BUCKET_COLORS[item.key] || colors.clients);
  const total = values.reduce((sum, value) => sum + Number(value || 0), 0);

  const options = {
    chart: {
      type: "donut",
      height: 264,
      fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif",
      foreColor: colors.axis,
      background: "transparent",
      animations: { enabled: true, speed: 450 },
      parentHeightOffset: 0,
    },
    labels,
    series: values,
    colors: colorList,
    stroke: { width: 2, colors: [colors.card] },
    plotOptions: {
      pie: {
        expandOnClick: false,
        donut: {
          size: "70%",
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: "11px",
              fontFamily: "Inter, sans-serif",
              fontWeight: 600,
              color: colors.axis,
              offsetY: -10,
            },
            value: {
              show: true,
              fontSize: "24px",
              fontFamily: "Manrope, Inter, sans-serif",
              fontWeight: 800,
              color: colors.strong,
              offsetY: 6,
              formatter: (value) => formatNumber(value),
            },
            total: {
              show: true,
              showAlways: true,
              label: "Leads",
              fontSize: "11px",
              fontFamily: "Inter, sans-serif",
              fontWeight: 600,
              color: colors.axis,
              formatter: () => formatNumber(total),
            },
          },
        },
      },
    },
    dataLabels: { enabled: false },
    legend: { show: false },
    tooltip: {
      theme: colors.tooltip,
      y: {
        formatter: (value) => `${formatNumber(value)} ${Number(value) === 1 ? "lead" : "leads"}`,
      },
    },
    states: {
      hover: { filter: { type: "lighten", value: 0.06 } },
      active: { filter: { type: "none" } },
    },
  };

  distributionChart = new window.ApexCharts(element, options);
  distributionChart.render();
}
