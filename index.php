<?php
$q = $_GET["q"];
$xt = $_GET["xt"];
$trans_cmd = $_GET["transmission"];
$id = $_GET["id"];
$current_directory = '/mnt/disk/volume1/service/DLNA/torrents';
$file = $_FILES["file"];
?>

<html>
  <head>
    <title>Fam Ackerson Torrent Mgmt</title>

    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
    <script type="text/javascript" src="http://github.com/paulirish/jquery-idletimer/raw/master/jquery.idle-timer.js"></script>

    <script type="text/javascript">
      var currentDirectory = '<?=$current_directory;?>';

      function showCurrentDirectory() {
        currentDirP = document.getElementById("currentDir");
        currentDirP.innerText = currentDirectory;
        showDirectoryContents(currentDirectory);
      }
      
      function showDirectoryContents(dir) {
        currentDirectory = dir;

        if (dir=="") {
          document.getElementById("dirContents").innerHTML="";
          return;
        }

        if (window.XMLHttpRequest) {
          xmlhttp=new XMLHttpRequest();
        }

        xmlhttp.onreadystatechange=function() {
          if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById("dirContents").innerHTML=xmlhttp.responseText;
          }
        }

        xmlhttp.open("GET","dircontents.php?ls="+dir,true);
        xmlhttp.send();

        currentDirP = document.getElementById("currentDir");
        currentDirP.innerText = currentDirectory;
      }

      (function($){
          var timeout = 30000;

          $(document).bind("idle.idleTimer", function(){
            location.reload();
          });

          $.idleTimer(timeout);
      })(jQuery);

      window.onload = showCurrentDirectory;
    </script>
    <style type="text/css">
      <!--
      html,
      body {
      margin:0;
      padding:0;
      height:100%;
      }
      #container {
      min-height:100%;
      height: 100%;
      position:relative;
      }
      #header {
      background:skyBlue; /*Background color */ 
      padding:10px;
      }
      #body {
      padding:10px;
      padding-bottom:60px; /* Height of the footer */
      }
      #floater {
      #opacity:0.9;
      border: 1px solid black;
      position:absolute;
      margin-left: 65%;
      padding:5px 10px;
      bottom:5px;
      width:30%;
      height:400px; /* Height of the footer */
      background:lightCoral; /*Background color */
      overflow:scroll;
      }
      -->
    </style>
  </head>

<body>
  <div id="container">
    <div id="header" style="float:left;clear:both;width:80%;">
      <form action="." method="get" style="float:left;">
<a href="./?q=<?=$q?>"><img alt='Refresh' title='Refresh' width='16px' height='16px' style='vertical-align:middle;margin-bottom:4px;' src='./images/refresh.png'></a>
&nbsp;&nbsp;Show:<input type="text" name="q" value="<?=$q;?>"/>&nbsp;&nbsp;
<a href="."><img alt='Clear search' title='Clear search' width='16px' height='16px' style='vertical-align:middle;margin-left:-14px;margin-bottom:4px;' src='./images/clear.png'></a>
<input type="submit" value="Search" />&nbsp;&nbsp;
|&nbsp;&nbsp;&nbsp;&nbsp;Magnet hash:<input type="text" name="xt" value=""/>
<input type="submit" value="Download" />&nbsp;&nbsp;
      </form>
      <form action="." method="post" enctype="multipart/form-data" style="float:right;">
        <label for="file" style="font-style:italic;">.torrent:</label>
        <input type="file" name="file" id="file" style="background-color:white;" /><input type="submit" name="submit" value="Download" />
      </form>
      <hr style="float:left;width:100%;"/>
<?
// a transmission cmd was invoked - Execute!
if (!is_null($trans_cmd) && !is_null($id)) {
    $cmd = '-l';

    if ($trans_cmd == 'stop') {
      $cmd = "-S";
    } else if ($trans_cmd == 'start') {
      $cmd = "-s";
    } else if ($trans_cmd == 'remove') {
      $cmd = "-r";
    }
    
    $result = shell_exec("transmission-remote -t {$id} {$cmd} 2>&1");
    echo "<span style='float:left;color:green;'>$result</span>";
}
// magnet info hash given
if (!is_null($xt) && $xt != "") {
    $uri = "magnet:?xt=urn:btih:".$xt;
    $result = shell_exec("transmission-remote -a ${uri} 2>&1");
    echo "<span style='float:left;color:green;'>$result</span>";
}
// torrent file upload
if (!is_null($file)) {
    if ($file["error"] > 0) {
        echo "Error: " . $file["error"] . "<br />";
    } else {
        require 'bencoded.php';
        $be = new BEncoded;
        $be->FromFile($file["tmp_name"]);
        $uri = "magnet:?xt=urn:btih:".$be->InfoHash();

        $result = shell_exec("transmission-remote -a ${uri} 2>&1");
        echo "<span style='float:left;color:green;'>$result</span>";
    }
}

torrent_list_info();
?>
      <hr style="float:left;clear:both;width:100%;">
    </div>
    <div id="body" style="float:left;clear:both;width:80%;">
<?
# A search was made! Show results...
if (!is_null($q) && strlen(trim($q)) > 0) {
  # TODO : https does NOT work here!!
  $html = "http://torrentz.eu/verifiedP?f={$q}"; #q=Game+of+Thrones+S02E09

  $dom_document = new DOMDocument();
  $dom_document->loadHTMLFile($html);

  $torrent_hashes = array();

  $dom_xpath = new DOMXpath($dom_document);
  $results = $dom_xpath->query("//div[@class='results']/dl");
  if (!is_null($results)) {
    $i = 0;
    foreach ($results as $result) {
      $hash = substr($result->getElementsByTagName('a')->item(0)->getAttribute("href"), 1);
      if (substr($hash, 0, 3) == "z/s") continue;
      $name = $result->getElementsByTagName('a')->item(0)->nodeValue;

      $bkgrd_color = 'white';
      if ($i % 2 == 0) $bkgrd_color = 'wheat';
      echo "<span style='padding:2px;display:block;background-color:{$bkgrd_color};'>";
      echo "<a href='?xt={$hash}&q={$q}'>{$name}</a>&nbsp;&nbsp;";

    	$spans = $result->getElementsByTagName('span');
    	foreach ($spans as $span) {
    	  if ($span->attributes->getNamedItem('class')->nodeValue == 's') {
          $size = $span->nodeValue;
    		  echo "&nbsp;&nbsp;&nbsp;&nbsp;<b>" . $size . "</b>";
    	  }
    	  if ($span->attributes->getNamedItem('class')->nodeValue == 'u') {
          $seeds = $span->nodeValue;
    		  echo "&nbsp;&nbsp;&nbsp;&nbsp;Seeders:&nbsp;" . $seeds;
    	  }
    	  if ($span->attributes->getNamedItem('class')->nodeValue == 'd') {
          $leech = $span->nodeValue;
    		  echo "&nbsp;&nbsp;&nbsp;&nbsp;Leechers:&nbsp;" . $leech . "<br/>";
    	  }
    	}
      echo "</span>";
      $i++;
    }
  }
} 

?>
      </div>
      <div id="floater">
        <span id="currentDir"></span>
        <div id="dirContents">File information</div>
      </div>
    </div>
  </body>
</html>

<?

function torrent_list_info() {
  `transmission-remote -l > /tmp/trans-list.txt`;
  $handle = @fopen("/tmp/trans-list.txt", "r");
  $rows = array();
  if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
      if (stripos($buffer, "ID", 0) === 0) continue;
      if (stripos($buffer, "Sum", 0) === 0) continue;
      $rows[] = str_replace (' ', "&nbsp;", $buffer);
    }
    if (!feof($handle)) {
      echo "Error: unexpected fgets() fail\n";
    }

    fclose($handle);
  }

  echo "<p style='color:white;float:left;clear:both;'>".str_replace(' ', "&nbsp;", "  ID     Done       Have  ETA           Up    Down  Ratio  Status       Name<br/>")."</p>";
  $pattern = "/^(&nbsp;)+(?<id>\d+)(&nbsp;)+(?<percentage>\d+%|n\/a)(&nbsp;)+/";
  $ids = array();
  $finished_ids = array();
  $i = 0;
  foreach ($rows as $row) {
    preg_match($pattern, $row, $matches);
    $id = $matches[id];
    $percentage = $matches[percentage] == null ? '0' : $matches[percentage];

    $toggle_cmd = 'stop';
    $toggle_img = 'stop.gif';
    if (strstr($row, "Stopped")) {
      $toggle_cmd = 'start';
      $toggle_img = 'play.jpeg';
    }

    $bkgrd_color = 'skyBlue';
    $text_color = 'black';

    if ($i % 2 == 0) $bkgrd_color = 'lightSteelBlue';
    if ($percentage == '100%') {
      $text_color = 'red';
      $finished_ids[] = $id;
    }

    $q = $_GET["q"];
    echo "<span style='background-color:{$bkgrd_color};color:{$text_color};display:block;float:left;clear:both;'>";
    $action_icon_id ="<a href='?transmission={$toggle_cmd}&id={$id}&q={$q}'><img width='16px' height='16px' src='./images/{$toggle_img}'></a>";
    $row = preg_replace("/{$id}/", $action_icon_id, $row, 1); 
    echo $row . "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id={$id}&q={$q}'><img alt='Remove torrent' title='Remove torrent' width='16px' height='16px' src='./images/remove.jpeg'></a><br/>";
    echo "</span>";
    $ids[] = $id;
    $i++;
  }
  echo "<div id='global_cmds' style='float:left;clear:both;'><span style='color:white;'> Global commands:</span>&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=stop&id=".implode(',', $ids)."&q={$q}'><img width='24px' height='24px' alt='Stop all' title='Stop all' src='./images/stop.gif'></a>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=start&id=".implode(',', $ids)."&q={$q}'><img width='24px' height='24px' alt='Start all' title='Start all' src='./images/play.jpeg'></a>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id=".implode(',', $ids)."&q={$q}'><img width='24px' height='24px' alt='Remove all' title='Remove all' src='./images/remove.jpeg'></a>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id=".implode(',', $finished_ids)."&q={$q}'><img width='24px' height='24px' alt='Remove seeds' title='Remove all finished' src='./images/trash.png'></a></div>";
}

?>
