<?php
session_start();
$ls = $_GET["ls"];

if (!is_null($ls)) {
  $_SESSION['currentDirectory'] = $ls;
  $files = scandir($ls);

  $db = null;
  $watched_paths = array();
  $watched_shows = array();

  if ($files) {
    $db = new SQLiteDatabase('db/show-history.sqlite', 0666, $error);
    if (!$db) echo $error;
    else {
      $q=$db->query("PRAGMA table_info(show_history)");
      if ($q->numRows() != 4) {
          if (!@$db->queryexec("
            CREATE TABLE show_history(
              Id integer PRIMARY KEY, 
              folder_path text NOT NULL, 
              file_name text NOT NULL, date_viewed datetime)")
          ) echo ("Create SQLite Database Error\n");
      } else {
        $sql = 'SELECT * FROM show_history WHERE folder_path LIKE \'' . $ls . '%\'';
        $result = $db->arrayQuery($sql, SQLITE_ASSOC);
        foreach ($result as $show) {
          $folder_name = substr($show['folder_path'], strrpos($show['folder_path'], '/') + 1);
          
          # add most recent date_viewed to parent folder
          if ($watched_paths[$folder_name]) {
            $latest_time = strtotime($watched_paths[$folder_name]);
            $this_time = strtotime($show['date_viewed']);
            if ($this_time > $latest_time) {
              $watched_paths[$folder_name] = $show['date_viewed'];
            }
          } else {
            $watched_paths[$folder_name] = $show['date_viewed'];
          }
          
          $watched_shows[$show['file_name']] = $show['date_viewed'];
        }
      }

    }

    // Execute query
  }
  
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
        if ($watched_paths[rtrim($file,'/')]) {
          $date_diff = $now - strtotime($watched_paths[rtrim($file,'/')]);
          if ($date_diff <= 2592000) { # 30days = 2592000, 7days = 604800
            $add_style = 'color:blue;';
            $alt = $watched_paths[rtrim($file,'/')];
          }
        }
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
          if ($watched_shows[$file] 
            && (strpos($link, "DLNA/torrents") || strpos($link, "DLNA/tv"))
            ) {
            $watched_date = $watched_shows[$file];
            # if watched_date was at least 7 days ago, render the file in italics
            $alt = $watched_shows[$file];
            $add_style = 'color:gray;';
          }
          $style = "style='vertical-align:top;".$add_style."' alt='".$alt."' title='".$alt."'";
          $file_string = "<a ".$style." href='./xspf.php?f=$link'>{$file}</a>";
          echo "<span style='display:block;padding-left:7px;margin-top:4px;'><img width='16' height='16' src='./images/$icon.png'>&nbsp;&nbsp;$file_string<br/></span>";
        }
      }
    }
  }

  if ($db) {
    unset($db);
  }

#  echo "<hr><a href='./sqlite.php' target='_blank'>db</a>";
  echo "<hr><a href='http://lg-nas:9001/rpc/rescan' alt='rescan for TV' title='rescan for TV' target='_blank' style='float:left;padding-left:5px;padding-right:30px;padding-bottom:5px;'><img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/twonky.png\"></a>";
  echo "<a href=\"javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/tv');\" style=\"float:right;padding-left:30px;padding-right:30px;padding-bottom:10px;\">/tv&nbsp;<img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/tv.png\"></a>";
  echo "<a href=\"javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/movies');\" style=\"float:right;padding-left:20px;padding-bottom:10px;\">/movies&nbsp;<img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/movie.png\"></a>";
  echo "<a href=\"javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/torrents');\" style=\"float:right;padding-left:10px;padding-bottom:10px;\">/torrents<img style=\"vertical-align:bottom;\" width=\"24\" height=\"24\" src=\"./images/torrent.png\"></a>";

}
?>
