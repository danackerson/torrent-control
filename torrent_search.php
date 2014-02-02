<?php

function display_torrent_search($q) {
	$torrent_hashes = array();

	$link = "https://torrentz.eu/verifiedP?f=".rawurlencode($q); #q=Game+of+Thrones+S02E09
	exec("curl {$link}", &$page);
	$html = implode("", $page);

	$dom_document = new DOMDocument();
	$dom_document->loadHTML($html);
	$dom_xpath = new DOMXpath($dom_document);
	$results = $dom_xpath->query("//div[@class='results']/dl");

	if (!is_null($results)) {
		$i = 0;

		echo "<table class='available_torrents' style=''>
		        <tr>
		          <th>Available Torrents</th><th>IMDb</th><th>Size</th><th>Seeders</th><th>Leechers</th>
		        </tr>";

		if ($results->length == 0) {
			echo "
				<tr>
		      <td>No trusted results. Search torrentz.eu for '<a target='_blank' href='http://torrentz.eu/search?f={$q}'>{$q}</a>'</td>
		    </tr>";
		}

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

			$encoded_name = urlencode($name);
	    echo "<tr style='background-color:$bkgrd_color;'>
	    				<td><a href='javascript:add_a_torrent(\"{$hash}\", \"{$encoded_name}\");'>{$name}</a></td>
	            <td><a href='http://www.imdb.com/find?q=$encoded_name' target='_blank'><img style='vertical-align: bottom;'title='IMDb search' height='24px' width='24px' alt='IMDb search' src='./images/imdb.ico'></a></td>
	            <td style='font-weight:bold;'>$size</td>
	            <td>$seeds</td><td>$leech</td>
	          </tr>";

	  	$i++;
		}

		echo "</table>";
	}
	unset($results);
}
?>