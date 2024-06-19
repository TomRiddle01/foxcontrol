<?php
//* plugin.foxpoints.php - FoxPoints
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_foxpoints extends FoxControlPlugin {
	public $config;
	public $posn_foxPointsScorePanel;
	public function $foxCode;
	public function $serverInfo;
	
	public function onStartUp() {
		global $widget_code, $settings;
	
		$this->registerMLIds(1);
		
		$this->config = $this->loadConfig();
		
		$this->posn_foxPointsScorePanel = $this->config->posn;
		$this->posn_foxPointsScorePanel = explode(' ', $this->posn_foxPointsScorePanel);
		
		$widget_code = '
		<quad posn="'.$this->posn_foxPointsScorePanel[0].' '.($this->posn_foxPointsScorePanel[1]-2.75).' 1" sizen="15 15" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
		<quad posn="'.($this->posn_foxPointsScorePanel[0]+0.5).' '.($this->posn_foxPointsScorePanel[1]-3).'2" sizen="14 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
		<quad posn="'.($this->posn_foxPointsScorePanel[0]+0.5).' '.($this->posn_foxPointsScorePanel[1]-3).' 2" sizen="14 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
		<label posn="'.($this->posn_foxPointsScorePanel[0]+7.5).' '.($this->posn_foxPointsScorePanel[1]-3.5).' 3" scale="0.7" halign="center" text="$o$FFFFoxPoints" />';
	
		$this->instance()->client->query('GetDetailedPlayerInfo', $settings['ServerLogin']);
		$this->serverInfo = $this->instance()->client->getResponse();
	
		//Authenticate Server
		$authenticateServer = $this->authenticateServer();
		
		if($authenticateServer != false) {
			$this->foxCode = $authenticateServer;
		} else {
			return;
		}
	}

	public function authenticateServer() {
		global $settings;
	
		//Authenticate the server to get the FoxCode
		$authenticateServer = file_get_contents('http://scripts.fox-control.de/foxpoints/authenticate.php?serverlogin='.$settings['ServerLogin'].'&path='.$this->serverInfo['Path']);
		
		//Check if there isn't an error
		if($authenticateServer == '1') {
			console('***********************************');
			console('FOXPOINTS: ERROR ON AUTHENTICATION');
			console('ARE YOU SURE YOUR SERVER IS REGISTERED AT http://scripts.fox-control.de/foxpoints/register.php???');
			console('************************************');
			
			return false;
		} else {
			return $authenticateServer;
		}
	}
	
	public function onEndChallenge($args) {
		global $settings, $widget_code;
	
		$fp = fsockopen("www.fox-control.de", 80, $errno, $errstr, 5);
	
		if (!$fp) {
			console('!!!FOXPOINTS ERROR: MASTERSERVER DOES NOT RESPONSE!!!');
			console($errstr .'('.$errno.')');
		} else {
			fwrite($fp, "GET / HTTP/1.1\r\n");
	
			
			
			$this->instance()->client->query('GetCurrentChallengeInfo');
			$challengeInfo = $this->instance()->client->getResponse();
			
			$this->instance()->client->query('GetPlayerList', 200, 0);
			$playerList = $this->instance()->client->getResponse();
		
			$sendTimes = file_get_contents('http://scripts.fox-control.de/foxpoints/sendTimes.php?serverlogin='.$settings['ServerLogin'].'&times='.serialize($currentRanking).'&UID='.$challengeInfo['UId'].'');
			$getPoints = file_get_contents('http://scripts.fox-control.de/foxpoints/getPoints.php?serverlogin='.$settings['ServerLogin'].'&players='.serialize($playerList).'&UID='.$challengeInfo['UId'].'');
			
			console($sendTimes);
			
			$getPoints = unserialize($getPoints);
			
			foreach($getPoints as $key => $value) {
				$code_foxpoints = '
				<label posn="'.($this->posn_foxPointsScorePanel[0]+0.5).' '.($this->posn_foxPointsScorePanel[1]-6).' 5" sizen="10 2" textsize="1" text="$oNew Points"/>
				<label posn="'.($this->posn_foxPointsScorePanel[0]+9).' '.($this->posn_foxPointsScorePanel[1]-6).' 5" sizen="3 2" textsize="1" text="$o$09f'.$value['lastPoints'].'"/>
				<label posn="'.($this->posn_foxPointsScorePanel[0]+0.5).' '.($this->posn_foxPointsScorePanel[1]-8).' 5" sizen="10 2" textsize="1" text="$oPoints"/>
				<label posn="'.($this->posn_foxPointsScorePanel[0]+9).' '.($this->posn_foxPointsScorePanel[1]-8).' 5" sizen="3 2" textsize="1" text="$o$09f'.$value['points'].'"/>
				
				<label posn="'.($this->posn_foxPointsScorePanel[0]+0.5).' '.($this->posn_foxPointsScorePanel[1]-12).' 5" sizen="10 2" textsize="1" text="$oBest Player"/>
				<label posn="'.($this->posn_foxPointsScorePanel[0]+9).' '.($this->posn_foxPointsScorePanel[1]-12).' 5" sizen="5.75 2" textsize="1" text="$o$09fNickname"/>
				<label posn="'.($this->posn_foxPointsScorePanel[0]+0.5).' '.($this->posn_foxPointsScorePanel[1]-14).' 5" sizen="10 2" textsize="1" text="$oPoints"/>
				<label posn="'.($this->posn_foxPointsScorePanel[0]+9).' '.($this->posn_foxPointsScorePanel[1]-14).' 5" sizen="3 2" textsize="1" text="$o$09fPoints"/>';
				
				$this->displayManialinkToLogin($value['Login'], $widget_code.$code_foxpoints, $this->mlids[0]);
			}
		}
	}
	
	public function onBeginChallenge($args) {
		$this->closeMl($this->mlids[0]);
	}
}
?>