<?php
$rows = array();
$ids = array();
$finished_ids = array();

transmission_status($rows);
if ($rows.count > 0) transmission_status($rows);

running_torrents($rows, $ids, $finished_ids);

echo "<div id='global_cmds' style='float:left;clear:both;margin-top:-25px;margin-left:10px;'><br/><span style='color:white;vertical-align:top;'> Global ops:</span>&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"stop\", \"".implode(',', $ids)."\");'><img width='24px' height='24px' alt='Pause all' title='Pause all' src='./images/stop.gif'></a>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"start\", \"".implode(',', $ids)."\");'><img width='24px' height='24px' alt='Start all' title='Start all' src='./images/play.jpeg'></a>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"remove\", \"".implode(',', $ids)."\");'><img width='24px' height='24px' alt='Remove all' title='Remove all' src='./images/remove.jpeg'></a>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:transmission_cmd(\"remove\", \"".implode(',', $finished_ids)."\");'><img width='24px' height='24px' alt='Remove seeds' title='Remove all finished' src='./images/trash.png'></a></div>";

function transmission_status(&$rows) {
	`transmission-remote -l > /tmp/trans-list.txt`;
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
}

function running_torrents($rows, &$ids, &$finished_ids) {
    $id_pattern = "(&nbsp;)+(?<id>\d+|\d+\\*)(&nbsp;)+";
    $percent_pattern = "(?<percentage>\d+%|n\/a)(&nbsp;)+";
    $have_pattern = "(?<have>\d+\.\d&nbsp;MB|\d+\.\d&nbsp;KB|\d+\.\d&nbsp;GB|None)(&nbsp;)+";
    $eta_pattern = "(?<eta>\d+&nbsp;day|\d+&nbsp;days|\d+&nbsp;hrs|\d+&nbsp;min|\d+sec|Unknown|Done)(&nbsp;)+";
    $band_pattern = "(?<band_up>\d+\.\d)(&nbsp;)+(?<band_down>\d+\.\d)(&nbsp;)+";
    $share_pattern = "(?<share>\d\.\d+|None)(&nbsp;)+";
    $status_pattern = "(?<status>Stopped|Seeding|Idle|Verifying|Downloading|Up&nbsp;&&nbsp;Down)(&nbsp;)+";
    $title_pattern = "(?<title>(.*))";

    #$pattern = "/^$id_pattern";
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