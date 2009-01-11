<?php

class mw2wp_mediawiki_reader {
	public function get_rendering($page_name) {
		$uri = 'http://wiki.auzigog.com/index.php?name='.$page_name.'&action=render';

		$contents = file_get_contents($uri);
		return $contents;
	}
}

?>
