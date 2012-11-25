<?php
$f = $_GET["f"];

$link = str_replace("/mnt/disk/volume1/", "file:///Volumes/", $f);
$title = substr($link, strrpos($link, "/") + 1);

$path_parts = pathinfo($f);
$folder_path = $path_parts['dirname'];
$file_name = $path_parts['filename'] . '.' . $path_parts['extension'];
$date_time = date('Y-m-d H:i:s');

$db = new SQLiteDatabase('db/show-history.sqlite', 0666, $error);
if (!$db) echo $error;
else {
  $stm = 'INSERT INTO show_history (\'folder_path\', \'file_name\', \'date_viewed\') VALUES(\'' . $folder_path . '\',\'' . $file_name . '\', \'' . $date_time . '\')';
  $result = $db->queryexec($stm);
  if (!$result) echo $result;
  #TODO if folder_path and file_name exist, do an UPDATE!

  unset($db);
}

header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Description: File Transfer');
header('Content-Type: application/xspf+xml');
header('Content-disposition: attachment; filename="movie.xspf"');
echo <<<ML
<?xml version="1.0" encoding="UTF-8"?>
<playlist version="1" xmlns="http://xspf.org/ns/0/">
  <trackList>
    <track>
      <title>{$title}</title>
      <location>{$link}</location>
    </track>
  </trackList>
</playlist>
ML;
?>