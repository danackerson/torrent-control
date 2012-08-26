<?php
$link = $_GET["f"];

$link = str_replace("/mnt/disk/volume1/", "file:///Volumes/", $link);
$title = substr($link, strrpos($link, "/") + 1);

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