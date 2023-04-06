<?php
exec("php replit.php > /dev/null &");
if (!isset($_GET['lang'])) $_GET['lang'] = "en";
$lang = json_decode(file_get_contents("lang.json"), true)[$_GET['lang']];

$error = "";
if (isset($_POST['token'])) $_GET['token'] = $_POST['token'];

if (isset($_POST['cf-turnstile-response']) && isset($_GET['token']) && !empty($_GET['token'])) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'secret' => getenv("TURNSTILE_SECRET"),
    'response' => $_POST['cf-turnstile-response'],
    'remoteip' => $_SERVER["REMOTE_ADDR"],
  ));
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $res = json_decode(curl_exec($ch), true);
  if ($res["success"] == true) {
    $token = $_GET['token'];
    $token_filename = "data/token/" . $token . '.json';
    $tokenData = json_decode(file_get_contents($token_filename), true);
    
    $guildId = $tokenData['guild'];
    $filename = "data/guild/" . $guildId . ".json";
    $data = json_decode(file_get_contents($filename), true);
    //unlink($filename);

    $result = exec("php verify.php " . $guildId . " " . $data['role'] . " " . $tokenData['user'], $output);
    //var_dump($output);
    echo "Verified";
    unlink($token_filename);
    exit;
  }
  
  $error .= "Error: Failed verification<br />";
}
if (!isset($_GET['token']) || empty($_GET['token'])) {
  $error .= "Error: The token is empty.<br />";
}
  
?>
<html lang="ja">
  <head>
    <title>Turnstile Verify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="index.css?<?php echo filemtime("index.css"); ?>" />
    <link rel="SHORTCUT ICON" href="https://wikichree.com/dtvbot/skin/logo.png" />
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3977024448605477"
     crossorigin="anonymous"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
    function verified(token) {
      document.getElementById("verify").submit();
    }
    </script>
    <meta charset="UTF-8">
  </head>
  <body>
    <h2>Turnstile Verify</h2>
    <p><?php echo $error; ?></p>
    <p><?php echo $lang['site_text']; ?><p>
    <form id="verify" action="" method="POST">
      <div class="cf-turnstile" data-sitekey="0x4AAAAAAADtojtgykaGjVpx" data-callback="verified"></div>
      <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>" />
    </form>
    <hr />
    <a href="https://wikichree.com/dtvbot/"><?php echo $lang['wiki_link']; ?></a>
  </body>
<?php
?>
</html>