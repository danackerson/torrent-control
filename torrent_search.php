<?php

function display_torrent_search($q) {
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

		    echo "<tr style='background-color:$bkgrd_color;'>
		            <td><a href='javascript:add_a_torrent(\"{$hash}\");'>{$name}</a></td><td style='font-weight:bold;'>$size</td>
		            <td>$seeds</td><td>$leech</td>
		          </tr>";
		  	
		  	$i++;
		}

		echo "</table>";
	}
}
?>