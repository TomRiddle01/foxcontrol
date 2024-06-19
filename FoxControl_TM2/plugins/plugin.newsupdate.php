<?php
//* plugin.newsupdate.php - Check for new versions
//* Version:   1.2
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_newsupdate extends FoxControlPlugin {
	public function onStartUp() {
		$this->registerMLIds(2);
		
		$this->name = 'Newsupdate';
		$this->author = 'matrix142';
		$this->version = '1.2';
	}

	public function getFile() {
		$fp = fsockopen("www.global-rebels.de", 80, $errno, $errstr, 5);
	
		if (!$fp) {
			console('!!!FOXCONTROL MASTERSERVER ERROR!!!');
			console($errstr .'('.$errno.')');
		} else {
			fwrite($fp, "GET / HTTP/1.1\r\n");
		
			$content = simplexml_load_file('http://fox.global-rebels.de/newsupdate/TrackMania2/newsupdate.xml');
			return $content;
		}
	}
	
	public function displayNews() {
		$content = $this->getFile();
		
		if(isset($content)) {
			$version = $content->version;
		
			if(FOXC_VERSION < $version) {
				$button = '
				<frame posn="111.5 3 0">
					<quad posn="-63 -30 1" sizen="15 10" style="BgsPlayerCard" substyle="ProgressBar" />
					<label posn="-62.5 -30 2" text="$f00$sUpdate available" />
					<label posn="-62.5 -33 2" textsize="2.5" text="$sYour Version: {YOUR_VERSION}" />
					<label posn="-62.5 -35 2" textsize="2.5" text="$sNew Version: {VERSION}" />
					<label posn="-56.25 -37.5 2" halign="center" textsize="5" style="TextCardSmallScores2Rank" text="$oClick to Update" action="'.$this->mlids[1].'" />
				</frame>';
			
				$button = str_replace('{YOUR_VERSION}', FOXC_VERSION, $button);
				$button = str_replace('{VERSION}', $version, $button);
			
				$this->instance()->client->query('GetPlayerList', 200, 0);
				$playerList = $this->instance()->client->getResponse();
			
				foreach($playerList as $key => $value) {
					if($this->instance()->is_admin($value['Login'])) {
						$this->chatToLogin($value['Login'], '$f90FoxControl Update available! Your Version: $fff'.FOXC_VERSION.' $f90New Version: $fff'.$version);
						$this->displayManialinkToLogin($value['Login'], $button, $this->mlids[0]);
					}
				}
			}
		}
	}
	
	public function onManialinkPageAnswer($args) {
		if($args[2] == $this->mlids[1]) {
			$pluginChatAdmin = $this->getPluginInstance('chat_admin');
			if($pluginChatAdmin !== false) {
				$pluginChatAdmin->onCommand(array(1 => $args[1], 2 => 'update'));
			} else {
				$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
			}
		}
	}
	
	public function onEndMap($args) {
		$this->displayNews();
	}
	
	public function onBeginMap($args) {
		$this->closeMl($this->mlids[0]);
	}
}
?>