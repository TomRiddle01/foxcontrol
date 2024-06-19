<?php
//* plugin.newsupdate.php - Check for new versions
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_newsupdate extends FoxControlPlugin {
	public function onStartUp() {
		$this->registerMLIds(1);
		
		$this->name = 'Newsupdate';
		$this->author = 'matrix142';
		$this->version = '0.5';
	}

	public function getFile() {
		$fp = fsockopen("www.fox-control.de", 80, $errno, $errstr, 5);
	
		if (!$fp) {
			console('!!!FOXCONTROL MASTERSERVER ERROR!!!');
			console($errstr .'('.$errno.')');
		} else {
			fwrite($fp, "GET / HTTP/1.1\r\n");
		
			$content = simplexml_load_file('http://scripts.fox-control.de/newsupdate/newsupdate.xml');
			return $content;
		}
	}
	
	public function displayNews() {
		$content = $this->getFile();
		$version = $content->version;
		
		if(FOXC_VERSION < $version) {
			$button = file_get_contents($content->code_url);
			
			$button = str_replace('{YOUR_VERSION}', FOXC_VERSION, $button);
			$button = str_replace('{VERSION}', $version, $button);
			
			$this->instance()->client->query('GetPlayerList', 200, 0);
			$playerList = $this->instance()->client->getResponse();
			
			foreach($playerList as $key => $value) {
				if($this->instance()->is_admin($value['Login'])) {
					$this->displayManialinkToLogin($value['Login'], $button, $this->mlids[0]);
				}
			}
		}		
	}
	
	public function onEndChallenge($args) {
		$this->displayNews();
	}
	
	public function onBeginChallenge($args) {
		$this->closeMl($this->mlids[0]);
	}
}
?>