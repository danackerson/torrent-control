<?php
session_start();
$ls = $_GET["ls"];

if (!is_null($ls)) {
  $_SESSION['currentDirectory'] = $ls;
  $files = scandir($ls);

  $now = time();
  foreach($files as $file) {
    if (!preg_match("/^\.(\w+)/", $file) && !preg_match("/^\.$/", $file) && !preg_match("/\.part$/", $file)) {
      if (is_dir($ls."/".$file)) {
        $full_path = $ls."/".$file;

        if (strcmp($file, "..") == 0) {
          $full_path = substr($ls, 0, strrpos($ls , "/"));
        }

        # escape $full_path - especially ticks cause it kills the javascript rendering on frontend
        $full_path = str_replace("'", "&#039", $full_path);
        $add_style = '';
        $alt = '';
        echo "<span style='display:block;padding-left:7px;margin-top:4px;font-weight:bold;'><img width='16' height='16' src='./images/folder.png'>&nbsp;&nbsp;<a style='vertical-align:top;".$add_style."' alt='".$alt."' title='".$alt."' href='javascript:showDirectoryContents(\"{$full_path}\");'>{$file}/</a><br/></span>";
      } else {
        $icon = "movies";
        $link = $ls."/".$file;
        
        if (  substr($file, -strlen(".avi")) === ".avi" || substr($file, -strlen(".mov")) === ".mov" || 
              substr($file, -strlen(".mkv")) === ".mkv" || substr($file, -strlen(".flv")) === ".flv" ||
              substr($file, -strlen(".3gp")) === ".3gp" || substr($file, -strlen(".mp4")) === ".mp4" ||
              substr($file, -strlen(".mpeg")) === ".mpeg" || substr($file, -strlen(".mpeg4")) === ".mpeg4" ||
              substr($file, -strlen(".rmvb")) === ".rmvb" || substr($file, -strlen(".rv")) === ".rv") {
          $alt = '';
          $add_style = '';
          $style = "style='vertical-align:top;".$add_style."' alt='".$alt."' title='".$alt."'";
          $file_string = "<a ".$style." href='./xspf.php?f=$link'>{$file}</a>";
          echo "<span style='display:block;padding-left:7px;margin-top:4px;'><img width='16' height='16' src='./images/$icon.png'>&nbsp;&nbsp;$file_string<br/></span>";
        }
      }
    }
  }

  echo "<hr><a href='http://lg-nas:9001/rpc/rescan' alt='rescan for TV' title='rescan for TV' target='_blank' style='float:left;padding-left:5px;padding-right:30px;padding-bottom:5px;'><img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/twonky.png\"></a>";
  echo "<a href=\"javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/tv');\" style=\"float:right;padding-left:30px;padding-right:30px;padding-bottom:10px;\">/tv&nbsp;<img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/tv.png\"></a>";
  echo "<a href=\"javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/movies');\" style=\"float:right;padding-left:20px;padding-bottom:10px;\">/movies&nbsp;<img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/movie.png\"></a>";
  echo "<a href=\"javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/torrents');\" style=\"float:right;padding-left:10px;padding-bottom:10px;\">/torrents<img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/torrent.png\"></a>";

}
?>
