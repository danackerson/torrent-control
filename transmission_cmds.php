<?php
$xt = $_GET["xt"];
$id = $_GET["id"];
$cmd = $_GET["cmd"];
$dn = $_GET["dn"];

if (isset($xt)) add_a_torrent($xt, $dn);
else if (isset($cmd) && isset($id)) execute($cmd, $id);
else if (isset($cmd)) vpn_exec($cmd);

function vpn_exec($vpn_cmd) {
      $cmd = "sudo killall openvpn";

      #TODO - check radiogroup selection for VPN region
      $vpn_region = "zurich"; # zurich,london,amsterdam
      if ($vpn_cmd == 'vpn_up') {
        $cmd = "sudo /usr/sbin/openvpn --config /etc/openvpn/{$vpn_region}.conf --script-security 2 --down '/usr/bin/transmission-remote 9092 -tall --stop' --down-pre 2>&1 > /tmp/openvpn.log";
      }

      $result = shell_exec($cmd);

      usleep(50000);
}

function execute($trans_cmd, $id) {
    $cmd = '-l';

    if ($trans_cmd == 'stop') {
      $cmd = "-S";
    } else if ($trans_cmd == 'start') {
      $cmd = "-s";
    } else if ($trans_cmd == 'remove') {
      $cmd = "-r";
    } else if ($trans_cmd == 'limit50') {
      $cmd = "-u 20 -d 350";
    } else if ($trans_cmd == 'limit90') {
      $cmd = "-u 5 -d 5";
    } else if ($trans_cmd == 'limit0') {
      $cmd = "-U -D";
    }

    $torrent_ids = "";
    if ($id) $torrent_ids = "-t {$id}";
    
    $result = shell_exec("transmission-remote 9092 {$torrent_ids} {$cmd} 2>&1");
    
    usleep(50000); // wait 50ms for transmission daemon to process - this helps when refreshing current status
    #echo "<span style='float:left;color:green;'>$result</span>";
}

function add_a_torrent($xt, $dn="") {
    $uri = "magnet:?xt=urn:btih:".$xt;
    if ($dn != "") $uri = $uri."&dn=".$dn;

    $result = shell_exec("transmission-remote 9092 -a ${uri} 2>&1");
    if (strpos($result, 'success') !== FALSE && $dn != "") {
      $list = shell_exec("transmission-remote 9092 -l");
      echo "<span style='float:left;color:green;'>$list</span>";
    }

    usleep(50000); // wait 50ms for transmission daemon to process - this helps when refreshing current status
}

?>
