<?php
//* plugin.scorepanel.php - Scorepanel
//* Version:   0.6
//* Coded by:  cyrilw, libero6, matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_scorepanel extends FoxControlPlugin {
	public $config;
	public $posn_topdonate;
	public $posn_toptracks;
	public $posn_mostactive;
	public $posn_nexttrack;
	public $posn_localrecords;
	public $posn_dedirecords;
	
	public function onStartUp() {
		global $settings, $widget_code_topdonate, $widget_code_toptracks, $widget_code_mostactive, $widget_code_nexttrack, $widget_code_localrecords, $widget_code_dedirecords;
	
		//Register MLIDs
		$this->registerMLIds(1);
		
		$this->name = 'Scorepanel';
		$this->author = 'matrix142, Cyril, Libero';
		$this->version = '0.6';
		
		//Load Config file
		$this->config = $this->loadConfig();
		
		//Get posns
		$this->posn_topdonate = $this->config->posns->top_donate;		
		$this->posn_toptracks = $this->config->posns->top_tracks;
		$this->posn_mostactive = $this->config->posns->most_active;
		$this->posn_nexttrack = $this->config->posns->next_track;
		$this->posn_localrecords = $this->config->posns->local_records;
		$this->posn_dedirecords = $this->config->posns->dedi_records;
		
		if($this->posn_topdonate != 'false'){
			$this->posn_topdonate = explode(' ', $this->posn_topdonate);
			/*
			CHANGE HERE THE STYLE OF THE TOP DONATE WIDGET
			$posn_topdonate[0] = X-Posn, $posn_topdonate[1] = Y-Posn set in plugin.scorepanel.config.xml
			*/
			$widget_code_topdonate = '
			<quad posn="'.$this->posn_topdonate[0].' '.$this->posn_topdonate[1].' 1" sizen="20 15.6" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.($this->posn_topdonate[0]+0.5).' '.($this->posn_topdonate[1]-3).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="'.($this->posn_topdonate[0]+0.5).' '.($this->posn_topdonate[1]-3).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="'.($this->posn_topdonate[0]+10).' '.($this->posn_topdonate[1]-3.5).' 3" scale="0.7" halign="center" text="$o$FFF'.$this->config->settings->names->headline_top_donate.'" />';
		}else {
			$widget_code_topdonate = '';
		}
		
		if($this->posn_toptracks != 'false') {
			$this->posn_toptracks = explode(' ', $this->posn_toptracks);
			/*
			CHANGE HERE THE STYLE OF THE TOP TRACKS WIDGET
			$posn_toptracks[0] = X-Posn, $posn_toptracks[1] = Y-Posn set in plugin.scorepanel.config.xml
			*/
			$widget_code_toptracks = '
			<quad posn="'.$this->posn_toptracks[0].' '.$this->posn_toptracks[1].' 1" sizen="20 15.6" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.($this->posn_toptracks[0]+0.5).' '.($this->posn_toptracks[1]-3).'2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="'.($this->posn_toptracks[0]+0.5).' '.($this->posn_toptracks[1]-3).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="'.($this->posn_toptracks[0]+10).' '.($this->posn_toptracks[1]-3.5).' 3" scale="0.7" halign="center" text="$o$FFF'.$this->config->settings->names->headline_top_tracks.'" />';
		}else {
			$widget_code_toptracks = '';
		}
		
		if($this->posn_mostactive != 'false') {
			$this->posn_mostactive = explode(' ', $this->posn_mostactive);
			/*
			CHANGE HERE THE STYLE OF THE MOST ACTIVE WIDGET
			$posn_mostactive[0] = X-Posn, $posn_mostactive[1] = Y-Posn set in plugin.scorepanel.config.xml
			*/
			$widget_code_mostactive = '
			<quad posn="'.$this->posn_mostactive[0].' '.$this->posn_mostactive[1].' 1" sizen="20 15.6" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.($this->posn_mostactive[0]+0.5).' '.($this->posn_mostactive[1]-3).'2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="'.($this->posn_mostactive[0]+0.5).' '.($this->posn_mostactive[1]-3).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="'.($this->posn_mostactive[0]+10).' '.($this->posn_mostactive[1]-3.5).' 3" scale="0.7" halign="center" text="$o$FFF'.$this->config->settings->names->headline_most_active.'" />';
		}else {
			$widget_code_mostactive = '';
		}
		
		if($this->posn_nexttrack != 'false') {
			$this->posn_nexttrack = explode(' ', $this->posn_nexttrack);
			/*
			CHANGE HERE THE STYLE OF THE NEXT TRACK WIDGET
			$posn_nexttrack[0] = X-Posn, $posn_nexttrack[1] = Y-Posn set in plugin.scorepanel.config.xml
			*/
			$widget_code_nexttrack = '
			<quad posn="'.$this->posn_nexttrack[0].' '.$this->posn_nexttrack[1].' 1" sizen="20 15.6" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-3).'2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-3).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="'.($this->posn_nexttrack[0]+10).' '.($this->posn_nexttrack[1]-3.5).' 3" scale="0.7" halign="center" text="$o$FFF'.$this->config->settings->names->headline_next_track.'" />';
		}else {
			$widget_code_nexttrack = '';
		}
		
		if($this->posn_localrecords != 'false') {
			$this->posn_localrecords = explode(' ', $this->posn_localrecords);
			/*
			CHANGE HERE THE STYLE OF THE LOCAL RECORDS WIDGET
			$posn_localrecords[0] = X-Posn, $posn_localrecords[1] = Y-Posn set in plugin.scorepanel.config.xml
			*/
			$widget_code_localrecords = '
			<quad posn="'.$this->posn_localrecords[0].' '.$this->posn_localrecords[1].' 1" sizen="20 53.5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.($this->posn_localrecords[0]-2.25).' '.($this->posn_localrecords[1]-0.5).' 2" sizen="21.8 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="'.($this->posn_localrecords[0]-2.25).' '.($this->posn_localrecords[1]-0.5).' 2" sizen="21.8 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="'.($this->posn_localrecords[0]+2.5).' '.($this->posn_localrecords[1]-1).' 3" scale="0.7" text="$o$FFF'.$this->config->settings->names->headline_local_records.'" />';
		}else {
			$widget_code_localrecords = '';
		}
		
		if($this->posn_dedirecords != 'false' AND $this->getPluginInstance('plugin_dedimania') !== false) {
			$this->posn_dedirecords = explode(' ', $this->posn_dedirecords);
			/*
			CHANGE HERE THE STYLE OF THE DEDIMANIA RECORDS WIDGET
			$posn_dedirecords[0] = X-Posn, $posn_dedirecords[1] = Y-Posn set in plugin.scorepanel.config.xml
			*/
			$widget_code_dedirecords = '
			<quad posn="'.$this->posn_dedirecords[0].' '.$this->posn_dedirecords[1].' 1" sizen="20 41.75" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
			<quad posn="'.($this->posn_dedirecords[0]+0.5).' '.($this->posn_dedirecords[1]-0.5).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="'.($this->posn_dedirecords[0]+0.5).' '.($this->posn_dedirecords[1]-0.5).' 2" sizen="19 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="'.($this->posn_dedirecords[0]+10).' '.($this->posn_dedirecords[1]-1).' 3" scale="0.7" halign="center" text="$o$FFF'.$this->config->settings->names->headline_dedi_records.'" />';	
		} else {
			$widget_code_dedirecords = '';
		}
	}	
	public function onEndChallenge($args) {
		global $widget_code_topdonate, $widget_code_toptracks, $widget_code_mostactive, $widget_code_nexttrack, $widget_code_localrecords, $widget_code_dedirecords;
		
		//TOP DONATE
		if($this->posn_topdonate != 'false') {
			$code_topdonate = '';
			$y = 0;
			
			for($i=0; $i<5; $i++) {
				$sql = mysqli_query($this->db, "SELECT * FROM `players` ORDER BY donations DESC LIMIT ".$i.", 1");
			
				if($row = $sql->fetch_object() AND $row->donations != '0') {
					$code_topdonate .= '
					<label posn="'.($this->posn_topdonate[0]+0.5).' '.($this->posn_topdonate[1]-6-$y).' 5" sizen="4.5 2" textsize="1" text="'.$row->donations.'"/>
					<label posn="'.($this->posn_topdonate[0]+5.75).' '.($this->posn_topdonate[1]-6-$y).' 5" sizen="13 2" textsize="1" text="'.htmlspecialchars($row->nickname).'"/>';
			
					$y += 1.8;
				}
			}
		}
		
		//TOP TRACKS
		if($this->posn_toptracks != 'false') {
			$code_toptracks = '';
			$y = 0;
			
			for($i=0; $i<5; $i++) {
				$sql = mysqli_query($this->db, "SELECT * FROM `karma` WHERE playerlogin = 'karma_total' AND vote > 0 ORDER BY vote DESC LIMIT ".$i.", 1");
			
				if($row = $sql->fetch_object()) {
					$this->instance()->client->query('GetChallengeInfo', $row->challengeid);
					$challengeInfo = $this->instance()->client->getResponse();
					
					if(!empty($challengeInfo)) {
						$code_toptracks .= '
						<label posn="'.($this->posn_toptracks[0]+0.5).' '.($this->posn_toptracks[1]-6-$y).' 5" sizen="4.5 2" textsize="1" text="$o$09f'.$row->vote.'"/>
						<label posn="'.($this->posn_toptracks[0]+5).' '.($this->posn_toptracks[1]-6-$y).' 5" sizen="14.5 2" textsize="1" text="'.htmlspecialchars(stripslashes($row->challengename)).'"/>';
					}
			
					$y += 1.8;
				}
			}
		}
		
		//MOST ACTIVE
		if($this->posn_mostactive != 'false') {
			$code_mostactive = '';
			$y = 0;
			
			for($i=0; $i<5; $i++) {
				$sql = mysqli_query($this->db, "SELECT * FROM `players` ORDER BY timeplayed DESC LIMIT ".$i.", 1");
			
				if($row = $sql->fetch_object()) {
					if($this->instance()->formattime_hour($row->timeplayed) != 0) {
						$time = $this->instance()->formattime_hour($row->timeplayed);
					} else if($this->instance()->formattime_minute($row->timeplayed) != 0) {
						$time = $this->instance()->formattime_minute($row->timeplayed);
					} else {
						$time = 0;
					}
					
					if($time != 0) {
						$code_mostactive .= '
						<label posn="'.($this->posn_mostactive[0]+0.5).' '.($this->posn_mostactive[1]-6-$y).' 5" sizen="4.5 2" textsize="1" text="$o$09f'.$time.'"/>
						<label posn="'.($this->posn_mostactive[0]+5).' '.($this->posn_mostactive[1]-6-$y).' 5" sizen="14.5 2" textsize="1" text="'.htmlspecialchars(stripslashes($row->nickname)).'"/>';
			
						$y += 1.8;
					}
				}
			}
		}
		
		//NEXT TRACK
		if($this->posn_nexttrack != 'false') {
			$this->instance()->client->query('GetNextChallengeInfo');
			$nca = $this->instance()->client->getResponse();
			
			$code_nexttrack = '
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-5.75).' 5" sizen="2 2" style="Icons128x128_1" substyle="NewTrack" />
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-7.75).' 5" sizen="2 2" style="Icons64x64_1" substyle="Buddy" />
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-9.75).' 5" sizen="2 2" style="Icons128x128_1" substyle="United" />
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-11.75).' 5" sizen="2 2" style="Icons128x128_1" substyle="Manialink" />
			<quad posn="'.($this->posn_nexttrack[0]+0.5).' '.($this->posn_nexttrack[1]-13.75).' 5" sizen="2 2" style="Icons64x64_1" substyle="RestartRace" />
			
			<label posn="'.($this->posn_nexttrack[0]+2.5).' '.($this->posn_nexttrack[1]-6).' 5" scale="0.6" sizen="35 2" text="'.$nca['Name'].'"/>
			<label posn="'.($this->posn_nexttrack[0]+2.5).' '.($this->posn_nexttrack[1]-8).' 5" scale="0.6" sizen="35 2" text="'.$nca['Author'].'"/>
			<label posn="'.($this->posn_nexttrack[0]+2.5).' '.($this->posn_nexttrack[1]-10).' 5" scale="0.6" sizen="35 2" text="'.$nca['Environnement'].'"/>
			<label posn="'.($this->posn_nexttrack[0]+2.5).' '.($this->posn_nexttrack[1]-12).' 5" scale="0.6" sizen="35 2" text="'.$nca['Mood'].'"/>
			<label posn="'.($this->posn_nexttrack[0]+2.5).' '.($this->posn_nexttrack[1]-14).' 5" scale="0.6" sizen="35 2" text="'.$this->instance()->format_time($nca['AuthorTime']).'"/>';
		}
		
		//LOCAL RECORDS
		if($this->posn_localrecords != 'false') {
			$pluginRecords = $this->getPluginInstance('plugin_records');
			if($pluginRecords != false) {
				$recsArray = $pluginRecords->recsArray;
			}
			
			$code_localrecords = '';
			$y = 0;
			
			for($i=0; isset($recsArray[$i]) && $i<25; $i++) {
				$code_localrecords .= '
				<label posn="'.($this->posn_localrecords[0]+0.75).' '.($this->posn_localrecords[1]-4-$y).' 4" sizen="1 2" textsize="0" text="$09f$o'.($i+1).'"/>
				<label posn="'.($this->posn_localrecords[0]+2).' '.($this->posn_localrecords[1]-4-$y).' 4" sizen="4 2" textsize="0" text="$fff'.$this->instance()->format_time($recsArray[$i]['time']).'"/>
				<label posn="'.($this->posn_localrecords[0]+5.75).' '.($this->posn_localrecords[1]-3.75-$y).' 4" sizen="13.5 2" textsize="1" text="$fff'.htmlspecialchars(stripslashes($recsArray[$i]['NickName'])).'"/>';
			
				$y += 2;
			}
		}
		
		//DEDI RECORDS
		$code_dedirecords = '';
		
		if($this->posn_dedirecords != 'false' AND $this->getPluginInstance('plugin_dedimania') !== false) {
			$pluginDedi = $this->getPluginInstance('plugin_dedimania');
			if($pluginDedi != false) {
				$dediArray = $pluginDedi->dediArray;
			}
		
			$y = 0;
		
			if(($gmode = $pluginDedi->checkGameMode($pluginDedi->_gameinfo,$pluginDedi->_mapinfo)) === false ) {
				$code_dedirecords .= '<label posn="'.($this->posn_dedirecords[0]+0.75).' '.($this->posn_dedirecords[1]-4-$y).' 4" sizen="16.5 2" textsize="1.2" text="$fffCurrent Game Mode is not supported"/>';
			} else {			
				for($i=0; isset($dediArray[$i]) && $i<19; $i++) {
					$code_dedirecords .= '
					<label posn="'.($this->posn_dedirecords[0]+0.75).' '.($this->posn_dedirecords[1]-4-$y).' 4" sizen="1 2" textsize="0" text="$09f$o'.($i+1).'"/>
					<label posn="'.($this->posn_dedirecords[0]+2).' '.($this->posn_dedirecords[1]-4-$y).' 4" sizen="4 2" textsize="0" text="$fff'.$this->instance()->format_time($dediArray[$i]['Best']).'"/>
					<label posn="'.($this->posn_dedirecords[0]+5.75).' '.($this->posn_dedirecords[1]-3.75-$y).' 4" sizen="13.5 2" textsize="1" text="$fff'.htmlspecialchars(stripslashes($dediArray[$i]['NickName'])).'"/>';
			
					$y += 2;
				}
			}
		}
		
		$widget_code = $widget_code_topdonate.$widget_code_toptracks.$widget_code_mostactive.$widget_code_nexttrack.$widget_code_localrecords.$widget_code_dedirecords.$code_topdonate.$code_toptracks.$code_mostactive.$code_nexttrack.$code_localrecords.$code_dedirecords;
		$this->displayManialink($widget_code, $this->mlids[0]);
	}
	public function onBeginChallenge($args) {	
		$this->closeMl($this->mlids[0]);
	}
}
?>