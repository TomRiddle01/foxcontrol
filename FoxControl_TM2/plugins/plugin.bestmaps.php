<?php
//* plugin.bestmaps.php - Best Maps
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_bestmaps extends FoxControlPlugin {
	public $listLoginPage = array();
	public $bestMaps;

	/*
	STARTUP FUNCTION
	*/
	public function onStartUp() {
		$this->name = 'Best Maps';
		$this->author = 'matrix142';
		$this->version = '0.5';
		
		//Register Chat Command
		$this->registerCommand('bestmaps', 'Displays a list of best voted maps', true);
		
		//Register ML IDs
		$this->registerMLIds(25);
	}
	
	/*
	ON CHAT COMMAND FUNCTION
	*/
	public function onCommand($args) {
		if($args[2] == 'bestmaps') {
			$this->bestMaps = array();
		
			//Getting Active List
			$sql = mysqli_query($this->db, "SELECT * FROM `karma` WHERE playerlogin = 'karma_total' AND vote > 0 ORDER BY vote DESC");
			while($row = $sql->fetch_object()) {
				$this->instance()->client->query('GetMapInfo', $row->challengefilename);
				$mapInfo = $this->instance()->client->getResponse();
				
				if(isset($mapInfo['Author'])) {
					$this->bestMaps[] = array('mapname' => htmlspecialchars($row->challengename), 'author' => $mapInfo['Author'], 'vote' => $row->vote, 'FileName' => $mapInfo['FileName']);
				}
			}
			
			$this->showBestMaps($args[1]);
		}
	}
	
	/*
	ON PAGES WINDOW CLASS
	*/
	public function onPages($args) {
		if($args[2] == 1) $this->listLoginPage[$args[1]] = 0; // <<
		elseif($args[2] == 2 && $this->listLoginPage[$args[1]] > 0) $this->listLoginPage[$args[1]]--; // <
		elseif($args[2] == 6) $this->listLoginPage[$args[1]]++; // >
		elseif($args[2] == 7) $this->listLoginPage[$args[1]] = floor(count($this->bestMaps) / 25);
		
		$this->showBestMaps($args[1]);
	}
	
	/*
	MANIALINK PAGE ANSWER
	*/
	public function onManialinkPageAnswer($args) {
		if($args[2] >= $this->mlids[0] && $args[2] <= $this->mlids[24]) {
			if($this->instance()->pluginIsActive('plugin.jukebox.php') == true) {
				$challenge_page_id = $this->listLoginPage[$args[1]];
				$challenge_page_id = $challenge_page_id*25;
				$jukedchallengex = ($args[2] - $this->mlids[0]+$challenge_page_id);
				
				$jukedchallenge = $this->bestMaps[$jukedchallengex];
				
				plugin_jukebox::jukeChallenge($jukedchallenge['FileName'], $args[1], true);
			}
		}
	}
	
	
	/*
	SHOW BEST MAPS
	*/
	public function showBestMaps($login) {	
		if(!isset($this->listLoginPage[$login])) $this->listLoginPage[$login] = 0;
		$currentID = $this->listLoginPage[$login] * 25;
		
		//Create Window
		$window = $this->window;
		$window->init();
		
		$window->title('Best Maps');
		
		$window->displayAsTable(true);
		$window->size(70, '');
		$window->posY('40');
		$window->target('onPages', $this);
		
		//Prev Page Button
		if(isset($this->bestMaps[$currentID - 25])) {
			$window->addButton('<<<', '7', false);
			$window->addButton('<', '7', false);
		} else {
			$window->addButton('', '7', false);
			$window->addButton('', '7', false);
		}
		
		//Close Button
		$window->addButton('', '15.5', false);
		$window->addButton('Close', '10', true);
		$window->addButton('', '15.5', false);
		
		//Next Page Button
		if(isset($this->bestMaps[$currentID + 25])) {
			$window->addButton('>>>', '7', false);
			$window->addButton('>', '7', false);
		} else {
			$window->addButton('', '7', false);
			$window->addButton('', '7', false);
		}
		
		//Window Head
		if($this->instance()->pluginIsActive('plugin.jukebox.php') == true) {
			$window->content('<td width="3">$iID</td><td width="22">$iMapname</td><td width="1"/><td width="10">$iAuthor</td><td width="10">$iVote</td><td width="5">$iJuke</td>');
		} else {
			$window->content('<td width="3">$iID</td><td width="22">$iMapname</td><td width="1"/><td width="10">$iAuthor</td><td width="10">$iVote</td>');
		}
		
		for($i=0; isset($this->bestMaps[$currentID]) && $i<=24; $i++) {
			$jukeID = $this->mlids[0] + $i;
		
			if($this->instance()->pluginIsActive('plugin.jukebox.php') == true) {
				$window->content('<td width="3">'.($currentID+1).'</td><td width="22" id="'.$jukeID.'">'.htmlspecialchars($this->bestMaps[$currentID]['mapname']).'</td><td width="1"/><td width="10">'.$this->bestMaps[$currentID]['author'].'<td width="10">'.$this->bestMaps[$currentID]['vote'].'</td><td width="5" id="'.$jukeID.'" align="center">Juke</td>');
			} else {
				$window->content('<td width="3">'.($currentID+1).'</td><td width="22">'.htmlspecialchars($this->bestMaps[$currentID]['mapname']).'</td><td width="1"/><td width="10">'.$this->bestMaps[$currentID]['author'].'<td width="10">'.$this->bestMaps[$currentID]['vote'].'</td>');
			}
			
			$currentID++;
		}
		
		$window->show($login);
	}
}
?>