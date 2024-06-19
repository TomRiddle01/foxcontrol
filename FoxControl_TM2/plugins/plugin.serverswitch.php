<?php
class plugin_serverswitch extends FoxControlPlugin {
	public $config;
	public $posn;
	public $currentID = array();
	public $db2;
	public $serverList;
	public $currentTime;

	/*
		START UP
	*/
	public function onStartUp() {
		$this->registerMLIds(2);
		$this->registerWidgets(1);
		
		$this->config = $this->loadConfig();
		
		$this->db2 = mysqli_connect($this->config->database->host, $this->config->database->user, $this->config->database->pass);
		if(!mysqli_select_db($this->db2, $this->config->database->name)) {
			mysqli_error($this->db2);
		}
		
		$tbl_serverswitch = "
			CREATE TABLE IF NOT EXISTS `servers` (
			`servername` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
			`serverlogin` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			`players` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			`maxplayers` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			`time` int(11) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		mysqli_query($this->db2, $tbl_serverswitch);
		
		$this->onBeginChallenge(false);
	}
	
	/*
		BEGIN MAP
	*/
	public function onBeginChallenge($args) {
		$this->posn = $this->getPosn('serverswitch');
		
		$widget = $this->widget;
		$widget->init();
		
		$widget->configurable(false);
		
		$widget->title('Server Switch');
		$widget->posn($this->posn[0], $this->posn[1]);
		$widget->size(16, 9.75);
		$widget->fontSize('1');
		$widget->icon('Icons128x128_1', 'ServersAll');
		
		$widget->saveWidget($this->widgetIDs[0], $this->mlids[0]);
		
		$this->displayWidget();
	}
	
	/*
		END MAP
	*/
	public function onEndChallenge($args) {
		$this->closeMl($this->mlids[0]);
	}
	
	/*
		PLAYER CONNECT
	*/
	public function onPlayerConnect($args) {
		$this->displayWidget($args['Login']);
	}
	
	/*
		EVERY SECOND
	*/
	public function onEverySecond() {
		if(empty($this->currentTime)) {
			$this->currentTime = time();
			$this->refresh();
		}
		
		if(time() > ($this->currentTime + 20)) {
			$this->currentTime = time();
			$this->refresh();
		}
	}
	
	/*
		REFRESH
	*/
	public function refresh() {
		global $settings;
		
		$this->serverList = array();
		
		$this->instance()->client->query('GetServerName');
		$serverName = $this->instance()->client->getResponse();
		
		$this->instance()->client->query('GetMaxPlayers');
		$maxPlayers = $this->instance()->client->getResponse();
		$maxPlayers = $maxPlayers['CurrentValue'];
		
		$this->instance()->client->query('GetPlayerList', 200, 0);
		$playerList = $this->instance()->client->getResponse();
		
		$playersCount = -1;
		foreach($playerList as $players) {
			$playersCount++;
		}
	
		$sql = mysqli_query($this->db2, "SELECT serverlogin FROM `servers` WHERE serverlogin = '".$settings['ServerLogin']."'");
		if($row = $sql->fetch_object()) {
			$sql2 = mysqli_query($this->db2, "UPDATE `servers` SET servername = '".$serverName."', players = '".$playersCount."', maxplayers = '".$maxPlayers."', time = '".time()."' WHERE serverlogin = '".$settings['ServerLogin']."'");
		} else {
			$sql2 = mysqli_query($this->db2, "INSERT INTO `servers` VALUES('".$serverName."', '".$settings['ServerLogin']."', '".$playersCount."', '".$maxPlayers."', '".time()."')");
		}
		
		$sql = mysqli_query($this->db2, "SELECT * FROM `servers` WHERE serverlogin != '".$settings['ServerLogin']."'");
		$id = 0;
		while($row = $sql->fetch_object()) {
			if($row->time < (time() - 30)) {
			} else {
				$this->serverList[$id] = array();
				$this->serverList[$id]['servername'] = $row->servername;
				$this->serverList[$id]['serverlogin'] = $row->serverlogin;
				$this->serverList[$id]['players'] = $row->players;
				$this->serverList[$id]['maxplayers'] = $row->maxplayers;
			}
			
			$id++;
		}

		$this->displayWidget();
	}
	
	/*
		DISPLAY WIDGET
	*/
	public function displayWidget($login = false) {
		global $_playerList;

		if($login == false) {
			foreach($_playerList as $key => $value) {
				$this->displayWidgetLogin($_playerList[$key]['Login']);
			}
		} else {
			$this->displayWidgetLogin($login);
		}
	}
	
	/*
		DISPLAY WIDGET TO LOGIN
	*/
	public function displayWidgetLogin($login) {
		$widget = $this->widget;
	
		if(!isset($this->currentID[$login])) $this->currentID[$login] = 0;
		$serverListID = $this->currentID[$login] * 2;
		
		//IF ONLY 3 SERVERS
		if(!isset($this->serverList[3])) {
			$widget->clearContent($login, $this->widgetIDs[0]);
			
			for($id = 0; isset($this->serverList[$id]) && $id <= 2; $id++) {
				$players = $this->serverList[$id]['players'].' / '.$this->serverList[$id]['maxplayers'];
			
				$widget->addContent('<td width="12" ml="maniaplanet://#join='.$this->serverList[$id]['serverlogin'].'@TMCanyon">'.$this->serverList[$id]['servername'].'</td><td width="0.5"></td><td width="3">'.$players.'</td>', $login, $this->widgetIDs[0]);
			}
		} 
		// IF MORE THAN 3 SERVERS
		else {
			//PREV PAGE BUTTON
			if(isset($this->serverList[$serverListID - 2])) {
				$widget->addCodeToLogin('<quad posn="0.2 -3 0" halign="center" sizen="15.8 2" style="{style2}" substyle="{substyle2}" />
										<label posn="-3.5 -3.35 1" halign="center" scale="0.6" text="Prev" />
										<quad posn="-6.5 -3 1" sizen="2 2" halign="center" style="Icons64x64_1" substyle="ArrowPrev" action="'.$this->mlids[0].'" />', $login, $this->widgetIDs[0]);
			}
			
			//NEXT PAGE BUTTON
			if(isset($this->serverList[$serverListID + 2])) {
				$widget->addCodeToLogin('<quad posn="0.2 -3 0" halign="center" sizen="15.8 2" style="{style2}" substyle="{substyle2}" />
										<label posn="3.5 -3.35 1" halign="center" scale="0.6" text="Next" />
										<quad posn="6.5 -3 1" sizen="2 2" halign="center" style="Icons64x64_1" substyle="ArrowNext" action="'.$this->mlids[1].'" />', $login, $this->widgetIDs[0]);
			}
			
			$widget->clearContent($login, $this->widgetIDs[0]);
			$widget->addContent('<td> </td>', $login, $this->widgetIDs[0]);			
			
			for($id = 0; isset($this->serverList[$serverListID]) && $id <= 2; $id++) {			
				$players = $this->serverList[$serverListID]['players'].' / '.$this->serverList[$serverListID]['maxplayers'];
			
				$widget->addContent('<td width="12" ml="maniaplanet://#join='.$this->serverList[$serverListID]['serverlogin'].'@TMCanyon">'.$this->serverList[$serverListID]['servername'].'</td><td width="0.5"></td><td width="3">'.$players.'</td>', $login, $this->widgetIDs[0]);
				$serverListID ++;
			}
		}
			
		$widget->displayWidget($login, $this->mlids[0], $this->widgetIDs[0]);
	}
	
	/*
		MANIALINK PAGE ANSWER
	*/
	public function onManialinkPageAnswer($args) {
		//PREV PAGE
		if($args[2] == $this->mlids[0]) {
			$this->currentID[$args[1]]--;
			$this->displayWidgetLogin($args[1]);
		} 
		//NEXT PAGE
		else if($args[2] == $this->mlids[1]) {
			$this->currentID[$args[1]]++;
			$this->displayWidgetLogin($args[1]);
		}
	}
}
?>