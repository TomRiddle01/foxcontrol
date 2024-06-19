<?php
//* plugin.clock.php - Just a simple clock ;)
//* Version:   0.4
//* Coded by:  libero6, cyrilw
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_clock extends FoxControlPlugin {
	public $last = 0;
	public $config;
	
	public function onStartUp() {
		$this->name = 'Clock';
		$this->author = 'Cyril & Libero';
		$this->version = '0.4';
		$this->registerMLIds(1);
		
		$this->config = $this->loadConfig();
	}
	public function onEverySecond() {
		global $settings;
	
		if(($this->last + 10) > time()) return;
		$this->last = time();
		
		$posn = explode(' ', $this->config->posn);
		
		$code = '
		<quad posn="'.$posn[0].' '.$posn[1].' 0" sizen="26 4.3" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'"/>
		<label posn="'.($posn[0]+7.3).' '.($posn[1]-0.5).' 1" textsize="1" halign="center" scale="1.3" text="$o'.date('H:i').'" />';
		
		if($this->config->settings->show_date == 'true') {
			$code .= '<label posn="'.($posn[0]+7.3).' '.($posn[1]-2.5).' 1" textsize="1" halign="center" text="'.date('d.m.Y').'" scale="0.9"/>';
		}
		
		$this->displayManialink($code, $this->mlids[0]);
	}
}
?>