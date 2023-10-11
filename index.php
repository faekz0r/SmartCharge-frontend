<html>
<head>

<style>

form {
  text-align: center;
  margin: 0 auto 0;
}

input[type=number] {
   width: 66px;
}

html {
    /* Base font size */
    font-family: Arial;
    font-size: 16px;
}

label,
input {
  /* In order to define widths */
  display: inline-block;
  font-size: 2.5rem;
}


input+input {
  display: block;
  margin-left: auto;
  margin-right: auto;
}

/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

a:link, a:visited, input[type=submit] {
  background-color: DeepSkyBlue;
  color: white;
  cursor: pointer;
  border: 0px;
  border-radius: 28px;
  padding: 10px 20px 10px 20px;
  text-align: center;
  text-decoration: none;
  transition: 0.2s;
  margin: 15px auto 15px;
}

a {
  margin: 15px 5px 15px !important;
}

a:hover, a:active, input[type=submit]:hover {
  background-color: DodgerBlue;
/*  color: white; */
}

p {
    margin: 25px auto 25px;
    display: block;
    text-align: center;
    font-size: 2.5rem;
}

td { text-align: right; }
td.submit { text-align: center; }

table {
  margin: 0 auto 0;
}


</style>

<script src="jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
// prevent default html submit
$(function () {  
  $('#myForm').on("submit", function(event){
	event.preventDefault();
	$.ajax({
	  method: "POST",
	  url: 'get_data.php',
	  data: $('#myForm').serialize(),
	  cache: false,
	  success: function(data){
		  location.reload();
	  },
	  error: function(){
		  alert("Form submission failed!");
	  }
	});
   });
});
</script>
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

# print_r($conf);

extract($conf);

# echo '<span style="white-space: pre-line; line-height:30px">
echo '
<table>
  <form name="myForm" id="myForm"">
   <tr>
      <td>
        <label for="end_hour">End at (h):</label>
      </td>
      <td>
        <input type="number" name="end_hour" min="0" max="23" value="'. $end_hour .'">
      </td>
    </tr>
    <tr>
      <td>
	<label for="charge_for_hours">Charge for hours:</label>
      </td>
      <td>
        <input type="number" name="charge_for_hours" min="0" max="23" value="'. (isset($charge_for_hours) ? $charge_for_hours : "") .'">
      </td>
    </tr>

    <tr>
      <td>
        <label for="max_price_for_high_limit">Max price (€/mWh):</label>
      </td>
      <td>
        <input type="number" name="max_price_for_high_limit" value="'. $max_price_for_high_limit .'">
      </td>
    </tr>

    <tr>
      <td>
        <label for="max_charge_limit">Max charge limit %:</label>
      </td>
      <td>
        <input type="number" name="max_charge_limit" min="50" max="100" value="'. $max_charge_limit .'">
      </td>
    </tr> 
       
    <tr>
      <td>
        <label for="min_charge_limit">Min charge limit %:</label>
      </td>
      <td>
        <input type="number" name="min_charge_limit" min="50" max="80" value="'. $min_charge_limit .'"/>
      </td>
    </tr>

    <tr>
      <td align="center" colspan="2" class="submit">
        <input type="submit" value="Save">
      </td>
    </tr>

  </form>
</table>
';


exec("pgrep main.sh", $pids);

if(!empty($pids)) {
	echo '<p>SmartCharge is running</p>';
	$running=true;
} else {
	echo '<p>SmartCharge is not running</p>';
	$running=false;
}

function killScript() {
	exec("killall main.sh");
	exec("sudo -u being /usr/bin/killall main.sh");
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



if ( (isset($_GET['run'])) ) {
	# This code will run if ?run=true is set.
	if ($running) {
	killScript();
	}
	exec("cd /home/being/SmartCharge; /home/being/SmartCharge/main.sh > /home/being/SmartCharge/main.log 2>&1 &");
	sleep(1);
	header('Location: ' . $_SERVER['HTTP_REFERER']);
	exit;
}

if ( (isset($_GET['kill'])) ) {
        # This code will run if ?run=true is set.
        if ($running) {
        killScript();
	}
	sleep(1);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
}

$last_ran_date=(file_get_contents("/home/being/SmartCharge/last_ran_date"));

echo '<p style="font-size: 1.5em">Last start:<br>'. $last_ran_date .'</p>';


?>

</body>
</html>
