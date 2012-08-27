<?php
session_start();
$ls = $_GET["ls"];

if (!is_null($ls)) {
  $_SESSION['currentDirectory'] = $ls;
  $files = scandir($ls);
  
  foreach($files as $file) {
    if (!preg_match("/^\.(\w+)/", $file) && !preg_match("/^\.$/", $file) && !preg_match("/\.part$/", $file)) {
      if (is_dir($ls."/".$file)) {
        $full_path = $ls."/".$file;

        if (strcmp($file, "..") == 0) {
          $full_path = substr($ls, 0, strrpos($ls , "/"));
        }

        # escape $full_path - especially ticks cause it kills the javascript rendering on frontend
        $full_path = str_replace("'", "&#039", $full_path);
        echo "<span style='display:block;padding-left:5px;margin-top:4px;font-weight:bold;'><img width='16' height='16' src='./images/folder.png'>&nbsp;&nbsp;<a style='vertical-align:top;padding-left:10px;' href='javascript:showDirectoryContents(\"{$full_path}\");'>{$file}/</a><br/></span>";
      } else {
        # TODO - paint these as xspf links?
        # TODO - probably only interesting to link .avi, .mp4, .mov, .mkv, .wmv, etc. files

        $icon = "movies";
        $link = urlencode($ls."/".$file);
        if (  substr($file, -strlen(".avi")) === ".avi" || substr($file, -strlen(".mov")) === ".mov" || 
              substr($file, -strlen(".mkv")) === ".mkv" || substr($file, -strlen(".flv")) === ".flv" ||
              substr($file, -strlen(".3gp")) === ".3gp" || substr($file, -strlen(".mp4")) === ".mp4" ||
              substr($file, -strlen(".mpeg")) === ".mpeg" || substr($file, -strlen(".mpeg4")) === ".mpeg4" ) {
          $file_string = "<a style='vertical-align:top;padding-left:10px;' href='./xspf.php?f=$link'>{$file}</a>";
          echo "<span style='display:block;padding-left:5px;margin-top:4px;'><img width='16' height='16' src='./images/$icon.png'>&nbsp;&nbsp;$file_string<br/></span>";
        }
      }
    }
  }
}
?>
