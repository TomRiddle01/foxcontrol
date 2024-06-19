<?php
//* plugin.music.php - Listen to music :)
//* Version:   0.4
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_music extends FoxControlPlugin {
	public $songsPerPage = 17;
	public $startList = 0;
	public $config;
	
	public function onStartUp () {
		$this->name = 'Music';
		$this->author = 'matrix142';
		$this->version = '0.4';
		
		//Load XML Data
		$this->config = $this->loadConfig();
		$url = $this->config->url;
		$count = $this->config->songs->song;
		$count2 = count($count);
		
		$this->registerMLIds(($count2+5));
		$this->registerCommand('music', 'Displays the Music Jukebox', false);
		
		$this->music_play();
		$this->displayMusicPanel();
	}

	public function onPlayerConnect ($connectedplayer){
		global $db, $music_mlcode;
	
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".trim($connectedplayer['Login'])."'";
		$mysql = mysqli_query($db, $sql);
	
		if($mysql->fetch_object()){
			$this->displayMusicPanel($connectedplayer['Login']);
		}
	}

	public function onBeginChallenge ($args){
		$this->displayMusicPanel();
		$this->music_play();
	}

	function onEndChallenge ($args) {
		global $music_mlcode, $songID, $newSongID, $name, $play;
	
		if($play != "no"){
			$songID++;
			$this->nextSong($songID, $newSongID, 'no');
			$this->chat('$z$i$s$0afNext song: '.$name.'');
		}
	
		$this->closeMl($this->mlids[0]);
	}
	
	public function displayMusicPanel ($login = false) {
		global $settings;
	
		$code = '
		<frame posn="0 -0.3 0">
		<quad posn="59.5 -22.75 0" sizen="15 3" halign="center" style="'.$settings['default_style2'].'" action="'.$this->mlids[4].'" substyle="'.$settings['default_substyle2'].'"/>
		<quad posn="62.7 -23.25 1" sizen="2.2 2.2" halign="center" style="Icons64x64_1" action="'.$this->mlids[1].'" substyle="ClipPlay" />
		<quad posn="60.2 -23.25 1" sizen="2.2 2.2" halign="center" style="Icons64x64_1" action="'.$this->mlids[2].'" substyle="ClipPause" />
		<quad posn="57.7 -23.25 1" sizen="2.2 2.2" halign="center" style="Icons64x64_1" action="'.$this->mlids[3].'" substyle="ClipRewind" />
		<label posn="53.5 -23.5 1" scale="0.7" text="$zMusic" />
		<quad posn="52.9 -23.25 1" sizen="2.5 2.5" halign="center" style="Icons128x32_1" substyle="Music" />
		</frame>';
		
		if($login != false){
			$this->displayManialinkToLogin($login, $code, $this->mlids[0]);
		}else{
			$this->instance()->client->query('GetPlayerList', 300, 0);
			$playerlist = $this->instance()->client->getResponse();
			
			$id = 0;
			while(isset($playerlist[$id]['Login'])){
				if($this->instance()->is_admin($playerlist[$id]['Login'])){
					$this->displayManialinkToLogin($playerlist[$id]['Login'], $code, $this->mlids[0]);
				}
				$id++;
			}
		}
	}
	
	public function nextSong ($songID, $newSongID, $reset){
		global $songID, $newSongID, $url, $song, $name, $count2;
	
		//Check if $songID exists
		if(!isset($songID)){
			$songID = 0;
		}
	
		//Load XML Data
		$url = $this->config->url;
		$count = $this->config->songs->song;
		$count2 = count($count);
	
		//Check if song with $songID exists
		if($songID > ($count2-1)){
			$songID = 0;
		}
	
		//Check if $songID is lower than 0
		if($songID < 0){
			$songID = $count2-1;
		}
	
		//Check $newSongID (if an admin has pressed a button)
		if(isset($newSongID)){	
			if($newSongID > ($count2-1)){
				$newSongID = 0;
			}
		
			if($newSongID < 0){
				$newSongID = $count2-1;
			}
			$songID = $newSongID;
		}
	
		//Load XML Data
		$song = $this->config->songs->song[$songID];
		$name = $this->songs->name[$songID];
	
		//Reset $newSongID
		if($reset == "yes"){
			$newSongID = null;
		}
	}

	public function music_play (){
		global $url, $song, $play, $songID, $newSongID;
	
		$this->nextSong($songID, $newSongID, 'yes');
	
		//Play Music
		if($play != "no"){
			$this->instance()->client->query('SetForcedMusic', true, $url.$song);
		}
	}

	/*
	Get Answers of pressed buttons
	Next: Set $newSongID +1
	Stop: Set $play = "no"
	Prev: Set $newSongID -1
	*/
	public function onManialinkPageAnswer ($ManialinkPageAnswer){
		global $count2;
		
		//Next Song
		if($ManialinkPageAnswer[2] == $this->mlids[1]){
			$login = $ManialinkPageAnswer[1];
			if($this->instance()->is_admin($login) == true){
				global $songID, $newSongID, $name;
			
				$this->instance()->client->query('GetDetailedPlayerInfo', $ManialinkPageAnswer[1]);
				$Playerinfo = $this->instance()->client->getResponse();
			
				$newSongID = $songID+1;
			
				$this->nextSong($songID, $newSongID, 'no');
				$this->chat('$z$i$s$f90Admin '.$Playerinfo['NickName'].' $z$i$s$0afsets next song to '.$name.'');
			}
		}
		
		//Stop/start playing
		if($ManialinkPageAnswer[2] == $this->mlids[2]){
			$login = $ManialinkPageAnswer[1];
			if($this->instance()->is_admin($login) == true){
				global $play;
				if($play == "no"){
					$play = "yes";
					$this->chat('$z$i$s$0afMusic will play at begin of a new map!');
				}else{
					$play = "no";
					$this->chat('$z$i$s$0afMusic will stop at end of this map!');
					$this->instance()->client->query('SetForcedMusic', false, '');
				}
			}
		}
		
		//Prev Song
		if($ManialinkPageAnswer[2] == $this->mlids[3]){
			$login = $ManialinkPageAnswer[1];
			if($this->instance()->is_admin($login) == true){
				global $songID, $newSongID, $name;
			
				$this->instance()->client->query('GetDetailedPlayerInfo', $ManialinkPageAnswer[1]);
				$Playerinfo = $this->instance()->client->getResponse();
			
				$newSongID = $songID-1;
			
				$this->nextSong($songID, $newSongID, 'no');
				$this->chat('$z$i$s$f90Admin '.$Playerinfo['NickName'].' $z$i$s$0afsets next song to '.$name.'');
			}	
		}
		
		//Open Jukebox
		if($ManialinkPageAnswer[2] == $this->mlids[4]){
			$this->music_jukebox($ManialinkPageAnswer[1]);
		}
		
		if($ManialinkPageAnswer[2] >= $this->mlids[5] AND $ManialinkPageAnswer[2] <= $this->mlids[(5+$count2)-1]){
			global $newSongID, $songID, $name;
		
			$id = $ManialinkPageAnswer[2] - $this->mlids[5];
			$newSongID = $id;
		
			$this->nextSong($songID, $newSongID, 'no');
		
			$this->instance()->client->query('GetDetailedPlayerInfo', $ManialinkPageAnswer[1]);
			$Playerinfo = $this->instance()->client->getResponse();
		
			$this->chat(''.$Playerinfo['NickName'].'$z$i$s$0af juked Song: '.$name.'');
		}
	}	

	public function onCommand ($args){
	
		if($args[2] == 'music'){
			$this->music_jukebox($args[1]);
		}
	}

	public function onButtonPressed($args) {
		global $i3;
		
		if($args[2] == 1) { //<
			if($this->startList <= $this->songsPerPage) $this->startList = 0;
			else $this->startList = $this->startList - $this->songsPerPage;
			$this->music_jukebox();
		} else if($args[2] == 3) { //>
			$this->startList = $this->startList + $this->songsPerPage;
			$this->music_jukebox();
		}
	}
	
	public function music_jukebox ($login){
		global $count2, $i3;
	
		if(empty($site)) $site = 0;
		$window = $this->window;
		$window->init();
		$window->title('Music Jukebox');
		$window->close(true);
		$window->displayAsTable(true);
		
		$window->size(42, '');
		$window->content('<td width="3">ID</td><td width="30">Songname</td>');
		$window->content(' ');
		
		$i2 = 0;
		if($this->startList == 0) $i3 = 0;
		else $i3 = $this->startList;
		for($i = 0; $i < $count2; $i++) {
			if($i2 >= $this->songsPerPage) break;
			if(!isset($this->config->songs->name[$i3])) break;
		
			$window->content('<td width="3">'.($i3 + 1).'</td><td width="30" id="'.$this->mlids[(5+$i3)].'">'.htmlspecialchars($this->config->songs->name[$i3]).'</td>');
				
			$i2++;
			$i3++;
		}
			
		if($this->startList >= ($this->songsPerPage - 1)) $window->addButton('<', 7, false);
		else $window->addButton('', 7, false);
			
		$window->addButton('Close', 15, true);
			
		if($this->startList < $this->songsPerPage AND ($this->startList + $count2) > $this->songsPerPage) $window->addButton('>', 7, false);
		else $window->addButton('', 7, false);
			
		$window->target('onButtonPressed', $this);
		$window->show($login);
	}
}
?>