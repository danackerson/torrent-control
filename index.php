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
    <link rel="shortcut icon" href="./images/torrent.png">
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

        xmlhttp.open("GET","dircontents.php?ls="+escape(dir),true);
        xmlhttp.send();

        currentDirP = document.getElementById("currentDir");
        currentDirP.innerText = "      " + currentDirectory;
      }

      (function($){
          var timeout = 60000;

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
        #padding:10px;
        width:100%;
        float:left;
        clear:both;
        #margin-top:1px;
        margin-bottom:1px;
      }
      #body {
        margin-left:1px;
        margin-right:1px;
        float:left;
        clear:none;
        #width:60%;
      }
      #floater {
        #opacity:0.9;
        border: 1px solid black;
        position:absolute;
        top:139px;
        right:1px;
        background:lightGreen; /*Background color */
        overflow:auto;
        float:right;
        clear:none;
        #width:39%;
      }
      #floater_footer {
        position:relative;
        padding-top:8px;
        #padding-left:5px;
        #padding-right:5px;
        #bottom:10px;
        #width:95%;
        #border: 1px solid black;
        background-color:lightBlue;
      }

      table.running_torrents {
        border-width: 1px;
        border-spacing: 2px;
        border-style: solid;
        border-color: gray;
        border-collapse: collapse;
        background-color: skyBlue;
        text-align: center;
      }
      table.running_torrents th {
        border-width: 1px;
        padding: 4px;
        border-style: inset;
        border-color: gray;
        background-color: skyBlue;
      }
      table.running_torrents td {
        border-width: 1px;
        padding: 4px;
        border-style: inset;
        border-color: gray;
        background-color: white;
      }

      table.available_torrents {
        border-width: 1px;
        border-spacing: 2px;
        border-style: hidden;
        border-color: black;
        border-collapse: collapse;
      }
      table.available_torrents th {
        border-width: 1px;
        padding: 4px;
        border-style: inset;
        border-color: gray;
        background-color: black;
        color: orange;
      }
      table.available_torrents td {
        border-width: 1px;
        padding: 4px;
        border-style: inset;
        border-color: gray;
      }
      -->
    </style>
  </head>

<body>
  <div id="container">
    <div id="header">
      <form action="." method="get" style="float:left;margin-bottom:0;margin-left:10px;margin-top:10px;">
Show:<input type="text" name="q" value="<?=$q;?>"/>&nbsp;&nbsp;
<a href="."><img alt='Clear search' title='Clear search' width='16px' height='16px' style='vertical-align:middle;margin-left:-14px;margin-bottom:3px;' src='./images/clear.png'></a>
<input type="submit" value="Search" />&nbsp;&nbsp;
|&nbsp;&nbsp;&nbsp;&nbsp;Magnet hash:<input type="text" name="xt" value=""/>
<input type="submit" value="Download" />&nbsp;&nbsp;
      </form>
      <form action="." method="post" enctype="multipart/form-data" style="float:right;margin-bottom:0;margin-right:20px;margin-top:10px;">
        <label for="file" style="font-style:italic;">.torrent:</label>
        <input type="file" name="file" id="file" style="background-color:white;" /><input type="submit" name="submit" value="Download" />
      </form>
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
    #echo "<span style='float:left;color:green;'>$result</span>";
}
// magnet info hash given
if (!is_null($xt) && $xt != "") {
    $uri = "magnet:?xt=urn:btih:".$xt;
    $result = shell_exec("transmission-remote -a ${uri} 2>&1");
    #echo "<span style='float:left;color:green;'>$result</span>";
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
        #echo "<span style='float:left;color:green;'>$result</span>";
    }
}

torrent_list_info();
?>
    </div>
    <div id="body" style="">
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

    echo "<table class='available_torrents' style=''>
            <tr>
              <th><a target='_blank' href='http://www.imdb.com/find?q={$q}&s=all'><img style='float:right;' title='IMDb search' height='24px' width='24px' alt='IMDb search' src='./images/imdb.ico'></a>Available Torrents</th><th>Size</th><th>Seeders</th><th>Leechers</th>
            </tr>";

    foreach ($results as $result) {
      $a_tag = $result->getElementsByTagName('a');
      if ($a_tag != null) {
        $first_result = $a_tag->item(0);
        if ($first_result != null) {
          $hash = $first_result->getAttribute("href");
        }
      }
      if ($hash == null) continue;
      $hash = substr($hash, 1);
      if (substr($hash, 0, 3) == "z/s") continue;
      $name = $result->getElementsByTagName('a')->item(0)->nodeValue;

      $bkgrd_color = 'white';
      if ($i % 2 == 0) $bkgrd_color = 'wheat';

    	$spans = $result->getElementsByTagName('span');
    	foreach ($spans as $span) {
    	  if ($span->attributes->getNamedItem('class')->nodeValue == 's') {
            $size = $span->nodeValue;
    		  #echo "&nbsp;&nbsp;&nbsp;&nbsp;<b>" . $size . "</b>";
    	  }
    	  if ($span->attributes->getNamedItem('class')->nodeValue == 'u') {
            $seeds = $span->nodeValue;
    		  #echo "&nbsp;&nbsp;&nbsp;&nbsp;Seeders:&nbsp;" . $seeds;
    	  }
    	  if ($span->attributes->getNamedItem('class')->nodeValue == 'd') {
            $leech = $span->nodeValue;
    		  #echo "&nbsp;&nbsp;&nbsp;&nbsp;Leechers:&nbsp;" . $leech . "<br/>";
    	  }
    	}

        echo "<tr style='background-color:$bkgrd_color;'>
                <td><a href='?xt={$hash}&q={$q}'>{$name}</a></td><td style='font-weight:bold;'>$size</td>
                <td>$seeds</td><td>$leech</td>
              </tr>";
      $i++;
    }

    echo "</table>";
  }
} 

?>
      </div>
      <div id="floater"  style="">
        <div id="floater_footer" style="">
          <a href="javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/tv');" style="text-decoration: none;padding-left:40px;">tv&nbsp;<img style="vertical-align:bottom;" width="24" height="24" src="./images/tv.png"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <a href="javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/movies');" style="text-decoration: none;">movies&nbsp;<img style="vertical-align:bottom;" width="24" height="24" src="./images/movie.png"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <a href="javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/torrents');" style="text-decoration: none;margin-right:40px;">torrents<img style="vertical-align:bottom;" width="24" height="24" src="./images/torrent.png"></a>
          <hr/>
        </div>
        <img width="16" height="16" style="padding-left:5px;" src="./images/nas.png"><span id="currentDir" style="padding-left:15px;"></span><hr/>
        <div id="dirContents" style="padding-bottom:15px;">File information</div>
        
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

    $ids = array();
    $finished_ids = array();
    running_torrents($rows, $ids, $finished_ids);

    echo "<div id='global_cmds' style='float:left;clear:both;margin-top:-25px;margin-left:10px;'><br/><span style='color:white;vertical-align:top;'> Global ops:</span>&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=stop&id=".implode(',', $ids)."&q={$q}'><img width='24px' height='24px' alt='Pause all' title='Pause all' src='./images/stop.gif'></a>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=start&id=".implode(',', $ids)."&q={$q}'><img width='24px' height='24px' alt='Start all' title='Start all' src='./images/play.jpeg'></a>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id=".implode(',', $ids)."&q={$q}'><img width='24px' height='24px' alt='Remove all' title='Remove all' src='./images/remove.jpeg'></a>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?transmission=remove&id=".implode(',', $finished_ids)."&q={$q}'><img width='24px' height='24px' alt='Remove seeds' title='Remove all finished' src='./images/trash.png'></a></div>";
    }

    function running_torrents($rows, &$ids, &$finished_ids) {
        $id_pattern = "(&nbsp;)+(?<id>\d+)(&nbsp;)+";
        $percent_pattern = "(?<percentage>\d+%|n\/a)(&nbsp;)+";
        $have_pattern = "(?<have>\d+\.\d&nbsp;MB|\d+\.\d&nbsp;KB|\d+\.\d&nbsp;GB|None)(&nbsp;)+";
        $eta_pattern = "(?<eta>\d+&nbsp;day|\d+&nbsp;days|\d+&nbsp;hrs|\d+&nbsp;min|\d+sec|Unknown|Done)(&nbsp;)+";
        $band_pattern = "(?<band_up>\d+\.\d)(&nbsp;)+(?<band_down>\d+\.\d)(&nbsp;)+";
        $share_pattern = "(?<share>\d\.\d+|None)(&nbsp;)+";
        $status_pattern = "(?<status>Stopped|Seeding|Idle|Verifying|Downloading|Up&nbsp;&&nbsp;Down)(&nbsp;)+";
        $title_pattern = "(?<title>(.*))";

        #$pattern = "/^$id_pattern$percent_pattern$have_pattern$eta_pattern/";
        $pattern = "/^$id_pattern$percent_pattern$have_pattern$eta_pattern$band_pattern$share_pattern$status_pattern$title_pattern$/";

        $i = 0;
        echo "<table class='running_torrents' style='float:left;clear:both;margin:25px;'>
                <tr style='color:white;'>
                    <th><img width='24' height='24' src='./images/running_torrents.png' alt='Action' title='Action'></th><th>% Done</th><th>Have</th><th>ETA</th><th>Up (KB/s)</th><th>Down (KB/s)</th><th>Ratio</th><th>Status</th><th>Title</th><th>Delete</th>
                </tr>";

        foreach ($rows as $row) {
            preg_match($pattern, $row, $matches);
            $id = $matches[id];

            #echo "<br/><br/><br/>";echo "$row<br/>";print_r($matches);echo "<br/><br/>";

            $percentage = $matches[percentage] == null ? '0' : $matches[percentage];
            $have = $matches[have];
            $eta = $matches[eta];
            $band_up = $matches[band_up]; $band_down = $matches[band_down];
            $share = $matches[share];
            $status = $matches[status];
            $title = $matches[title];

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
            $action_icon_id = "<a href='?transmission={$toggle_cmd}&id={$id}&q={$q}'><img title='{$toggle_cmd} {$id}' alt='{$toggle_cmd} {$id}' width='16px' height='16px' src='./images/{$toggle_img}'></a>";
            $delete_torrent = "<a href='?transmission=remove&id={$id}&q={$q}'><img alt='remove {$id}' title='remove {$id}' width='16px' height='16px' src='./images/remove.jpeg'></a>";

            echo "  <tr style='color:$text_color;'>
                        <td>$action_icon_id</td><td>$percentage</td><td>$have</td><td>$eta</td>
                        <td>$band_up</td><td>$band_down</td><td>$share</td><td>$status</td><td>$title</td>
                        <td>$delete_torrent</td>
                    </tr>";

            $ids[] = $id;
            $i++;
        }

        echo "</table>";
    }
?>
