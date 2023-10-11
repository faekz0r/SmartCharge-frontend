<?php

$tesla_api_oauth2 = 'https://auth.tesla.com/oauth2/v3';
$tesla_api_redirect = 'https://auth.tesla.com/void/callback';
$tesla_api_owners = 'https://owner-api.teslamotors.com/oauth/token';
$tesla_api_code_vlc = 86;
$cid = "";
$cs = ""; 
$user_agent = "Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148";
$cookie_file = __DIR__."/tmp/cookies.txt";

function tesla_connect($url, $returntransfer=1, $referer="", $http_header="", $post="", $need_header=0, $cookies="", $timeout = 10)
{
    global $cookie_file;

    if(!empty($post)) { $cpost = 1; } else { $cpost = 0; }
    if(is_array($http_header)) { $chheader = 1; } else { $chheader = 0; }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, $returntransfer);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, $need_header);
    curl_setopt($ch, CURLOPT_POST, $cpost);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_MAX_TLSv1_2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if(!empty($referer)) { curl_setopt($ch, CURLOPT_REFERER, $referer); }

    if($chheader == 1) { curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header); }

    if($cpost == 1) { curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    
    if(!empty($cookies)) { curl_setopt($ch, CURLOPT_COOKIE, $cookies); }

    $response = curl_exec($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    return array("response" => $response, "header" => $header);

}


function gen_challenge()
{
    global $tesla_api_code_vlc;

    $code_verifier = substr(hash('sha512', mt_rand()), 0, $tesla_api_code_vlc);
    $code_challenge = rtrim(strtr(base64_encode($code_verifier), '+/', '-_'), '='); 
    
    $state = rtrim(strtr(base64_encode(substr(hash('sha256', mt_rand()), 0, 12)), '+/', '-_'), '='); 

    return array("code_verifier" => $code_verifier, "code_challenge" => $code_challenge, "state" => $state);
}


function gen_url($code_challenge, $state)
{
    global $tesla_api_oauth2, $tesla_api_redirect;


    $datas = array(
          'audience' => '',
          'client_id' => 'ownerapi',
          'code_challenge' => $code_challenge,
          'code_challenge_method' => 'S256',
          'locale' => 'en-US',
          'prompt' => 'login',
          'redirect_uri' => $tesla_api_redirect,
          'response_type' => 'code',
          'scope' => 'openid email offline_access',
          'state' => $state
    );

    return $tesla_api_oauth2."/authorize?".http_build_query($datas);
}


function return_msg($code, $msg)
{
    return json_encode(array("success" => $code, "message" => $msg));
}


function login($weburl, $code_verifier, $code_challenge, $state)
{
    global $tesla_api_redirect, $user_agent, $tesla_api_oauth2, $cid, $cs, $tesla_api_owners;

    
    $code = explode('https://auth.tesla.com/void/callback?code=', $weburl);
    $code = explode("&", $code[1])[0];


    if(empty($code)) { return return_msg(0, "Something is wrong ... Code not exists"); }

    // Get the Bearer token
    $http_header = array('Content-Type: application/json', 'Accept: application/json', 'User-Agent: '.$user_agent);
    $post = json_encode(array("grant_type" => "authorization_code", "client_id" => "ownerapi", "code" => $code, "code_verifier" => $code_verifier, "redirect_uri" => $tesla_api_redirect));
    $response = tesla_connect($tesla_api_oauth2."/token", 1, "", $http_header, $post, 0);

    $token_res = json_decode($response["response"], true);
    $bearer_token = $token_res["access_token"];
    $refresh_token = $token_res["refresh_token"];

    if(empty($bearer_token)) { return return_msg(0, "Bearer Token issue"); var_dump($token_res); }

    // Final Step
    unset($response);
    $http_header = array('Authorization: Bearer '.$bearer_token, 'Content-Type: application/json');
    $post = json_encode(array("grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer", "client_id" => $cid, "client_secret" => $cs));
    $response = tesla_connect($tesla_api_owners, 1, "", $http_header, $post, 0);

    $tokens = json_decode($response["response"], true);

    if(empty($tokens['access_token'])) { return return_msg(0, "Token issue"); }

    $tokens["bearer_token"] = $bearer_token;
    $tokens["bearer_refresh_token"] = $refresh_token;
    $return_message = json_encode($tokens);

    // Output
    return return_msg(1, $return_message);
}


function tesla_oauth2_refresh_token($bearer_refresh_token)
{
    global $tesla_api_oauth2, $tesla_api_redirect, $tesla_api_owners, $tesla_api_code_vlc, $cid, $cs;


    $brt = $bearer_refresh_token;

    // Get the Bearer token
    $http_header = array('Content-Type: application/json', 'Accept: application/json');
    $post = json_encode(array("grant_type" => "refresh_token", "client_id" => "ownerapi", "refresh_token" => $brt, "scope" => "openid email offline_access"));
    $response = tesla_connect($tesla_api_oauth2."/token", 1, "https://auth.tesla.com/", $http_header, $post, 0);


    $token_res = json_decode($response["response"], true);
    $bearer_token = $token_res["access_token"];
    $refresh_token = $token_res["refresh_token"];


    if(empty($bearer_token)) { return return_msg(0, "Bearer Refresh Token is not valid"); }

    $http_header = array('Authorization: Bearer '.$bearer_token, 'Content-Type: application/json');
    $post = json_encode(array("grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer", "client_id" => $cid, "client_secret" => $cs));
    $response = tesla_connect($tesla_api_owners, 1, "", $http_header, $post, 0);

    $tokens = json_decode($response["response"], true);

    if(empty($tokens['access_token'])) { return return_msg(0, "Token issue"); }

    $tokens["bearer_token"] = $bearer_token;
    $tokens["bearer_refresh_token"] = $refresh_token;
    $return_message = json_encode($tokens);

    // Output
    return return_msg(1, $return_message);

}



if($_POST["go"] == "login")
{
    echo login($_POST["weburl"], $_POST["code_verifier"], $_POST["code_challenge"], $_POST["state"]);
} else if ($_POST["go"] == "refresh")
{
    echo tesla_oauth2_refresh_token($_POST["token"]);
} else {

$challenge = gen_challenge();
$code_verifier = $challenge["code_verifier"];
$code_challenge = $challenge["code_challenge"];
$state = $challenge["state"];
$timestamp = time();

?>
<h3>First Login</h3>
<form method="post">
<input type="hidden" name="go" value="login">
<input type="hidden" name="code_verifier" value="<?php echo $code_verifier; ?>">
<input type="hidden" name="code_challenge" value="<?php echo $code_challenge; ?>">
<input type="hidden" name="state" value="<?php echo $state; ?>">
Unfortunately Tesla has installed a recaptcha, so the automatic login is no longer possible.<br>
Please read the individual steps well:<br><br>

Step 1: Please <strong><a href="#<?php echo $timestamp; ?>" onclick="teslaLogin();return false();">click here</a></strong> to log in to Tesla (A popup window will open, please allow popups).<br>
Step 2: Please enter your Tesla login data on the Tesla website.<br>
Step 3: If the login was successful, you will receive a <strong>Page not found</strong> information on the Tesla website. Copy the complete web address ( e.g. <strong><?php echo $tesla_api_redirect; ?>?code=.....&state=...&issuer=....</strong> )<br>
Step 4: Paste the copied web address here and press the <strong>Login</strong>-Button:<br>
<input type="text" name="weburl" size="100" required><input type="submit" value="Login"></form>

<hr>
<h3>Refresh Token</h3>
<form method="post">
<input type="hidden" name="go" value="refresh">
Please enter the Bearer Refresh-Token:<br>
<input name="token" size="100" required><input type="submit" value="Refresh">
</form>
<script>
function teslaLogin () {
    teslaLogin = window.open("<?php echo gen_url($code_challenge, $state);?>", "TeslaLogin", "width=600,height=400,status=yes,scrollbars=yes,resizable=yes");
    teslaLogin.focus();
}
</script>

<?php
}
?>
