<?php
$xt = $_GET["xt"];
$id = $_GET["id"];
$cmd = $_GET["cmd"];

if (isset($xt)) add_a_torrent($xt);
else if (isset($cmd) && isset($id)) execute($cmd, $id);

function execute($trans_cmd, $id) {
	$cmd = '-l';

    if ($trans_cmd == 'stop') {
      $cmd = "-S";
    } else if ($trans_cmd == 'start') {
      $cmd = "-s";
    } else if ($trans_cmd == 'remove') {
      $cmd = "-r";
    }
    
    $result = shell_exec("transmission-remote -t {$id} {$cmd} 2>&1");
    usleep(25000); // wait 15ms for transmission daemon to process - this helps when refreshing current status
    #echo "<span style='float:left;color:green;'>$result</span>";
}

function add_a_torrent($xt) {
    $uri = "magnet:?xt=urn:btih:".$xt;
    $result = shell_exec("transmission-remote -a ${uri} 2>&1");
    usleep(15000); // wait 15ms for transmission daemon to process - this helps when refreshing current status
    #echo "<span style='float:left;color:green;'>$result</span>";
}

?>