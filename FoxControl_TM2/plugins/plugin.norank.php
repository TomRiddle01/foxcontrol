<?php
//* plugin.norank.php - No Rank
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_norank extends FoxControlPlugin {
public $challenges = array();
	public $chall_users = array();
	public function onStartUp() {
		$this->registerCommand('norank', 'Shows maps without a record from you', false);
		$this->registerMLIds(27);
		
		$this->name = 'No Rank challenges';
		$this->author = 'matrix142';
		$this->version = '0.5';
	}
	public function onCommand($args) {
		if($args[2] == 'norank') {
			$this->displayList($args[1]);
		}
	}
	public function onManialinkPageAnswer($args) {	
		if($args[2] == $this->mlids[0]) {
			$this->displayList($args[1]);
		} else if($args[2] >= $this->mlids[2] && $args[2] <= $this->mlids[26]) {
			if($this->instance()->pluginIsActive('plugin.jukebox.php') == true) {
				$challenge_page_id = $this->chall_users[$args[1]];
				$challenge_page_id = $challenge_page_id*25;
				$jukedchallengex = ($args[2] - $this->mlids[2]+$challenge_page_id);		
				$jukedchallenge = $this->challenges[$jukedchallengex];
				plugin_jukebox::jukeChallenge($jukedchallenge['FileName'], $args[1], true);
			}
		} else if($args[2] == $this->mlids[1]) $this->closeMl($this->mlids[1], $args[1]);
	}
	public function onPages($args) {
		if($args[2] == 1) $this->chall_users[$args[1]] = 0; // <<
		elseif($args[2] == 2 && $this->chall_users[$args[1]] > 0) $this->chall_users[$args[1]]--; // <
		elseif($args[2] == 6) $this->chall_users[$args[1]]++; // >
		elseif($args[2] == 7) $this->chall_users[$args[1]] = floor(count($this->challenges) / 25);
		$this->displayList($args[1]);
	}
	public function writeChallenges($login) {
		$this->challenges = array();
		
		//Get Challenge List
		$this->instance()->client->query('GetMapList', 1000, 0);
		$challenge_list = $this->instance()->client->getResponse();
		
		for($i = 0; $i < count($challenge_list); $i++) {
			$sql = mysqli_query($this->db, "SELECT * FROm `records` WHERE challengeid = '".$challenge_list[$i]['UId']."' AND playerlogin = '".$login."'");
			if(!$row = $sql->fetch_object()) {
				//Select Votes
				$mysql = mysqli_query($this->db, "SELECT * FROM `karma` WHERE challengeid = '".$challenge_list[$i]['UId']."'");

				//Write Karma Array
				$karma = 0;
				$karma_count = 0;
				while($kar_currvote = $mysql->fetch_object()) {
					if($kar_currvote->playerlogin!=='root') {
						$karma += $kar_currvote->vote;
						$karma_count++;
					}
				}
		
				if($karma != 0) {
					$karmavote = $karma/$karma_count;
					$karmavote = round($karmavote, 1);
				}
				else {
					$karmavote = 'No votes';
				}
		
				$this->challenges[] = array('Name' => $challenge_list[$i]['Name'], 'FileName' => $challenge_list[$i]['FileName'], 'Author' => $challenge_list[$i]['Author'], 'Environnement' => $challenge_list[$i]['Environnement'], 'Karma' => $karmavote);
			}
		}
	}
	public function displayList($login) {
		$this->writeChallenges($login);
	
		if(!isset($this->chall_users[$login])) $this->chall_users[$login] = 0;
		$challenge_page_id = $this->chall_users[$login];
		$challenge_page_id = $challenge_page_id*25;
		$challenge_page_id_number_2 = $challenge_page_id-25;
		
		$curr_challid = $this->chall_users[$login] * 25;
		if(isset($this->challenges[$curr_challid - 25])) $chall_prev_page = true;
		else $chall_prev_page = false;
		if(isset($this->challenges[$curr_challid + 25])) $chall_next_page = true;
		else $chall_next_page = false;
		
		//Include window class
		$window = $this->window;
		$window->init();
		$window->title('$fffNo Rank');
		$window->displayAsTable(true);
		$window->size(70, '');
		$window->posY('40');
		$window->target('onPages', $this);
		if($chall_prev_page == true){
			$window->addButton('<<<', '7', false);
			$window->addButton('<', '7', false);
		} else {
			$window->addButton('', '7', false);
			$window->addButton('', '7', false);
		}
		$window->addButton('', '15.5', false);
		$window->addButton('Close', '10', true);
		$window->addButton('', '15.5', false);
		if($chall_next_page == true){
			$window->addButton('>', '7', false);
			$window->addButton('>>>', '7', false);
		} else {
			$window->addButton('', '7', false);
			$window->addButton('', '7', false);
		}
		
		$window->content('<td width="3">$iID</td><td width="30">$iMapname</td><td width="1"/><td width="13">$iAuthor</td><td width="1"/><td width="10">$iEnvironment</td><td width="1"></td><td width="12">$iVotes</td>');
		
		$chall_code = '';
		for($i = 0; isset($this->challenges[$curr_challid]) && $i <= 24; $i++)
		{
			$chall_ml_id = $this->mlids[2] + $i;
			if($this->instance()->pluginIsActive('plugin.jukebox.php') == true) $window->content('<td width="3">'.($curr_challid + 1).'</td><td width="30" id="'.$chall_ml_id.'">'.htmlspecialchars($this->challenges[$curr_challid]['Name']).'</td><td width="1"/><td width="13">'.htmlspecialchars($this->challenges[$curr_challid]['Author']).'</td><td width="1"/><td width="10">'.$this->challenges[$curr_challid]['Environnement'].'</td><td width="1"></td><td width="12">'.$this->challenges[$curr_challid]['Karma'].'</td>');
			else $window->content('<td width="3">'.($curr_challid + 1).'</td><td width="30">'.htmlspecialchars($this->challenges[$curr_challid]['Name']).'</td><td width="1"/><td width="13">'.htmlspecialchars($this->challenges[$curr_challid]['Author']).'</td><td width="1"/><td width="10">'.$this->challenges[$curr_challid]['Environnement'].'</td><td width="1"></td><td width="12">'.$this->challenges[$curr_challid]['Karma'].'</td>');
			$curr_challid++;
		}
		
		$window->show($login);
	}
}