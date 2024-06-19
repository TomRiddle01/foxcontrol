<?php
//* class.foxcontrolplugin.php - FoxControlPlugin Class
//* Version:   0.4
//* Coded by:  cyrilw
//* Copyright: FoxRace, http://www.fox-control.de

class FoxControlPlugin {
	public function instance() {
		global $control;
		return $control;
	}
	public $classname = '';
	public $enabled = true;
	public $mlids = array();
	public $db = '';
	public $window = null;
	public $name = '';
	public $author = '';
	public $version = '';
	public function initFCPluginClass($classn) {
		global $fc_db, $window;
		$this->db = $fc_db;
		$this->classname = $classn;
		$this->window = $window;
	}
	public function registerMLIds($ids) {
		$this->mlids = $this->instance()->registerMLIds($ids, $this->classname);
		if($this->mlids === false) {
			console('['.$this->classname.'] Could not register MLIds for '.$this->classname.'! Plugin is now disabled');
			$this->enabled = false;
		}
	}
	public function registerCommand($command, $description, $admin) {
		if($this->instance()->registerCommand($command, $description, $admin, $this->classname) === false) {
			console('['.$this->classname.'] Could not register command \''.$command.'\'!');
			$this->enabled = false;
		}
	}
	public function displayManialink($ml_code, $ml_id, $ml_version = 0, $ml_duration = 0, $ml_closewhenclick = false){
		$this->instance()->client->query('SendDisplayManialinkPage', '<?xml version="1.0" encoding="UTF-8" ?>
		<manialink id="'.$ml_id.'" version="'.$ml_version.'">
		<timeout>0</timeout>
		'.$ml_code.'
		</manialink>', $ml_duration, $ml_closewhenclick);
	}
	public function displayManialinkToLogin($ml_login, $ml_code, $ml_id, $ml_version = 0, $ml_duration = 0, $ml_closewhenclick = false){
		$this->instance()->client->query('SendDisplayManialinkPageToLogin', $ml_login, '<?xml version="1.0" encoding="UTF-8" ?>
		<manialink id="'.$ml_id.'" version="'.$ml_version.'">
		<timeout>0</timeout>
		'.$ml_code.'
		</manialink>', $ml_duration, $ml_closewhenclick);
	}
	public function closeMl($id, $login = false) {
		if($login === false){
			$this->instance()->client->query('SendDisplayManialinkPage', '<?xml version="1.0" encoding="UTF-8" ?>
			<manialink id="'.$id.'">
			</manialink>', 1, false);

		} else {
			$this->instance()->client->query('SendDisplayManialinkPageToLogin', $login, '<?xml version="1.0" encoding="UTF-8" ?>
			<manialink id="'.$id.'">
			</manialink>', 1, false);
		}
	}
	public function chat($message, $textcolor = 'fff', $color = '06f') {
		if($color === false) $this->instance()->client->query('ChatSendServerMessage', '$'.str_replace('$', '', $textcolor).$message);
		else $this->instance()->client->query('ChatSendServerMessage', '$'.str_replace('$', '', $color).'»$'.str_replace('$', '', $textcolor).' '.$message);
	}
	public function chatToLogin($login, $message, $textcolor = 'fff', $color = '06f') {
		if($color === false) $this->instance()->client->query('ChatSendServerMessageToLogin', '$'.str_replace('$', '', $textcolor).$message, $login);
		else $this->instance()->client->query('ChatSendServerMessageToLogin', '$'.str_replace('$', '', $color).'»$'.str_replace('$', '', $textcolor).' '.$message, $login);
	}
	public function getRights($login) {
		global $settings;
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".$login."'";
		$mysql = mysqli_query($this->db, $sql);
		$rights = 0;
		$Admin_Rank = 'Player';
		if($admin_rights = $mysql->fetch_object()){
			if($admin_rights->rights==1){
				$rights = 1;
				$Admin_Rank = $settings['Name_Operator'];
			}
			elseif($admin_rights->rights==2){
				$rights = 2;
				$Admin_Rank = $settings['Name_Admin'];
			}
			elseif($admin_rights->rights==3){
				$rights = 3;
				$Admin_Rank = $settings['Name_SuperAdmin'];
			}
		}
		return array(0 => $rights, 1 => $Admin_Rank);
		//rights:
		//0 = player
		//1 = operator
		//2 = admin
		//3 = super admin
	}
	public function getPluginInstance($classname) {
		global $plugins_cb;
		for($i = 0; $i < count($plugins_cb); $i++) {
			if($plugins_cb[$i][1] == $classname) return $plugins_cb[$i][0];
		}
		return false;
	}
	public function loadConfig() {
		$classname = str_replace('_', '.', $this->classname);
		$filename = $classname.'.config.xml';
		
		$xml = @simplexml_load_file('./plugins/config/'.$filename);
		
		return $xml;
	}
	public function getPosn($subtree = '') {
		$xml = $this->loadConfig();
		
		$this->instance()->client->query('GetGameMode');
		$gamemode = $this->instance()->client->getResponse();
		
		if(!empty($subtree)) {
			$posn = explode(" ", $xml->posns->local_recs->timeattack);
		}
		else{
			$posn = explode(" ", $xml->posns->$gamemode);
		}
		
		return $posn;
	}
}
?>