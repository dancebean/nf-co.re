<?php
// Build the HTML for a pipeline documentation page.
// Imported by public_html/pipeline.php - pulls a markdown file from GitHub and renders.
$import_chartjs = true;

// Sidebar for pipeline homepage with key stats

// Get number of open issues and PRs
$issues_json_fn = dirname(dirname(dirname(__FILE__))).'/nfcore_issue_stats.json';
$issues_json = json_decode(file_get_contents($issues_json_fn), true);
$num_issues = count($issues_json['repos'][$pipeline->name]['issues']);
$num_prs = count($issues_json['repos'][$pipeline->name]['prs']);

// Get number of clones over time
$stats_json_fn = dirname(dirname(dirname(__FILE__))).'/nfcore_stats.json';
$stats_json = json_decode(file_get_contents($stats_json_fn), true);
$stats = $stats_json['pipelines'][$pipeline->name]['repo_metrics'][ $stats_json['updated'] ];
$clones_counts = $stats_json['pipelines'][$pipeline->name]['clones_count'];
$total_clones = 0;
$clones_since = false;
foreach($clones_counts as $datetime => $count){
  $total_clones += $count;
  if(!$clones_since) $clones_since = strtotime($datetime);
  $clones_since = min($clones_since, strtotime($datetime));
}

// Get contributor avatars
$contrib_avatars = [];
foreach($stats_json['pipelines'][$pipeline->name]['contributors'] as $contributor){
  $contrib_avatars[
    '<a href="'.$contributor['author']['html_url'].'" title="@'.$contributor['author']['login'].', '.$contributor['total'].' contributions" data-toggle="tooltip"><img src="'.$contributor['author']['avatar_url'].'"></a>'
  ] = $contributor['total'];
}
arsort($contrib_avatars);

// Last release and last commit
$last_release = 'N/A';
$release_cmd = '';
if(count($pipeline->releases) > 0){
  $last_release = time_ago($pipeline->releases[0]->published_at);
  $release_cmd = ' -r '.$pipeline->releases[0]->tag_name;
}
$last_commit = time_ago($pipeline->updated_at);

ob_start();
?>

<div class="pipeline-sidebar">
  <h6><i class="fas fa-terminal fa-xs"></i> command</h6>
  <div class="border pipeline-run-cmd p-1">
    <code class="small">&raquo; nextflow run <?php echo $pipeline->full_name; echo $release_cmd; ?> -profile test</code>
  </div>

  <h6><i class="fas fa-arrow-down fa-xs"></i> <span id="clones_header">clones in last <?php echo time_ago($clones_since, false); ?></span></h6>
  <div class="row border-bottom">
    <div class="col-6">
      <p id="clones_count"><?php echo $total_clones; ?></p>
    </div>
    <div class="col-6" style="overflow: none;">
      <canvas id="clones_plot" height="70"></canvas>
    </div>
  </div>

  <div class="row border-bottom">
    <div class="col-6">
      <h6>stars</h6>
      <p><a href="/<?php echo $pipeline->name;?>/stargazers"><?php echo $stats['stargazers_count']; ?></a></p>
    </div>
    <div class="col-6">
      <h6>watchers</h6>
      <p><a href="/<?php echo $pipeline->name;?>/watchers"><?php echo $stats['subscribers_count']; ?></a></p>
    </div>
  </div>

  <div class="row border-bottom">
    <div class="col-6">
      <h6>last release</h6>
      <p><a href="/<?php echo $pipeline->name;?>/releases"><?php echo $last_release; ?></a></p>
    </div>
    <div class="col-6">
      <h6>last updated</h6>
      <p><?php echo $last_commit; ?></p>
    </div>
  </div>

  <div class="row border-bottom">
    <div class="col-6">
      <h6>open issues</h6>
      <p><a href="<?php echo $pipeline->html_url; ?>/issues"><?php echo $num_issues; ?></a></p>
    </div>
    <div class="col-6">
      <h6>pull requests</h6>
      <p><a href="<?php echo $pipeline->html_url; ?>/pulls"><?php echo $num_prs; ?></a></p>
    </div>
  </div>

  <div class="border-bottom">
    <h6>collaborators</h6>
    <p class="contrib-avatars"><?php echo implode(array_keys($contrib_avatars)); ?></p>
  </div>

  <h6>get in touch</h6>
  <p><a class="btn btn-sm btn-outline-info" href="https://nfcore.slack.com/channels/<?php echo $pipeline->name; ?>">ask a question on Slack</a></p>
  <p><a class="btn btn-sm btn-outline-secondary" href="<?php echo $pipeline->html_url; ?>/issues">open an issue on GitHub</a></p>

</div>

<script type="text/javascript">
$(function(){
  // Plot hover vertical line
  // https://stackoverflow.com/a/45172506/713980
  Chart.defaults.LineWithLine = Chart.defaults.line;
  Chart.controllers.LineWithLine = Chart.controllers.line.extend({
     draw: function(ease) {
        Chart.controllers.line.prototype.draw.call(this, ease);

        if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
           var activePoint = this.chart.tooltip._active[0],
               ctx = this.chart.ctx,
               x = activePoint.tooltipPosition().x,
               topY = this.chart.scales['y-axis-0'].top,
               bottomY = this.chart.scales['y-axis-0'].bottom;

           // draw line
           ctx.save();
           ctx.beginPath();
           ctx.moveTo(x, topY);
           ctx.lineTo(x, bottomY);
           ctx.lineWidth = 1;
           ctx.strokeStyle = '#999';
           ctx.stroke();
           ctx.restore();
        } else {
          $('#clones_header').text('clones in last <?php echo time_ago($clones_since, false); ?>');
          $('#clones_count').text('<?php echo $total_clones; ?>');
        }
     }
  });

  // Make the plot
  var ctx = document.getElementById('clones_plot').getContext('2d');
  new Chart(ctx, {
    data: {
      datasets: [
        {
          backgroundColor: 'rgba(84, 171, 106, 0.2)',
          borderColor: 'rgba(84, 171, 106, 1)',
          pointRadius: 0,
          pointHoverBorderColor: 'rgba(84, 171, 106, 0)', // transparent
          pointHoverBackgroundColor: 'rgba(84, 171, 106, 0)', // transparent
          data: [
            <?php
            foreach($clones_counts as $datetime => $count){
              echo '{ x: "'.date('Y-m-d', strtotime($datetime)).'", y: '.$count.' },'."\n\t\t\t";
            }
            ?>
            ]
          }
      ],
    },
    type: 'LineWithLine',
    options: {
      onClick: function(e){
        window.location.href = '/<?php echo $pipeline->name; ?>/stats';
      },
      elements: {
        point: {
          radius: 0,
          hitRadius: 3,
          hoverRadius: 3
        },
        line: {
          borderWidth: 2,
          tension: 0 // disables bezier curves
        }
      },
      scales: {
        xAxes: [{
          type: 'time',
          display: false
        }],
        yAxes: [{
          display: false
        }],
      },
      legend: {
        display: false
      },
      tooltips: {
        enabled: false,
        mode: 'x',
        intersect: false,
        custom: function(tooltipModel) {
          tooltipModel.opacity = 0
        },
        callbacks: {
          // Use the footer callback to display the sum of the items showing in the tooltip
          footer: function(tooltipItems, data) {
            $('#clones_header').text('clones - '+tooltipItems[0]['label']);
            $('#clones_count').text(tooltipItems[0]['value']);
          },
        }
      },
    }
  });
});
</script>

<?php
$pipeline_stats_sidebar = ob_get_contents();
ob_end_clean();
