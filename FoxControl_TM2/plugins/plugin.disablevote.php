<?php
//* plugin.disablevote.php - Disable Callvote
//* Version:   0.6
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_disablevote extends FoxControlPlugin {
	public $adminsOnline = array();
	public $defaultVoteTimeOut;
	public $callVoteDisabled = false;
	public $config;

	public function onStartUp() {	
		$this->name = 'Disable Callvotes';
		$this->author = 'matrix142';
		$this->version = '0.6';
		
		$this->config = $this->loadConfig();
		$this->defaultVoteTimeOut = (int) $this->config->defaultVoteTimeOut*1000;
		
		$this->setCallVote($this->defaultVoteTimeOut);
		
		$this->instance()->client->query('GetPlayerList', 200, 0);
		$playerList = $this->instance()->client->getResponse();
		
		for($i = 0; isset($playerList[$i]); $i++) {
			$this->onPlayerConnect($playerList[$i]);
		}
	}
	
	public function onPlayerConnect($connectedPlayer) {
		if($this->config->enableAutoVote == '1') {
			if($this->instance()->is_admin($connectedPlayer['Login'])) {
				$this->adminsOnline[] = $connectedPlayer['Login'];
				sort($this->adminsOnline);
			
				$this->checkCallVote();
			}
		}
	}
	
	public function onPlayerDisconnect($args) {
		if($this->config->enableAutoVote == '1') {
			if($this->instance()->is_admin($args[0])) {
				$key = array_search($args[0], $this->adminsOnline);

				unset($this->adminsOnline[$key]);
				sort($this->adminsOnline);
			
				$this->checkCallVote();
			}
		}
	}
	
	public function checkCallVote() {		
		$this->instance()->client->query('GetCallVoteTimeOut');
		$callVote = $this->instance()->client->getResponse();
		
		if($callVote['CurrentValue'] == 0) {
			$this->callVoteDisabled = true;
		} else {
			$this->callVoteDisabled = false;
		}
	
		if(isset($this->adminsOnline[0]) && $this->callVoteDisabled == false) {		
			if($this->defaultVoteTimeOut > 0) {			
				$this->setCallVote(0);
				$this->callVoteDisabled = true;
				
				$this->chat('$fff[$06fA$fffuto$06fV$fffote] Callvotes are now disabled because an Admin is online.');
			}
		} else if(!isset($this->adminsOnline[0]) && $this->callVoteDisabled == true){		
			if($this->defaultVoteTimeOut > 0) {
				$this->setCallVote($this->defaultVoteTimeOut);
				$this->callVoteDisabled = false;
			
				$this->chat('$fff[$06fA$fffuto$06fV$fffote] Callvotes are now enabled because the Admin left the game.');
			}
		}
	}
	
	public function setCallVote($callVoteTimeOut) {
		$this->instance()->client->query('GetServerOptions');
		$serveroptions = $this->instance()->client->getResponse();
			
		$options2 = array(
			'Name' 								=> (string) $serveroptions['Name'],
			'Comment' 							=> (string) $serveroptions['Comment'],
			'Password' 							=> (string) $serveroptions['Password'],
			'PasswordForSpectator' 				=> (string) $serveroptions['PasswordForSpectator'],
			'RefereePassword'					=> (string) $serveroptions['RefereePassword'],
			'NextMaxPlayers' 					=> (int) $serveroptions['NextMaxPlayers']+0,
			'NextMaxSpectators' 				=> (int) $serveroptions['NextMaxSpectators']+0,
			'IsP2PUpload' 						=> (bool) $serveroptions['IsP2PUpload'],
			'IsP2PDownload'						=> (bool) $serveroptions['IsP2PDownload'],
			'NextLadderMode' 					=> (int) $serveroptions['NextLadderMode']+0,
			'NextVehicleNetQuality' 			=> (int) $serveroptions['NextVehicleNetQuality']+0,
			'NextCallVoteTimeOut' 				=> (int) $callVoteTimeOut,
			'CallVoteRatio' 					=> doubleval($serveroptions['CallVoteRatio']),
			'AllowMapDownload' 					=> (bool) $serveroptions['AllowMapDownload'],
			'AutoSaveReplays'					=> (bool) $serveroptions['AutoSaveReplays'],
			'RefereeMode'						=> (int) $serveroptions['RefereeMode']+0,
			'AutoSaveValidationReplays'			=> (bool) $serveroptions['AutoSaveValidationReplays'],
			'HideServer'						=> (int) $serveroptions['HideServer']+0,
			'CurrentUseChangingValidationSeed'	=> (bool) $serveroptions['CurrentUseChangingValidationSeed']
		);
			
		$this->instance()->client->query('SetServerOptions', $options2);
	}
}
?>