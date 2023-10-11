<?php

# function execInBackground($cmd) {
#         exec($cmd . " > /dev/null &");  
# }

$vars_in_json_array=json_encode($_POST);

echo json_encode($_POST);

# echo "<br>";

# print_r($_POST);

# $jq_to_bash = <<<'EOD'
# jq -r 'to_entries | .[] | .key + "=" + (.value | @sh)' > vars
# EOD;


# exec("echo '$vars_in_json_array' | '$jq_to_bash'");

exec("echo '$vars_in_json_array' | jq -r 'to_entries | .[] | .key + \"=\" + (.value | @sh)' > vars.sh");

$old_path = getcwd();
chdir('/var/www/SmartCharge/');
shell_exec('/bin/bash -c /var/www/SmartCharge/transfer_vars.sh');
chdir($old_path);

?>
