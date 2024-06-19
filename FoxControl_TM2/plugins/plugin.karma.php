<?php
//* plugin.karma.php - Track Karma
//* Version:   0.4
//* Coded by:  cyrilw, libero6
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_karma extends FoxControlPlugin {
	/*
		START UP
	*/
	public function onStartUp() {
		global $settings, $widget_code_karma, $posn_karma;
	
		$this->name = 'Track Karma';
		$this->author = 'matrix142';
		$this->version = '0.5';
	
		$this->registerMLIds(5);
		
		$this->registerCommand('++', 'Votes $s++$s for the current track.', false);
		$this->registerCommand('+', 'Votes $s+$s for the current track.', false);
		$this->registerCommand('+-', 'Votes $s+-$s for the current track.', false);
		$this->registerCommand('-+', false, false);
		$this->registerCommand('-', 'Votes $s-$s for the current track.', false);
		$this->registerCommand('--', 'Votes $s--$s for the current track.', false);
		
		$this->config = $this->loadConfig();
		$posn_karma = $this->getPosn('karma');
		
		/*
			CHANGE WIDGET STYLE HERE
		*/
		if($posn_karma != false) {
			$widget_code_karma = '
			<quad posn="'.$posn_karma[0].' '.$posn_karma[1].' 1" sizen="16 6.5" halign="center" valign="center" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.$posn_karma[0].' '.($posn_karma[1]-2).' 0" sizen="16 2.5" halign="center" valign="center" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			
			<label posn="'.($posn_karma[0]-3.7).' '.($posn_karma[1]-1.3).' 2" text="$o$FFF5" scale="0.5" style="TextButtonBig" action="'.$this->mlids[4].'" />
			<label posn="'.($posn_karma[0]-2.2).' '.($posn_karma[1]-1.3).' 2" text="$o$FFF4" scale="0.5" style="TextButtonBig" action="'.$this->mlids[3].'" />
			<label posn="'.($posn_karma[0]-0.7).' '.($posn_karma[1]-1.3).' 2" text="$o$FFF3" scale="0.5" style="TextButtonBig" action="'.$this->mlids[2].'"/>
			<label posn="'.($posn_karma[0]+0.8).' '.($posn_karma[1]-1.3).' 2" text="$o$FFF2" scale="0.5" style="TextButtonBig" action="'.$this->mlids[1].'"/>
			<label posn="'.($posn_karma[0]+2.3).' '.($posn_karma[1]-1.3).' 2" text="$o$FFF1" scale="0.5" style="TextButtonBig" action="'.$this->mlids[0].'"/>
			
			<label posn="'.($posn_karma[0]+3.8).' '.($posn_karma[1]-1.3).' 2" text="$o$F00-" scale="0.5" style="TextButtonBig"/>
			<label posn="'.($posn_karma[0]-6.2).' '.($posn_karma[1]-1.3).' 2" text="$o$1A0+" scale="0.5" style="TextButtonBig" />
			
			<label posn="'.($posn_karma[0]-7.2).' '.($posn_karma[1]+2.45).' 2" text="$o$FFF{VOTE_AVERAGE}" sizen="5 2" scale="0.8" style="TextButtonBig" />
			<label posn="'.($posn_karma[0]-0.7).' '.($posn_karma[1]+2.45).' 2" text="$FFF{VOTES}" sizen="10 2" scale="0.6" style="TextButtonBig"/>';
		}
		
		$this->onBeginChallenge(false);
	}
	
	/*
		ON COMMAND
	*/
	public function onCommand($args) {
		if($args[2] == '++') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[4]));
		if($args[2] == '+') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[3]));
		if($args[2] == '+-') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[2]));
		if($args[2] == '-') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[1]));
		if($args[2] == '--') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
	}
	
	/*
		PLAYER CHAT
	*/
	public function onChat($args) {
		if($args[2] == '++') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[4]));
		if($args[2] == '+') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[3]));
		if($args[2] == '+-') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[2]));
		if($args[2] == '-') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[1]));
		if($args[2] == '--') $this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
	}
	
	/*
		BEGIN CHALLENGE
	*/
	public function onBeginChallenge($args) {
		global $posn_karma;
	
		if($posn_karma != false) {
			$this->getKarma();
			
			$this->instance()->client->query('GetPlayerList', 200, 0);
			$playerList = $this->instance()->client->getResponse();
				
			$i = 0;
			while(isset($playerList[$i])) {
				$this->displayKarma($playerList[$i]['Login']);
				
				$i++;
			}
		}
	}
	
	/*
		PLAYER CONNECT
	*/
	public function onPlayerConnect($args) {
		global $posn_karma;
	
		if($posn_karma != false) {			
			$this->displayKarma($args['Login']);
		}
	}
	
	/*
		GET KARMA FOR CURRENT MAP
	*/
	public function getKarma() {
		global $karmavote, $karma_count;
	
		$this->instance()->client->query('GetCurrentMapInfo');
		$kar_challinfo = $this->instance()->client->getResponse();
	
		//Select Votes
		$mysql = mysqli_query($this->db, "SELECT * FROM `karma` WHERE challengeid = '".$kar_challinfo['UId']."'");

		//Write Karma Array
		$karma = 0;
		$karma_count = 0;
		while($kar_currvote = $mysql->fetch_object()) {
			if($kar_currvote->playerlogin !== 'karma_total') {
				$karma += $kar_currvote->vote;
				$karma_count++;
			}
		}
		
		if($karma != 0) {
			$karmavote = $karma/$karma_count;
			$karmavote = round($karmavote, 2);
		}
		else {
			$karmavote = false;
		}
		
		$mysql = mysqli_query($this->db, "SELECT * FROM `karma` WHERE challengeid = '".$kar_challinfo['UId']."' AND playerlogin = 'karma_total'");
		
		if($row = $mysql->fetch_object()) {
			$sql2 = mysqli_query($this->db, "UPDATE karma SET vote = '".$karmavote."', timestamp = '".time()."' WHERE challengeid = '".$kar_challinfo['UId']."' AND playerlogin = 'karma_total'");
		} else {
			$sql = mysqli_query($this->db, "INSERT INTO karma (challengeid, challengename, playerlogin, vote, timestamp) VALUES ('".$kar_challinfo['UId']."', '".$kar_challinfo['Name']."', 'karma_total', '".$karmavote."', '".time()."')");
		}
	}
	
	/*
		DISPLAY KARMA WIDGET
	*/
	public function displayKarma($login) {
		global $widget_code_karma, $karmavote, $karma_count, $posn_karma;
		
		$karma_code = $widget_code_karma;
		
		//Create Widget Code
		if($karmavote != false) {
			$karma_code = str_replace('{VOTES}', 'Votes: '.$karma_count, $karma_code);
			$karma_code = str_replace('{VOTE_AVERAGE}', $karmavote, $karma_code);
			
			$this->instance()->client->query('GetCurrentMapInfo');
			$kar_challinfo = $this->instance()->client->getResponse();
		
			$sql = mysqli_query($this->db, "SELECT * FROM karma WHERE playerlogin = '".$login."' AND challengeid = '".$kar_challinfo['UId']."'");
			if($row = $sql->fetch_object()) {
				$karma_vote_pos = $row->vote * 1.4;
			}
		
			$karmavote = round($karmavote);
			
			$x = $posn_karma[0] + 2.65;
			$y = $posn_karma[1] + 0.95;
			
			for($i=0; $i<$karmavote; $i++) {
				$karma_code .= '<quad posn="'.$x.' '.$y.' 4" sizen="2 2" style="BgRaceScore2" halign="center" substyle="Fame" />';
				$x -= 1.5;
			}
			
			if(isset($karma_vote_pos)) {
				$x_num = $posn_karma[0] + 2.5 - $karma_vote_pos;
				$y_num = $posn_karma[1] - 0.55;
				
				$karma_code .= '<quad posn="'.$x_num.' '.$y_num.' 2" sizen="2.5 2.5" style="Icons64x64_1" substyle="LvlGreen" />';
			}
			
		} else {
			$karma_code = str_replace('{VOTES}', '', $karma_code);
			$karma_code = str_replace('{VOTE_AVERAGE}', '', $karma_code);
			
			$karma_code .= '<label posn="'.($posn_karma[0]-4.7).' '.($posn_karma[1]+1.7).' 3" scale="0.6" text="$fff$oNo votes yet"/>';
		}
		
		//Display Karma Widget
		$this->displayManialinkToLogin($login, $karma_code, $this->mlids[0]);
	}
	
	public function onManialinkPageAnswer($args) {
		if($args[2] >= $this->mlids[0] && $args[2] <= $this->mlids[4]) {
			$this->instance()->client->query('GetCurrentMapInfo');
			$kar_challinfo = $this->instance()->client->getResponse();
		
			$this->instance()->client->query('GetDetailedPlayerInfo', $args[1]);
			$playerInfo = $this->instance()->client->getResponse();
		
			//Get Vote Number
			$i = 0;
			while(true) {
				if($args[2] == $this->mlids[$i]) {
					$vote = $i + 1;
					break;
				}
				$i++;
			}
			
			$sql = mysqli_query($this->db, "SELECT * FROM karma WHERE challengeid = '".$kar_challinfo['UId']."' AND playerlogin = '".$args[1]."'");
			if($row = $sql->fetch_object()) {
				if($vote == $row->vote) return;
				
				$sql2 = mysqli_query($this->db, "UPDATE karma SET vote = '".$vote."', timestamp = '".time()."' WHERE challengeid = '".$kar_challinfo['UId']."' AND playerlogin = '".$args[1]."'");
			
				if($this->config->settings->general->show_message == '1') {
					$color = $this->config->settings->color->color_voted;
					$this->chat($playerInfo['NickName'].'$z$s'.$color.' voted $fff'.$vote.''.$color.'!', $color);
				}
			} else {
				$sql2 = mysqli_query($this->db, "INSERT INTO karma (challengeid, challengename, playerlogin, vote, timestamp) VALUES ('".$kar_challinfo['UId']."', '".$kar_challinfo['Name']."', '".$args[1]."', '".$vote."', '".time()."')");
			
				if($this->config->settings->general->show_message == '1') {
					$color = $this->config->settings->color->color_voted;
					$this->chat($playerInfo['NickName'].'$z$s'.$color.' voted $fff'.$vote.''.$color.'!', $color);
				}
			}
			
			$this->onBeginChallenge(false);
		}
	}
}
?>