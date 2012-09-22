<?php

session_start();

$q = $_GET["q"];
$xt = $_GET["xt"];
$file = $_FILES["file"];

require 'torrent_search.php';

$current_directory = $_SESSION['currentDirectory'];
if ($current_directory == null) $current_directory = '/mnt/disk/volume1/service/DLNA/torrents';
?>

<html>
  <head>
    <title>Torrents</title>
    <link rel="shortcut icon" href="./images/torrent.png">
    
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

      var running_torrents_list = new XMLHttpRequest();
      var transmission_ajax = new XMLHttpRequest();

      running_torrents_list.onreadystatechange=function() {
        if (running_torrents_list.readyState == 4 && running_torrents_list.status == 200) {
          document.getElementById("running_torrents_list").innerHTML=running_torrents_list.responseText;
        }
      }

      function transmission_cmd(cmd, id) {
        transmission_ajax.open("GET","transmission_cmds.php?cmd=" + cmd + "&id=" + id,true);
        transmission_ajax.send();
        display_running_torrents();
      }

      function try_to_download() {
        xt = document.getElementsByName("xt")[0].value;
        if (xt != null && xt != '' && xt.length == 40) add_a_torrent(xt);
        else alert("Invalid or missing hash string");
      }

      function add_a_torrent(xt) {
        transmission_ajax.open("GET","transmission_cmds.php?xt=" + xt,true);
        transmission_ajax.send();
        display_running_torrents();
      }

      function display_running_torrents() {
        running_torrents_list.open("GET","transmission_status.php?nocache=" + new Date().getTime(),true);
        running_torrents_list.send();
      }

      setInterval(display_running_torrents,10000);

      window.onload = showCurrentDirectory;
    </script>
    <style type="text/css">
      <!--
      .xtbutton {
        appearance: button;
        -moz-appearance: button;
        -webkit-appearance: button;
        text-decoration: none; font: menu; color: ButtonText;
        display: inline-block; padding: 2px 8px;
        font-size: 11px;
      }
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
        width:100%;
        float:left;
        clear:both;
        margin-bottom:1px;
      }
      #body {
        margin-left:1px;
        margin-right:1px;
        float:left;
        clear:none;
      }
      #explorer {
        #opacity:0.9;
        border: 1px solid gray;
        position:absolute;
        bottom:1px;
        right:1px;
        height:75%;
        width:40%;
        background:lightGreen;
        float:right;
        clear:none;
      }
      #explorer_header {
        position:relative;
        padding-top:8px;
        background-color:lightBlue;
      }
      #explorer_current {
        height: 90%;
        overflow: auto;
        position: absolute;
        top: 50px;
        white-space: nowrap;
        width: 100%;
        padding-bottom: 10px;
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
|&nbsp;&nbsp;&nbsp;&nbsp;Info hash:<input type="text" name="xt" value=""/>
<a class="xtbutton" href="javascript:try_to_download();">Download</a>&nbsp;&nbsp;
      </form>
      <form action="." method="post" enctype="multipart/form-data" style="float:right;margin-bottom:0;margin-right:20px;margin-top:10px;">
        <label for="file" style="font-style:italic;">.torrent:</label>
        <input type="file" name="file" id="file" style="background-color:skyBlue;" /><input type="submit" name="submit" value="Download" />
      </form>
      <div id="running_torrents_list"><?php require 'transmission_status.php'?></div>
    </div>
    <div id="body" style="">
<?
// torrent file upload
if (!is_null($file)) {
    if ($file["error"] > 0) {
        echo "Error: " . $file["error"] . "<br />";
    } else {
        $cmd = "transmission-remote -a ".$file["tmp_name"]." 2>&1";
        $result = shell_exec($cmd);
        echo "<span style='float:left;color:green;'>$result => transmission torrent list above will soon refresh with the new info</span>";
    }
}
if (isset($q)) display_torrent_search($q);

?>
      </div>
      <div id="explorer"  style="">
        <div id="explorer_header" style="">
          <a href="javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/tv');" style="text-decoration: none;padding-left:40px;">tv&nbsp;<img style="vertical-align:bottom;" width="24" height="24" src="./images/tv.png"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <a href="javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/movies');" style="text-decoration: none;">movies&nbsp;<img style="vertical-align:bottom;" width="24" height="24" src="./images/movie.png"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <a href="javascript:showDirectoryContents('/mnt/disk/volume1/service/DLNA/torrents');" style="text-decoration: none;margin-right:40px;">torrents<img style="vertical-align:bottom;" width="24" height="24" src="./images/torrent.png"></a>
          <hr/>
        </div>
        <div id="explorer_current">
        <img width="16" height="16" style="padding-left:5px;" src="./images/nas.png" alt="Current directory" title="Current directory"><span id="currentDir" style="padding-left:15px;"></span><hr/>
        <div id="dirContents" style="">File information</div>
        </div>
      </div>
    </div>
  </body>
</html>