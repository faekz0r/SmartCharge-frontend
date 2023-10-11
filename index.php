<html>
<head>

<link rel="stylesheet" type="text/css" href="styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6Rqe9uvcReZ/lGO9sFHBcCs7Q6tSAa4wM4b0vyGp4F4cCMZF4W4x4S9g5fMHr7gZ9Fp" crossorigin="anonymous">

</head>
<body>

<?php

# header("Location:index.php");

$user_vars=fopen("/home/being/SmartCharge/user_vars.sh", "r");
$pattern='/^(\w+)="([\w\/\.\:]+)"/';
while ($line=fgets($user_vars, 80)) {

if (preg_match($pattern, $line, $match)) {
     $conf[$match[1]]=$match[2];
     }
}

fclose($user_vars);

# print_r($conf);

extract($conf);

$formDiv = <<<HTML
<div class="form-container">
  <form name="myForm" id="myForm" action="get_data.php">
    <div class="form-group">
      <label for="end_hour">End at (h):</label>
      <input type="number" name="end_hour" min="0" max="23" value="{$end_hour}">
    </div>
    <div class="form-group">
      <label for="charge_for_hours">Charge for hours:</label>
      <input type="number" name="charge_for_hours" min="0" max="23" value="{$charge_for_hours}">
    </div>
    <div class="form-group">
      <label for="max_price_for_high_limit">Max price (€/mWh):</label>
      <input type="number" name="max_price_for_high_limit" value="{$max_price_for_high_limit}">
    </div>
    <div class="form-group">
      <label for="max_charge_limit">Max charge limit %:</label>
      <input type="number" name="max_charge_limit" min="50" max="100" value="{$max_charge_limit}">
    </div>
    <div class="form-group">
      <label for="min_charge_limit">Min charge limit %:</label>
      <input type="number" name="min_charge_limit" min="50" max="80" value="{$min_charge_limit}">
    </div>
    <div class="form-group">
      <input type="submit" value="Save">
    </div>
  </form>
</div>
HTML;

echo $formDiv;

exec("pgrep main.sh", $pids);

if(!empty($pids)) {
	echo '<p>SmartCharge is running</p>';
	$running=true;
} else {
	echo '<p>SmartCharge is not running</p>';
	$running=false;
}

if ($running) {
	$run="Re-run";
	$kill_href='<a href="?kill=true">Kill</a>';
} else {
	$run="Run";
	$kill_href='';
}


echo '<p><a href="?run=true">'. $run .'</a>'. $kill_href .'</p>';

echo '<p><a href="tail_log.php">Log tail</a><a href="whole_log.php">Whole log</a></p>';

function killScript() {
    exec("killall main.sh");
    exec("sudo -u being /usr/bin/killall main.sh");
}

function redirect() {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}


if ( (isset($_GET['run'])) ) {
	# This code will run if ?run=true is set.
	if ($running) {
	killScript();
	}
	exec("cd /home/being/SmartCharge; /home/being/SmartCharge/main.sh > /home/being/SmartCharge/main.log 2>&1 &");
	sleep(1);
	redirect();
}

if ( (isset($_GET['kill'])) ) {
        # This code will run if ?run=true is set.
        if ($running) {
        killScript();
	}
	sleep(1);
	redirect();
}

$last_ran_date=(file_get_contents("/home/being/SmartCharge/last_ran_date"));

echo '<p style="font-size: 1.5em">Last start:<br>'. $last_ran_date .'</p>';


?>

<script src="jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="functions.js"></script>

</body>
</html>
