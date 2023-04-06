<?php
$canStart = false;
$time = 0;
if (file_exists("time.txt")) $time = file_get_contents("time.txt");
if ($time + 300 < time()) $canStart = true;
?>
<html>
  <head>
    <title>Pitan Bot</title>
  </head>
  <body>
    <?php echo '<p>' . ($canStart ? "起動中です" : "既に起動しています") . '</p>'; ?> 
  </body>
</html>
<?php
/// ロックファイルを一時作成
$file = fopen("test.lock","w+"); 

/// 排他的ロックを試行
if (flock($file,LOCK_EX + LOCK_NB)) 
{
  /// 模擬的な処理...
  while(true) {
    if ($canStart) exec("php main.php > /dev/null &");
    echo 'running...' . PHP_EOL;
    sleep(10000);
  }

  /// ロック解除
  flock($file,LOCK_UN); 
}
else
{ 
  echo "多重起動できません";
} 
fclose($file);