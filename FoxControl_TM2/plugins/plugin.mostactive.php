<?php
//* plugin.mostactive.php - Most Active
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_mostactive extends FoxControlPlugin {
	public $listLoginPage = array();
	public $activeList;

	/*
	STARTUP FUNCTION
	*/
	public function onStartUp() {
		$this->name = 'Most Active';
		$this->author = 'matrix142';
		$this->version = '0.5';
		
		//Register Chat Command
		$this->registerCommand('mostactive', 'Displays a list of most active players', true);
		
		//Register ML IDs
		$this->registerMLIds(25);
	}
	
	/*
	ON CHAT COMMAND FUNCTION
	*/
	public function onCommand($args) {
		if($args[2] == 'mostactive') {
			$this->activeList = array();
		
			//Getting Active List
			$sql = mysqli_query($this->db, "SELECT * FROM `players` ORDER BY timeplayed DESC LIMIT 100");
			while($row = $sql->fetch_object()) {
				$this->activeList[] = array('nickname' => htmlspecialchars($row->nickname), 'login' => $row->playerlogin, 'active' => $row->timeplayed, 'country' => $row->country);
			}
			
			$this->showActiveList($args[1]);
		}
	}
	
	/*
	ON PAGES WINDOW CLASS
	*/
	public function onPages($args) {
		if($args[2] == 1) $this->listLoginPage[$args[1]] = 0; // <<
		elseif($args[2] == 2 && $this->listLoginPage[$args[1]] > 0) $this->listLoginPage[$args[1]]--; // <
		elseif($args[2] == 6) $this->listLoginPage[$args[1]]++; // >
		elseif($args[2] == 7) $this->listLoginPage[$args[1]] = floor(count($this->activeList) / 25);
		
		$this->showActiveList($args[1]);
	}
	
	/*
	SHOW BANLIST
	*/
	public function showActiveList($login) {	
		if(!isset($this->listLoginPage[$login])) $this->listLoginPage[$login] = 0;
		$currentID = $this->listLoginPage[$login] * 25;
		
		//Create Window
		$window = $this->window;
		$window->init();
		
		$window->title('Active List');
		
		$window->displayAsTable(true);
		$window->size(70, '');
		$window->posY('40');
		$window->target('onPages', $this);
		
		//Prev Page Button
		if(isset($this->activeList[$currentID - 25])) {
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
		if(isset($this->activeList[$currentID + 25])) {
			$window->addButton('>>>', '7', false);
			$window->addButton('>', '7', false);
		} else {
			$window->addButton('', '7', false);
			$window->addButton('', '7', false);
		}
		
		//Window Head
		$window->content('<td width="3">$iID</td><td width="19">$iNickName</td><td width="15">$iLogin</td><td width="15">$iTime Played</td><td width="15">$iCountry</td>');
		
		for($i=0; isset($this->activeList[$currentID]) && $i<=24; $i++) {	
			if($this->instance()->formattime_hour($this->activeList[$currentID]['active']) != 0) {
				$time = $this->instance()->formattime_hour($this->activeList[$currentID]['active']);
			} else if($this->instance()->formattime_minute($this->activeList[$currentID]['active']) != 0) {
				$time = $this->instance()->formattime_minute($this->activeList[$currentID]['active']);
			} else {
				$time = 0;
			}
		
			if($time != 0) {
				$window->content('<td width="3">'.($currentID+1).'</td><td width="19">'.htmlspecialchars($this->activeList[$currentID]['nickname']).'</td><td width="15">'.$this->activeList[$currentID]['login'].'</td><td width="15">'.$time.'</td><td width="15">'.$this->activeList[$currentID]['country'].'</td>');
			}
			
			$currentID++;
		}
		
		$window->show($login);
	}
}
?>