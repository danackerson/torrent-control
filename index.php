<?php
$q = $_GET["q"];
$xt = $_GET["xt"];
$trans_cmd = $_GET["transmission"];
?>

<html>
  <head>
    <title>Fam Ackerson Torrent Management</title>
    <script type="text/javascript">
      var currentDirectory = '/mnt/disk/volume1/service/DLNA/torrents';

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
      height:200px; /* Height of the footer */
      background:lightCoral; /*Background color */
      overflow:scroll;
      }
      -->
    </style>
  </head>

<body>
  <div id="container">
    <div id="header">
      <form action="." method="get">
        Search term: <input type="text" name="q" value="<?=$q;?>"/>&nbsp;&nbsp;
        <input type="submit" value="Submit" />&nbsp;&nbsp;(<a href=".">refresh</a>)
      </form>
      <hr/>
<?
torrent_list_info();
?>
      <hr/>
    </div>
    <div id="body">
<?
# A search was made! Show results...
if (!is_null($q) && strlen(trim($q)) > 0) {
  # TODO : https does NOT work here!!
  $html = "http://torrentz.eu/verifiedP?f={$q}"; #q=Game+of+Thrones+S02E09

  $dom_document = new DOMDocument();
  $dom_document->loadHTMLFile($html);

  $torrent_hashes = array();

  $dom_xpath = new DOMXpath($dom_document);
  $results = $dom_xpath->query("//dl");
  if (!is_null($results)) {
    $i = 0;
    foreach ($results as $result) {
      $hash = substr($result->getElementsByTagName('a')->item(0)->getAttribute("href"), 1);
      if (substr($hash, 0, 3) == "z/s") continue;
      $name = $result->getElementsByTagName('a')->item(0)->nodeValue;

      $bkgrd_color = 'white';
      if ($i % 2 == 0) $bkgrd_color = 'wheat';
      echo "<span style='padding:2px;display:block;background-color:{$bkgrd_color};'>";
      echo "<a href='?xt={$hash}'>{$name}</a>&nbsp;&nbsp;";

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
# Else a torrent was added!
} else if (!is_null($xt)) {
  $uri = "magnet:?xt=urn:btih:".$xt;

  system("transmission-remote -a ${uri}");
# Else a transmission cmd was invoked - Execute!
} else if (!is_null($trans_cmd)) {
    $id = $_GET["id"];
    $cmd = '-l';

    if ($trans_cmd == 'stop') {
      $cmd = "-S";
    } else if ($trans_cmd == 'start') {
      $cmd = "-s";
    } else if ($trans_cmd == 'remove') {
      $cmd = "-r";
    }

    system("transmission-remote -t {$id} {$cmd}");
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

  echo "<p>".str_replace(' ', "&nbsp;", "  ID     Done       Have  ETA           Up    Down  Ratio  Status       Name<br/>")."</p>";
  $pattern = "/^(&nbsp;)+(?<digit>\d+)&nbsp;/";
  $ids = array();
  $i = 0;
  foreach ($rows as $row) {
    preg_match($pattern, $row, $matches);
    $id = $matches[digit];

    $toggle_cmd = 'stop';
    $toggle_img = 'stop.gif';
    if (strstr($row, "Stopped")) {
      $toggle_cmd = 'start';
      $toggle_img = 'play.jpeg';
    }

    $bkgrd_color = 'skyBlue';
    if ($i % 2 == 0) $bkgrd_color = 'lightSteelBlue';
      
    echo "<span style='background-color:{$bkgrd_color};display:block;'>";
    $row = str_replace($id, "<a href='?transmission={$toggle_cmd}&id={$id}'><img width='16px' height='16px' src='./images/{$toggle_img}'></a>", $row);
    echo $row . "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id={$id}'><img alt='Remove torrent' title='Remove torrent' width='16px' height='16px' src='./images/remove.jpeg'></a><br/>";
    echo "</span>";
    $ids[] = $id;
    $i++;
  }
  echo "<br/><a href='?transmission=stop&id=".implode(',', $ids)."'>Stop all</a>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=start&id=".implode(',', $ids)."'>Start all</a>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id=".implode(',', $ids)."'>Remove all</a>";
}

?>
