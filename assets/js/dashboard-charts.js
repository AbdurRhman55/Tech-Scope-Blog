/**
 *  Admin dashboard — analytics charts (Chart.js v4)
 *  Data is embedded server-side as window.__TS_STATS__ by admin/index.php.
 *  All colors match the Tailwind theme tokens (source.css).
 */
(function () {
  'use strict';

  var D = window.__TS_STATS__;
  if (!window.Chart || !D) return;

  var ACCENT = '#e8402a';
  var PRIMARY = '#1f4fd8';
  var INK = '#101319';
  var MUTED = '#6b7280';
  var LINE = 'rgba(229, 225, 216, 0.65)';
  var FONT = getComputedStyle(document.body).fontFamily ||
    "'Manrope', ui-sans-serif, system-ui, sans-serif";

  Chart.defaults.font.family = FONT;
  Chart.defaults.font.size = 11;
  Chart.defaults.color = MUTED;
  Chart.defaults.animation.duration = 600;
  Chart.defaults.animation.easing = 'easeOutQuart';

  var TOOLTIP = {
    backgroundColor: INK,
    titleColor: '#f7f5f0',
    bodyColor: 'rgba(247, 245, 240, 0.85)',
    padding: 10,
    cornerRadius: 8,
    boxPadding: 4,
    displayColors: true
  };

  function compact(v) {
    if (v >= 1000000) return (v / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (v >= 1000) return (v / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
    return '' + v;
  }

  function gridY() {
    return { color: LINE, drawTicks: false, border: { display: false } };
  }

  function legendTop() {
    return {
      display: true,
      position: 'top',
      align: 'end',
      labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 16, color: MUTED, font: { weight: '600' } }
    };
  }

  function trunc(s, n) {
    return s.length > n ? s.slice(0, n - 1) + '…' : s;
  }

  /* ---------------- 1. Traffic (30 days, area + dashed previous period) --- */
  var trafficEl = document.getElementById('traffic-chart');
  if (trafficEl) {
    new Chart(trafficEl, {
      type: 'line',
      data: {
        labels: D.labels,
        datasets: [
          {
            label: 'Pageviews',
            data: D.pageviews,
            borderColor: ACCENT,
            backgroundColor: 'rgba(232, 64, 42, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointHoverBackgroundColor: ACCENT,
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
          },
          {
            label: 'Visitors',
            data: D.visitors,
            borderColor: PRIMARY,
            backgroundColor: 'transparent',
            fill: false,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointHoverBackgroundColor: PRIMARY,
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
          },
          {
            label: 'Previous period',
            data: D.prev,
            borderColor: '#9ca3af',
            backgroundColor: 'transparent',
            fill: false,
            borderDash: [5, 4],
            borderWidth: 1.5,
            pointRadius: 0,
            pointHoverRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: legendTop(),
          tooltip: Object.assign({}, TOOLTIP, {
            callbacks: {
              label: function (c) {
                return ' ' + c.dataset.label + ': ' + Number(c.parsed.y).toLocaleString();
              }
            }
          })
        },
        scales: {
          x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 8, autoSkip: true, maxRotation: 0 } },
          y: Object.assign(gridY(), {
            beginAtZero: true,
            ticks: { precision: 0, callback: compact, padding: 8 }
          })
        }
      }
    });
  }

  /* ---------------- 2. Comment health (donut) ----------------------------- */
  var commentEl = document.getElementById('comment-chart');
  if (commentEl) {
    var cm = D.comments || {};
    var cmData = [cm.approved || 0, cm.pending || 0, cm.spam || 0, cm.trash || 0];
    var cmTotal = cmData.reduce(function (a, b) { return a + b; }, 0);

    var centerText = {
      id: 'centerText',
      afterDraw: function (chart) {
        var area = chart.chartArea;
        if (!area) return;
        var ctx = chart.ctx;
        var x = (area.left + area.right) / 2;
        var y = (area.top + area.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = INK;
        ctx.font = '700 24px ' + FONT;
        ctx.fillText(cmTotal.toLocaleString(), x, y - 8);
        ctx.fillStyle = MUTED;
        ctx.font = '600 10px ' + FONT;
        ctx.fillText('COMMENTS', x, y + 14);
        ctx.restore();
      }
    };

    new Chart(commentEl, {
      type: 'doughnut',
      data: {
        labels: ['Approved', 'Pending', 'Spam', 'Trash'],
        datasets: [{
          data: cmData,
          backgroundColor: ['#059669', '#d97706', '#e11d48', '#9ca3af'],
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 14, color: MUTED, font: { weight: '600' } }
          },
          tooltip: Object.assign({}, TOOLTIP, {
            callbacks: {
              label: function (c) {
                return ' ' + c.label + ': ' + Number(c.parsed).toLocaleString();
              }
            }
          })
        }
      },
      plugins: [centerText]
    });
  }

  /* ---------------- 3. Top articles this period (horizontal bar) ---------- */
  var topEl = document.getElementById('top-posts-chart');
  if (topEl && D.top_posts && D.top_posts.length) {
    new Chart(topEl, {
      type: 'bar',
      data: {
        labels: D.top_posts.map(function (p) { return trunc(p.title, 42); }),
        datasets: [{
          label: 'Views',
          data: D.top_posts.map(function (p) { return p.views; }),
          backgroundColor: ACCENT,
          borderRadius: 6,
          barPercentage: 0.72,
          categoryPercentage: 0.8,
          maxBarThickness: 22
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: Object.assign({}, TOOLTIP, {
            callbacks: {
              title: function (items) {
                return items.length ? D.top_posts[items[0].dataIndex].title : '';
              },
              label: function (c) {
                return ' ' + Number(c.parsed.x).toLocaleString() + ' views';
              }
            }
          })
        },
        scales: {
          x: Object.assign(gridY(), { beginAtZero: true, ticks: { precision: 0, callback: compact } }),
          y: { grid: { display: false }, border: { display: false }, ticks: { color: INK, font: { weight: '600' } } }
        }
      }
    });
  }

  /* ---------------- 4. Reads per category (vertical bar) ------------------ */
  var catEl = document.getElementById('category-chart');
  if (catEl && D.categories && D.categories.length) {
    new Chart(catEl, {
      type: 'bar',
      data: {
        labels: D.categories.map(function (c) { return trunc(c.name, 16); }),
        datasets: [{
          label: 'Reads',
          data: D.categories.map(function (c) { return c.views; }),
          backgroundColor: PRIMARY,
          borderRadius: 6,
          barPercentage: 0.66,
          maxBarThickness: 34
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: Object.assign({}, TOOLTIP, {
            callbacks: {
              title: function (items) {
                return items.length ? D.categories[items[0].dataIndex].name : '';
              },
              label: function (c) {
                return ' ' + Number(c.parsed.y).toLocaleString() + ' reads';
              }
            }
          })
        },
        scales: {
          x: { grid: { display: false }, border: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 0, font: { size: 10 } } },
          y: Object.assign(gridY(), { beginAtZero: true, ticks: { precision: 0, callback: compact, padding: 8 } })
        }
      }
    });
  }

  /* ---------------- 5. Publishing cadence (12 months, mini bar) ----------- */
  var cadenceEl = document.getElementById('cadence-chart');
  if (cadenceEl && D.months) {
    new Chart(cadenceEl, {
      type: 'bar',
      data: {
        labels: D.months.labels,
        datasets: [{
          label: 'Published',
          data: D.months.counts,
          backgroundColor: PRIMARY,
          borderRadius: 4,
          barPercentage: 0.62,
          maxBarThickness: 18
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: Object.assign({}, TOOLTIP, {
            callbacks: {
              label: function (c) {
                return ' ' + Number(c.parsed.y) + ' post' + (c.parsed.y === 1 ? '' : 's');
              }
            }
          })
        },
        scales: {
          x: { grid: { display: false }, border: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 6, font: { size: 9.5 } } },
          y: Object.assign(gridY(), { beginAtZero: true, ticks: { precision: 0, maxTicksLimit: 5 } })
        }
      }
    });
  }
})();
