<?php
//* plugin.banlist.php - Top Donators
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_topdons extends FoxControlPlugin {
	/*
	STARTUP FUNCTION
	*/
	public function onStartUp() {
		$this->name = 'TopDonators';
		$this->author = 'matrix142';
		$this->version = '0.5';
		
		//Register Chat Command
		$this->registerCommand('topdons', 'Displays a list of top donators', false);
		
		//Register ML IDs
		$this->registerMLIds(25);
	}
	
	/*
	ON CHAT COMMAND FUNCTION
	*/
	public function onCommand($args) {
		if($args[2] == 'topdons') {			
			$this->showTopDons($args[1]);
		}
	}
	
	/*
	SHOW BANLIST
	*/
	public function showTopDons($login) {
		
		//Create Window
		$window = $this->window;
		$window->init();
		
		$window->title('$fffTop Donators');
		
		$window->displayAsTable(true);
		$window->size(55, '');
		$window->posY('40');
		
		//Close Button
		$window->addButton('', '15.5', false);
		$window->addButton('Close', '10', true);
		$window->addButton('', '15.5', false);
		
		//Window Head
		$window->content('<td width="3">$iID</td><td width="18">$iNickName</td><td width="15">$iLogin</td><td width="15">$iPlanets</td>');
		
		$sql = mysqli_query($this->db, "SELECT * FROM `players` ORDER BY donations DESC LIMIT 0,25");
		
		$id = 0;
		while($row = $sql->fetch_object()) {
			if($row->donations != 0) {
				$window->content('<td width="3">'.($id + 1).'</td><td width="18">'.htmlspecialchars($row->nickname).'</td><td width="15">'.$row->playerlogin.'</td><td width="15">'.$row->donations.'</td>');
			
				$id++;
			}
		}
		
		$window->show($login);
	}
}
?>