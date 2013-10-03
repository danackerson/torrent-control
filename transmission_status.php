<?php
$rows = array();
$ids = array();
$finished_ids = array();

$turtle_rate = $bear_rate = $rabbit_rate = 'skyBlue';

transmission_status($rows, $turtle_rate, $bear_rate, $rabbit_rate);

running_torrents($rows, $ids, $finished_ids);

$v_stat = vpn_status();
echo "<p id='vpn' style='float:left;margin-top:29px;margin-left:-15px;'>";
echo "{$v_stat}";
echo "</p>";

echo "<div id='global_cmds' style='float:left;clear:both;margin-top:-25px;margin-left:10px;'><br/>";
echo "<span style='color:white;vertical-align:top;'> Rate:</span>";
echo "&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"limit90\", null);'><img width='24px' height='16px' style='vertical-align:top;background-color:{$turtle_rate};' alt='Slow' title='Slow' src='./images/turtle.png'></a>";
echo "&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"limit50\", null);'><img width='24px' height='16px' style='vertical-align:top;background-color:{$bear_rate};' alt='Steady' title='Steady' src='./images/bear.png'></a>";
echo "&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"limit0\", null);'><img width='24px' height='16px' style='vertical-align:top;background-color:{$rabbit_rate};' alt='Sprint' title='Sprint' src='./images/rabbit.png'></a>";
echo "</div>";


function vpn_status() {
    $result=`sudo /root/checkVPN.sh`;
    $html = "&nbsp;<a href='javascript:vpn_cmd(\"vpn_up\");'><img width='24px' height='24px' alt='VPN OFF' title='VPN OFF' style='vertical-align:top;background-color:gray' src='./images/openvpn_off.png'></a>";
    $vpn_region_used = "zurich"; # default - TODO - should come from whatever is in the drop-down select box!

    if (strpos($result, 'up') !== FALSE) {
        $html = "<a href='javascript:vpn_cmd(\"vpn_down\");'><img width='24px' height='24px' alt='VPN ON' title='VPN ON' style='vertical-align:top;' src='./images/openvpn_on.png'></a>";
        
        # find out which VPN region being used    
        $result=`pgrep -fl /usr/sbin/openvpn | awk '{print $4}'`;
        preg_match('/\/etc\/openvpn\/(.*).conf/', $result, $matches);
        $vpn_region_used=$matches[1];
    }

    return "${html}&nbsp;&nbsp;<a href='https://www.privatetunnel.com/index.php/my-usage-graphs.html?duration=year&inhibitGraphs=' target='_blank'><img src='./images/popup.gif'></a><br/>Region:&nbsp;${vpn_region_used}";
}

function transmission_status(&$rows, &$turtle_rate, &$bear_rate, &$rabbit_rate) {
	`transmission-remote 9092 -l > /tmp/trans-list.txt`;
    $handle = fopen("/tmp/trans-list.txt", "r");

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

    `transmission-remote 9092 -si | grep "speed limit" > /tmp/trans-si.txt `;
    $data = array();
    $handle = fopen("/tmp/trans-si.txt", "r");
    if ($handle) {
      while (($buffer = fgets($handle, 4096)) !== false) {
          $pattern = "/speed limit: (?<rate>.*) \(/";
          preg_match($pattern, $buffer, $matches);
          $rate = trim($matches[rate]);

          $first_char = substr($rate,0,1);
          switch ($first_char) {
            case "5":
                $turtle_rate = 'green'; break;
            case "2":
                $bear_rate = 'green'; break;
            case "U":
                $rabbit_rate = 'green'; break;
          }
      }

      if (!feof($handle)) {
          echo "Error: unexpected fgets() fail\n";
      }

      fclose($handle);
    }
}

function running_torrents($rows, &$ids, &$finished_ids) {
    $id_pattern = "(&nbsp;)+(?<id>\d+|\d+\\*)(&nbsp;)+";
    $percent_pattern = "(?<percentage>\d+%|n\/a)(&nbsp;)+";
    $have_pattern = "(?<have>\d+\.\d&nbsp;MB|\d+\.\d&nbsp;KB|\d+\.\d&nbsp;GB|None)(&nbsp;)+";
    $eta_pattern = "(?<eta>\d+&nbsp;day|\d+&nbsp;days|\d+&nbsp;hrs|\d+&nbsp;min|\d+sec|Unknown|Done)(&nbsp;)+";
    $band_pattern = "(?<band_up>\d+\.\d)(&nbsp;)+(?<band_down>\d+\.\d)(&nbsp;)+";
    $share_pattern = "(?<share>\d+\.\d+|None)(&nbsp;)+";
    $status_pattern = "(?<status>Stopped|Seeding|Idle|Verifying|Downloading|Up&nbsp;&&nbsp;Down)(&nbsp;)+";
    $title_pattern = "(?<title>(.*))";

#    $pattern = "/^$id_pattern$percent_pattern$have_pattern$eta_pattern$band_pattern$share_pattern/";
    $pattern = "/^$id_pattern$percent_pattern$have_pattern$eta_pattern$band_pattern$share_pattern$status_pattern$title_pattern$/";
    
    $i = 0;
    echo "<table class='running_torrents' style='float:left;clear:both;margin:25px;'>
            <tr style='color:white;'>
                <th><a href='javascript:display_running_torrents();'><img width='24' height='24' src='./images/running_torrents.png' alt='Action' title='Action'></a></th><th>% Done</th><th>Have</th><th>ETA</th><th>Up (KB/s)</th><th>Down (KB/s)</th><th>Ratio</th><th>Status</th><th>Title</th><th>Delete</th>
            </tr>";

    foreach ($rows as $row) {
        preg_match($pattern, $row, $matches);
        $id = $matches[id];
        $id = rtrim($id, "*");

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
        $action_icon_id = "<a href='javascript:transmission_cmd(\"{$toggle_cmd}\", \"{$id}\");'><img title='{$toggle_cmd} {$id}' alt='{$toggle_cmd} {$id}' width='16px' height='16px' src='./images/{$toggle_img}'></a>";
        $delete_torrent = "<a href='javascript:transmission_cmd(\"remove\", \"{$id}\");'><img alt='remove {$id}' title='remove {$id}' width='16px' height='16px' src='./images/remove.jpeg'></a>";

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
