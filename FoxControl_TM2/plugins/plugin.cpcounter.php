<?php
//* plugin.records.php - Records
//* Version:   0.6
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_cpcounter extends FoxControlPlugin {
	public $checkPoints = array();
	public $challengeInfo;
	
	public function onStartUp() {
		$this->registerMLIds(1);
		
		$this->instance()->client->query('GetCurrentChallengeInfo');
		$this->challengeInfo = $this->instance()->client->getResponse();
	}
	
	public function onBeginChallenge($args) {		
		$this->instance()->client->query('GetCurrentChallengeInfo');
		$this->challengeInfo = $this->instance()->client->getResponse();
	}
	
	public function onPlayerCheckpoint($args) {		
		global $settings;
	
		if(($args[4] + 1) <= ($this->challengeInfo['NbCheckpoints'] - 1)) {
			$code = '
			<quad posn="0 -35 0" halign="center" sizen="10 5" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="0 -35 1" halign="center" sizen="10 2" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<quad posn="0 -35 1" halign="center" sizen="10 2" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
			<label posn="0 -35.3 2" halign="center" textsize="1" text="$oCheckpoints" />
			<label posn="0 -37.6 1" halign="center" textsize="1.5" text="$o'.($args[4] + 1).' / '.($this->challengeInfo['NbCheckpoints'] - 1).'" />';
			
			$this->displayManialinkToLogin($args[1], $code, $this->mlids[0]);
		}
	}
	
	public function onPlayerFinish($args) {
		global $settings;
	
		$code = '
		<quad posn="0 -35 0" halign="center" sizen="10 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />
		<quad posn="0 -35 1" halign="center" sizen="10 2" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
		<quad posn="0 -35 1" halign="center" sizen="10 2" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'" />
		<label posn="0 -35.3 2" halign="center" textsize="1" text="$oCheckpoints" />
		<label posn="0 -37.6 1" halign="center" textsize="1.5" text="$o0 / '.($this->challengeInfo['NbCheckpoints'] - 1).'" />';
			
		$this->displayManialinkToLogin($args[1], $code, $this->mlids[0]);
	}
	
	public function onEndChallenge($args) {
		$this->closeMl($this->mlids[0]);
	}
}
?>