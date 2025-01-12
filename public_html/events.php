<?php
$title = 'Events';
$subtitle = 'Details of past and future nf-core meetups.';
$md_github_url = 'https://github.com/nf-core/nf-co.re/blob/master/nf-core-events.yaml';
include('../includes/header.php');

require_once("../Spyc.php");
$events_yaml = spyc_load_file('../nf-core-events.yaml');
$events = $events_yaml['events'];

$event_type_classes = array(
  'hackathon' => 'primary',
  'talk' => 'success',
  'tutorial' => 'info'
);

# Parse dates and sort events by date
foreach($events as $idx => $event){
  # Check that start date is set, delete if not
  if(!isset($event['start_date'])){
    unset($events[$idx]);
    continue;
  }
  # Check end date is set
  if(!isset($event['end_date'])) {
    $event['end_date'] = $event['start_date'];
  }
  # Parse dates
  if(!isset($event['start_time'])) $event['start_time'] = '';
  if(!isset($event['end_time'])) $event['end_time'] = '';
  $event['start_ts'] = strtotime($event['start_date'].' '.$event['start_time']);
  $event['end_ts'] = strtotime($event['end_date'].' '.$event['end_time']);
  # Check end is after start
  if($event['end_ts'] < $event['start_ts']){
    $event['end_date'] = $event['start_date'];
    $event['end_ts'] = strtotime($event['end_date'].' '.$event['end_time']);
  }
  # Update array
  $events[$idx] = $event;
}
usort($events, function($a, $b) {
    return $b['start_ts'] - $a['start_ts'];
});

$future_events = false;
$past_events = false;
foreach($events as $idx => $event):
  # Nice date strings
  $date_string = date('j<\s\u\p>S</\s\u\p> M Y', $event['start_ts']).' - '.date('j<\s\u\p>S</\s\u\p> M Y', $event['end_ts']);
  if(date('mY', $event['start_ts']) == date('mY', $event['end_ts'])){
    $date_string = date('j<\s\u\p>S</\s\u\p> ', $event['start_ts']).' - '.date('j<\s\u\p>S</\s\u\p> M Y', $event['end_ts']);
  }
  if(date('dmY', $event['start_ts']) == date('dmY', $event['end_ts'])){
    $date_string = date('j<\s\u\p>S</\s\u\p> M Y', $event['end_ts']);
  }

  # Print Upcoming / Past Events headings
  if(!$future_events && $event['start_ts'] > time()){
    $future_events = true;
    echo '<h2 id="future_events"><a href="#future_events" class="header-link"><span class="fas fa-link" aria-hidden="true"></span></a>Upcoming Events</h2>';
  }
  if(!$past_events && $event['start_ts'] < time()){
    $past_events = true;
    echo '<h2 id="past_events"><a href="#past_events" class="header-link"><span class="fas fa-link" aria-hidden="true"></span></a>Past Events</h2>';
  }

  $colour_class = $event_type_classes[strtolower($event['type'])];
?>

<!-- Event Card -->
<div class="card my-4 border-top-0 border-right-0 border-bottom-0 border-<?php echo $colour_class; ?>">
  <div class="card-body <?php if($past_events){ echo 'py-2'; } ?>">
    <h5 class="my-0 py-0">
      <small><span class="badge badge-<?php echo $colour_class; ?> float-right small"><?php echo ucfirst($event['type']); ?></span></small>
      <a class="text-success" href="#event_<?php echo $idx; ?>_modal" data-toggle="modal" data-target="#event_<?php echo $idx; ?>_modal"><?php echo $event['title']; ?></a>
    </h5>
    <?php if(!$past_events): ?>
      <h6 class="small text-muted"><?php echo $date_string; ?></h6>
      <p><?php echo $event['description']; ?></p>
      <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#event_<?php echo $idx; ?>_modal">
        See details
      </button>
    <?php else: ?>
      <h6 class="small text-muted mb-0">
        <?php echo $date_string; ?> -
        <a class="text-success" href="#event_<?php echo $idx; ?>_modal" data-toggle="modal" data-target="#event_<?php echo $idx; ?>_modal">
          See details
        </a>
      </h6>
    <?php endif; ?>
  </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="event_<?php echo $idx; ?>_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $event['title']; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo $event['description']; ?></p>

        <dl class="row small">
        <?php
        // Start time
        if($event['start_time']){
          echo '<dt class="col-sm-4">Event starts:</dt><dd class="col-sm-8">'.date('H:i, j<\s\u\p>S</\s\u\p> M Y', $event['start_ts']).'</dd>';
        } else {
          echo '<dt class="col-sm-4">Event starts:</dt><dd class="col-sm-8">'.date('j<\s\u\p>S</\s\u\p> M Y', $event['start_ts']).'</dd>';
        }
        // End time
        if($event['end_ts'] > $event['start_ts'] && $event['end_time']){
          echo '<dt class="col-sm-4">Event ends:</dt><dd class="col-sm-8">'.date('H:i, j<\s\u\p>S</\s\u\p> M Y', $event['end_ts']).'</dd>';
        } else if($event['end_ts'] > $event['start_ts']){
          echo '<dt class="col-sm-4">Event ends:</dt><dd class="col-sm-8">'.date('j<\s\u\p>S</\s\u\p> M Y', $event['end_ts']).'</dd>';
        }

        // Location
        echo '<dt class="col-sm-4">Location:</dt><dd class="col-sm-8">';
        if(isset($event['location_name'])){
          if(isset($event['location_url'])){
            echo '<a href="'.$event['location_url'].'" target="_blank">'.$event['location_name'].'</a>'.'<br>';
          } else {
            echo $event['location_name'].'<br>';
          }
        } else if(isset($event['location_url'])){
          echo '<a href="'.$event['location_url'].'" target="_blank">'.$event['location_url'].'</a>'.'<br>';
        }
        if(isset($event['address'])){
          echo $event['address'].'<br>';
        }
        if(isset($event['location_latlng'])){
          echo '<a class="btn btn-sm btn-outline-secondary" href="https://www.google.com/maps/search/?api=1&query='.implode(',', $event['location_latlng']).'" target="_blank">See map</a>';
        }
        echo '</dd>';

        // Location
        echo '<dt class="col-sm-4">Contact person:</dt><dd class="col-sm-8">';
        if(isset($event['contact'])){
          echo $event['contact'];
        }
        if(isset($event['contact_email'])){
          echo ' (<a href="mailto:'.$event['contact_email'].'">'.$event['contact_email'].'</a>)<br>';
        }
        if(isset($event['contact_github'])){
          echo '<a href="https://github.com/'.$event['contact_github'].'" target="_blank"><i class="fab fa-github"></i> '.$event['contact_github'].'</a>';
        }
        echo '</dd>';

        // Event website (twice)
        if(isset($event['event_url'])){
          echo '<dt class="col-sm-4">Event website:</dt><dd class="col-sm-8">
            <a href="'.$event['event_url'].'" target="_blank" style="white-space: nowrap; width: 100%; overflow-x: auto; display: inline-block;">'.$event['event_url'].'</a>
          </dd>';
        }

        // Links
        if(isset($event['links'])){
          echo '<dt class="col-sm-4">Additional Links:</dt><dd class="col-sm-8">';
          foreach($event['links'] as $text => $link){
            echo '<a href="'.$link.'" target="_blank">'.$text.' <i class="fas fa-external-link-alt fa-xs ml-1"></i></a><br>';
          }
          echo '</dd>';
        }

        ?>
        </dl>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <?php if(isset($event['event_url'])): ?>
          <a href="<?php echo $event['event_url']; ?>" target="_blank" class="btn btn-primary">Event Website</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php endforeach;

include('../includes/footer.php');
