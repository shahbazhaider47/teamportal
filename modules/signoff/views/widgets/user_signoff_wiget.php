<?php
  // Safe default if controller didn’t pass it for any reason
  $progress_chart = $progress_chart ?? ['labels'=>[], 'targets'=>[], 'achieved'=>[], 'range'=>['start'=>date('Y-m-01'),'end'=>date('Y-m-t')]];
?>
<!-- ===== User Progress by Form (Monthly) ===== -->
<div class="card mt-4">
  <div class="d-flex justify-content-between align-items-center p-3">
    <span class="h6 mb-0">
      My Progress by Form
      <small class="text-muted">(<?= html_escape($progress_chart['range']['start']) ?> → <?= html_escape($progress_chart['range']['end']) ?>)</small>
    </span>
  </div>
  <div class="card-body">
    <?php if (!empty($progress_chart['labels'])): ?>
      <div class="row">
        <div class="col-12">
          <canvas id="myTargetsProgressChart" height="120"></canvas>
        </div>
      </div>
    <?php else: ?>
      <div class="text-muted text-center py-3">
        No targets found for the current period.
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($progress_chart['labels'])): ?>
  <!-- Chart.js (use your asset pipeline if you already include it) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    (function() {
      var data = <?= json_encode($progress_chart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      var ctx  = document.getElementById('myTargetsProgressChart').getContext('2d');

      // Build datasets
      var chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Achieved',
              data: data.achieved,
              borderWidth: 1
            },
            {
              label: 'Target',
              data: data.targets,
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top' },
            tooltip: {
              mode: 'index',
              intersect: false,
              callbacks: {
                label: function(ctx) {
                  var v = ctx.parsed.y;
                  return ctx.dataset.label + ': ' + (typeof v === 'number' ? v.toLocaleString() : v);
                }
              }
            },
            title: {
              display: false
            }
          },
          interaction: { mode: 'index', intersect: false },
          scales: {
            x: {
              ticks: { maxRotation: 0, autoSkip: true }
            },
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) { return Number(value).toLocaleString(); }
              }
            }
          }
        }
      });
    })();
  </script>
<?php endif; ?>