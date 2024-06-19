<?php
//* plugin.adminpanel.php - Adminpanel
//* Version:   0.4
//* Coded by:  cyrilw, libero6
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_adminpanel extends FoxControlPlugin {
	public function onStartUp() {
		$this->registerMLIds(8);
		$this->displayAdminpanelToAll();
		
		$this->name = 'Admin Panel';
		$this->author = 'Cyril & Libero';
		$this->version = '0.4';
	}
	
	public function onPlayerConnect($args) {
		$this->displayAdminpanel($args['Login']);
	}
	
	public function onBeginChallenge($args) {
		$this->displayAdminpanelToAll();
	}
	
	public function onEndChallenge($args) {
		$this->displayAdminpanelToAll(true);
	}
	
	public function displayAdminpanelToAll($is_end_challenge = false) {
		$this->instance()->client->query('GetPlayerList', 300, 0);
		$playerlist = $this->instance()->client->getResponse();
		for($i = 0; $i < count($playerlist); $i++) {
			$this->displayAdminpanel($playerlist[$i]['Login'], $is_end_challenge);
		}
	}
	
	public function displayAdminpanel($login, $is_end_challenge = false) {
		global $settings;
		if($this->enabled == false) return;
		if($this->instance()->is_admin($login) == false) return;
		$ap_mlcode = '
		<quad posn="57.65 -20 0" sizen="18.5 3" halign="center" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'"/>
		<quad posn="62.7 -20 2" sizen="3 3" halign="center" style="Icons64x64_1" action="'.$this->mlids[0].'" substyle="ArrowNext" />
		<quad posn="60.7 -20 1" sizen="3 3" halign="center" style="Icons64x64_1" action="'.$this->mlids[1].'" substyle="ClipPause" />
		<quad posn="58.7 -20 1" sizen="3 3" halign="center" style="Icons64x64_1" action="'.$this->mlids[2].'" substyle="ArrowPrev" />
		<quad posn="56.7 -20 1" sizen="3 3" halign="center" style="Icons64x64_1" action="'.$this->mlids[3].'" substyle="ArrowFastPrev" />
		<quad posn="54.7 -20.3 2" sizen="2.4 2.4" halign="center" style="Icons64x64_1" action="'.$this->mlids[4].'" substyle="ArrowRed" />
		
		<quad posn="52.3 -20 2" sizen="3.3 3.3" halign="center" style="Icons128x128_1" action="'.$this->mlids[6].'" substyle="Challenge" />
		
		<quad posn="50 -20.3 1" sizen="2.5 2.5" halign="center" style="Icons64x64_1" action="'.$this->mlids[5].'" substyle="Buddy" />';
		
		if($is_end_challenge == true){
			$ap_mlcode = str_replace('-20', '-24', str_replace('-20.5', '-24.5', $ap_mlcode));
		}
		$this->displayManialinkToLogin($login, $ap_mlcode, $this->mlids[0]);
	}
	
	public function onButton($args) {
		if($args[2] == 1) {
			$chatAdmin = $this->getPluginInstance('chat_admin');
			$chatAdmin->onCommand(array(1 => $args[1], 2 => 'remove', 3 => array(0 => 'track', 1 => 'current')));
			
			$window = $this->window;
			$window->closeWindow($args[1]);
		}
	}
	
	public function onManialinkPageAnswer($args) {
		global $settings;
		
		//Get Infos
		$this->instance()->client->query('GetDetailedPlayerInfo', $args[1]);
		$Admin = $this->instance()->client->getResponse();
		
		$rights = $this->getRights($Admin['Login']);
		if($rights[0] == 0) return;
		else if($rights[0] == 1) require('include/op_rights.php');
		else if($rights[0] == 2) require('include/admin_rights.php');
		else if($rights[0] == 3) require('include/superadmin_rights.php');
		
		//SKIP
		if($args[2] == $this->mlids[0]){
			if($skip_challenge==true){
				$this->instance()->challenge_skip();
				$this->chat($rights[1].' $fff'.$Admin['NickName'].'$z$s $f90skipped the challenge!', 'f90');
			}
			else $this->chatToLogin($Admin['Login'], $settings['Text_wrong_rights']);
		}
		
		//FORCE END ROUND
		elseif($args[2] == $this->mlids[1]){
			if($force_end_round==true){
				$this->instance()->client->query('ForceEndRound');
				$this->chat($rights[1].' $fff'.$Admin['NickName'].'$z$s $f90forced round end!', 'f90');
			}
			else $this->chatToLogin($Admin['Login'], $settings['Text_wrong_rights']);
		}
		
		//RESTART
		elseif($args[2] == $this->mlids[3]){
			if($restart_challenge==true){
				global $chall_restarted_admin;
				$chall_restarted_admin = true;
				$this->instance()->client->query('RestartChallenge');
				$this->chat($rights[1].' $fff'.$Admin['NickName'].'$z$s $f90restarted the challenge!', 'f90');
			}
			else $this->chatToLogin($Admin['Login'], $settings['Text_wrong_rights']);
		}
		
		//REPLAY
		else if($args[2] == $this->mlids[2]) {
			$chatAdmin = $this->getPluginInstance('chat_admin');
			
			if($chatAdmin !== false) {
				$chatAdmin->onCommand(array(1 => $args[1], 2 => 'replay'));
			}	
		}
		
		//CANCEL VOTE
		elseif($args[2] == $this->mlids[4]){
			if($cancel_vote==true){
				$this->instance()->client->query('CancelVote');
				$this->chat($rights[1].' $fff'.$Admin['NickName'].'$z$s $f90canceled vote!', 'f90');
			}
			else $this->chatToLogin($Admin['Login'], $settings['Text_wrong_rights']);
		}
		
		//DELETE TRACK
		else if($args[2] == $this->mlids[6]) {
			$chatAdmin = $this->getPluginInstance('chat_admin');
			
			if($chatAdmin !== false) {
				$window = $this->window;
				$window->init();
				$window->title('$fffDelete Map');
				$window->displayAsTable(true);
				$window->size(30, '');
				$window->posY('40');
				$window->target('onButton', $this);
				
				$window->content('<td width="25" align="center">Do you really want to delete this Map?</td>');
				
				$window->addButton('Yes', '7', false);
				$window->addButton('', '3', false);
				$window->addButton('No', '7', true);
				
				$window->show($args[1]);
			}
		}
		
		//OPEN PLAYERLIST
		elseif($args[2] == $this->mlids[5]){
			$pluginPlayers = $this->getPluginInstance('plugin_players');
			if($pluginPlayers === false) {
				$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
			} else {
				$pluginPlayers->onCommand(array(1 => $args[1], 2 => 'players', 3 => array(0 => 'admin')));
			}
		}
	}
}
?>