<?php
$ls = $_GET["ls"];

if (!is_null($ls)) {
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
        echo "<span style='display:block;margin:3px;font-weight:bold;'><a href='javascript:showDirectoryContents(\"{$full_path}\");'>{$file}/</a><br/></span>";
      } else {
        echo "<span style='display:block;margin:3px;'>{$file}<br/></span>";
      }
    }
  }
}
?>
