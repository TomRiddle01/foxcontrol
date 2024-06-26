<?php
// FoxControl
// Copyright 2010 - 2012 by FoxRace, http://www.fox-control.de

//* control.php - Main file
//* Version:   1.2
//* Coded by:  matrix142, cyrilw, libero
//* Copyright: FoxRace, http://www.fox-control.de

require_once('include/GbxRemote.inc.php');

//DONT CHANGE THIS!
define('nz', "\r\n");
define('FOXC_VERSION', '1.2');
define('FOXC_VERSIONP', 'TrackMania 2 Stable');
define('FOXC_BUILD', '2013-04-08');

error_reporting(E_ALL);

function console($console) {
	if(trim($console) == '') return;
	
	$ct = explode("\n", $console);
	for($i = 0; isset($ct[$i]); $i++)
	{
		echo'['.date('d.m.y H:i:s').'] '.$ct[$i].nz;
	}
	
	//Write daily logs files
	/*if(file_exists('logs/'.date('d.m.Y').'.log')){
		$log = file_get_contents('logs/'.date('d.m.Y').'.log');
		$log = $log.nz.'['.date('d.m.y H:i:s').'] '.$console;
		
		file_put_contents('logs/'.date('d.m.Y').'.log', $log);
	}
	else{
		$newdate = date('d.m.Y');
		
		file_put_contents('logs/'.$newdate.'.log', '['.date('d.m.y H:i:s').'] '.$console);
		
		chmod ('./logs/'.$newdate.'.log', 0777);
	}*/
}

class control {
	public $playerList = array();
	public $socket;
	public $socketAddress;
	public $socketPort;
	private $socketAuthenticated = false;

	public function run() {
		global $control, $settings, $_widgetStyles;
		
		$control = $this;
		
		console('Starting FoxControl');
		
		$this->client = New IXR_Client_Gbx;
		
		//Initialize the config file
		console('Initialize the Config file..');
		
		$settings = array();
		
		if(file_exists('config.xml')) {
			$xml = @simplexml_load_file('config.xml');
		} else {
			console('ERROR: Can\'t read the Config file (config.xml)!');
			exit;
		}
		
		console('Config file initialized!'.nz);
		
		$settings['Port'] = $xml->port;
		$settings['ServerIP'] = $xml->serverIP;
		$settings['ServerPW'] = $xml->SuperAdminPW;
		$settings['ServerLogin'] = $xml->serverlogin;
		$settings['ServerPassword'] = $xml->serverpassword;
		$settings['AdminTMLogin'] = $xml->YourTmLogin;
		$settings['ServerLocation'] = $xml->ServerLocation;
		$settings['Nation'] = $xml->nation;
		$settings['DB_Path'] = $xml->db_path;
		$settings['DB_User'] = $xml->db_user;
		$settings['DB_PW'] = $xml->db_passwd;
		$settings['DB_Name'] = $xml->db_name;
		$settings['socketEnabled'] = $xml->enableSocket;
		$settings['socketPort'] = $xml->socketPort;
		$settings['Name_SuperAdmin'] = $xml->name_superadmin;
		$settings['Name_Admin'] = $xml->name_admin;
		$settings['Name_Operator'] = $xml->name_operator;
		$settings['ServerName'] = $xml->servername;
		$settings['Text_wrong_rights'] = $xml->text_false_rights;
		$settings['StartWindow'] = $xml->startwindow;
		$settings['Text_StartWindow'] = $xml->startwindowtext;
		$settings['Message_PlayerConnect'] = $xml->player_message_connect;
		$settings['Message_PlayerLeft'] = $xml->player_message_left;
		$settings['message_connect'] = $xml->message_connect;
		$settings['message_left'] = $xml->message_left;
		$settings['Color_Default'] = $xml->default_color;
		$settings['Color_Kick'] = $xml->color_kick;
		$settings['Color_Warn'] = $xml->color_warn;
		$settings['Color_Ban'] = $xml->color_ban;
		$settings['Color_UnBan'] = $xml->color_unban;
		$settings['Color_ForceSpec'] = $xml->color_forcespec;
		$settings['Color_Ignore'] = $xml->color_ignore;
		$settings['Color_SetPW'] = $xml->color_setpw;
		$settings['Color_NewServername'] = $xml->color_newservername;
		$settings['Color_NewAdmin'] = $xml->color_newadmin;
		$settings['Color_RemoveAdmin'] = $xml->color_removeadmin;
		$settings['Color_Join'] = $xml->color_join;
		$settings['Color_Left'] = $xml->color_left;
		$settings['Color_OpConnect'] = $xml->color_op_connect;
		$settings['Color_AdminConnect'] = $xml->color_admin_connect;
		$settings['Color_SuperAdminConnect'] = $xml->color_superadmin_connect;
		$settings['Color_NewChallenge'] = $xml->color_newchallenge;
		$settings['UI_ScoreTable'] = $xml->default_scoretable_enabled;
		$settings['UI_ChallengeInfo'] = $xml->default_challenge_info_enabled;
		$settings['UI_Notice'] = $xml->notice_enabled;
		$settings['menu_name'] = $xml->menu_name;
		$settings['display_local_recs'] = $xml->display_local_recs;
		$settings['display_live_rankings'] = $xml->display_live_rankings;
		$settings['max_local_recs'] = $xml->max_local_recs;
		$settings['chat_locals_number'] = $xml->chat_locals_number;
		$settings['autosave_matchsettings'] = $xml->autosave_matchsettings;
		$settings['matchsettings_filename'] = $xml->matchsettings_filename;
		
		if(file_exists('config.style.xml')) {
			$xml_config = @simplexml_load_file('config.style.xml');
		} else {
			console('ERROR: Can\'t read the Config file (config.style.xml)!');
			exit;
		}
		
		$_widgetStyles = array();
		
		$settings['default_style1'] = $xml_config->default_style1;
		$settings['default_substyle1'] = $xml_config->default_substyle1;
		$settings['default_style2'] = $xml_config->default_style2;
		$settings['default_substyle2'] = $xml_config->default_substyle2;
		$settings['default_window_style'] = $xml_config->default_window_style;
		$settings['default_window_substyle'] = $xml_config->default_window_substyle;
		
		$_widgetStyles[] = array('name' => 'Default', 'style1' => $xml_config->default_style1, 'substyle1' => $xml_config->default_substyle1, 'style2' => $xml_config->default_style2, 'substyle2' => $xml_config->default_substyle2);
		
		for($i = 0; isset($xml_config->alt_widgetstyles->style[$i]); $i++) {
			$_widgetStyles[] = array('name' => $xml_config->alt_widgetstyles->style[$i]->name, 'style1' => $xml_config->alt_widgetstyles->style[$i]->style1, 'substyle1' => $xml_config->alt_widgetstyles->style[$i]->substyle1, 'style2' => $xml_config->alt_widgetstyles->style[$i]->style2, 'substyle2' => $xml_config->alt_widgetstyles->style[$i]->substyle2);
		}
		
		//Timezone
		date_default_timezone_set($settings['ServerLocation']);
		
		//If server connection is false
		if(!$this->connect($settings['ServerIP'], $settings['Port'],  'SuperAdmin', $settings['ServerPW'])) {
			die('ERROR: Connection canceled! Wrong Port, IP or SuperAdmin Password!' . nz); 
		//Else initialize FoxControl
		} else {
			$defaultcolor = $settings['Color_Default'];
			
			$this->client->query('SetApiVersion', '2012-06-19');
			
			//Hide all Manialinks
			$this->client->query('SendHideManialinkPage');
		
			//Display FoxControl is starting window
			$this->client->query('SendDisplayManialinkPage', '<?xml version="1.0" encoding="UTF-8" ?>
			<manialink id="1">
				<quad posn="0 43 0" sizen="30 3" style="Bgs1" halign="center" substyle="NavButton" action="0"/>
				<label text="$06fF$fffox$06fC$fffontrol$z$fff is starting.." halign="center" posn="0 42.7 1" sizen="30 3" />
			</manialink>', 0, False);
			
			//Insert start message into console
			console('FoxControl Is now running. PHP-Version: '.phpversion().' - PHP-OS: '. PHP_OS);
			console(nz.'-->Connecting to the database..');
		
			//Connect to database
			global $db, $fc_db;
		
			$db = mysqli_connect($settings['DB_Path'], $settings['DB_User'], $settings['DB_PW']);
			
			if(!mysqli_select_db($db, $settings['DB_Name'])) {
				die('[ERROR] Can\'t connect to the database!');
			}
			
			console('-->Connected!'.nz);
			
			/*
				Creating Databases if not exist
			*/
			$tbl_admins = "
				CREATE TABLE IF NOT EXISTS `admins` (
				`id` smallint(6) NOT NULL AUTO_INCREMENT,
				`playerlogin` varchar(50) NOT NULL,
				`rights` smallint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=280 ;";
			mysqli_query($db, $tbl_admins);
			
			$tbl_karma = "
				CREATE TABLE IF NOT EXISTS `karma` (
				`challengeid` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`challengename` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`playerlogin` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`vote` varchar(6) NOT NULL,
				`timestamp` int(11) NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
			mysqli_query($db, $tbl_karma);

			$tbl_players = "
				CREATE TABLE IF NOT EXISTS `players` (
				`id` smallint(6) NOT NULL AUTO_INCREMENT,
				`playerlogin` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`nickname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`lastconnect` int(30) NOT NULL,
				`timeplayed` int(30) NOT NULL,
				`donations` int(11) NOT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=120 ;";
			mysqli_query($db, $tbl_players);
			mysqli_query($db, "ALTER TABLE `players` ADD country VARCHAR(50) NOT NULL");
			mysqli_query($db, "ALTER TABLE `players` ADD connections INT(20) NOT NULL");
			
			$tbl_records = "
				CREATE TABLE IF NOT EXISTS `records` (
				`challengeid` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`playerlogin` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`nickname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`time` int(11) NOT NULL,
				`date` datetime NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
			mysqli_query($db, $tbl_records);
			
			$tbl_widgets = "
				CREATE TABLE IF NOT EXISTS `widget_settings` (
				`playerlogin` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`widgetid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`style1` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`substyle1` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`style2` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`substyle2` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`posx` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
				`posy` varchar(200) COLLATE utf8_unicode_ci NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
			mysqli_query($db, $tbl_widgets);
			
			mysqli_query($db, "ALTER TABLE `widget_settings` ADD defaultPosX VARCHAR(10) NOT NULL");
			mysqli_query($db, "ALTER TABLE `widget_settings` ADD defaultPosY VARCHAR(10) NOT NULL");
			
			/*
				Fix bug MostActive time enorm
			*/
			$sql = mysqli_query($db, "SELECT timeplayed FROM `players`");
			while($row = $sql->fetch_object()) {
				if($row->timeplayed > 10000000) {
					mysqli_query($db, "UPDATE `players` SET timeplayed = '0' WHERE playerlogin = '".$row->playerlogin."'");
				}
			}
			
			/*
				Stop creating Databases
			*/
		
			$fc_db = $db;
			global $FoxControl_Reboot, $FoxControl_Shutdown;
			$FoxControl_Reboot = false;
			$FoxControl_Shutdown = false;
		
			//Create superadminacc
			if(trim($settings['AdminTMLogin']) != '' AND trim($settings['AdminTMLogin'] != 'YourLogin')) {
				$sql = mysqli_query($db, "SELECT * FROM admins WHERE playerlogin = '".$settings['AdminTMLogin']."'");
				if(!$row = $sql->fetch_object()) {
					$sql2 = mysqli_query($db, "INSERT INTO `admins` (id, playerlogin, rights) VALUES ('', '".$settings['AdminTMLogin']."', '3')");
				}
	
				$atmlfile = file('config.xml');
				file_put_contents('config.xml', str_replace('<YourTmLogin>'.$settings['AdminTMLogin'].'</YourTmLogin>', '', $atmlfile));
			}
	
			//Enable callbacks
			console('-->Enable Callbacks');
			if (!$this->client->query('EnableCallbacks', true)) {
				console('[Error ' . $this->client->getErrorCode() . '] ' . $this->client->getErrorMessage());
				die('[Error] Cant\'t enable callbacks!');
			} else {
				console('-->Callbacks enabled'.nz);
			}
		
			//Sending data to FoxControl MasterServer
			$this->sendServerData();
		
			/*
				LOAD PLUGINS
			*/
			global $fc_custom_ui, $fc_active_plugins, $plugins_cb, $fc_mlids, $fc_commands, $fc_widgetids, $events;
		
			$fc_custom_ui = array();
			$fc_active_plugins = array();
			$plugins_cb = array();
			$fc_mlids = 3;
			$fc_commands = array();
			$fc_widgetids = 0;
			$events = array();
		
			require_once('./include/class.foxcontrolplugin.php');
			require_once('./include/class.window.php');
			require_once('./include/class.widget.php');
		
			$pluginclass = 'window';
			$plugins_cb[] = array(0 => new $pluginclass, 1 => $pluginclass, 2 => array(), 3 => array());
			$plugins_cb[0][0]->initFCPluginClass($pluginclass);
			$plugins_cb[0][0]->onInit();
			
			$pluginclass = 'widget';
			$plugins_cb[] = array(0 => new $pluginclass, 1 => $pluginclass, 2 => array(), 3 => array());
			$plugins_cb[1][0]->initFCPluginClass($pluginclass);
			$plugins_cb[1][0]->onInit();
		
			global $window, $widget;
			$window = $plugins_cb[0][0];
			$widget = $plugins_cb[1][0];
			
			/*
				WRITE EVENTS ARRAY
			*/
			$events['onStartUp'] = array();
			$events['onEverySecond'] = array();
			$events['onTick'] = array();
			$events['onPlayerConnect'] = array();
			$events['onPlayerDisconnect'] = array();
			$events['onManialinkPageAnswer'] = array();
			$events['onCommand'] = array();
			$events['onChat'] = array();
			$events['onBeginRace'] = array();
			$events['onEndRace'] = array();
			$events['onEcho'] = array();
			$events['onServerStart'] = array();
			$events['onServerStop'] = array();
			$events['onBeginChallenge'] = array();
			$events['onEndChallenge'] = array();
			$events['onBeginMap'] = array();
			$events['onEndMap'] = array();
			$events['onBeginRound'] = array();
			$events['onEndRound'] = array();
			$events['onBeginMatch'] = array();
			$events['onEndMatch'] = array();
			$events['onStatusChanged'] = array();
			$events['onPlayerCheckpoint'] = array();
			$events['onPlayerFinish'] = array();
			$events['onPlayerIncoherence'] = array();
			$events['onBillUpdated'] = array();
			$events['onTunnelDataReceived'] = array();
			$events['onChallengeListModified'] = array();
			$events['onMapListModified'] = array();
			$events['onPlayerInfoChanged'] = array();
			$events['onManualFlowControlTransition'] = array();
			$events['onVoteUpdated'] = array();
			$events['onRulesScriptCallback'] = array();
			$events['onModeScriptCallback'] = array();
			
			//Create Playerlist
			$this->client->query('GetPlayerList', 200, 0);
			$playerlist = $this->client->getResponse();
			
			foreach($playerlist as $key => $value) {
				$this->updatePlayerList($playerlist[$key]['Login']);
			}
			
			/*
				READ PLUGINS.XML
			*/
			$xml = @simplexml_load_file('plugins.xml');
			$plugin_id = 0;
		
			console('-->Loading plugins..');
		
			while(isset($xml->plugin[$plugin_id])){
				console('-->Load plugin '.trim($xml->plugin[$plugin_id]).' ['.$plugin_id.']');
				
				if(file_exists('plugins/'.trim($xml->plugin[$plugin_id]).'')) {
					require('plugins/'.trim($xml->plugin[$plugin_id]).'');
				
					$fc_active_plugins[] = trim($xml->plugin[$plugin_id]);
					$pluginclass = str_replace('.', '_', trim($xml->plugin[$plugin_id]));
					$pluginclass = str_replace('_php', '', $pluginclass);
					$plugins_cb[] = array(0 => new $pluginclass, 1 => $pluginclass, 2 => array(), 3 => array());
					$plugins_cb[$plugin_id+2][0]->initFCPluginClass($pluginclass);
				} else {
					die('[ERROR] Can\'t load plugin \''.trim($xml->plugin[$plugin_id]).'\'. File does not exist!');
				}
				$plugin_id++;
			}
			
			for($i = 0; $i < count($plugins_cb); $i++) {
				foreach($events as $key => $value) {
					if(method_exists($plugins_cb[$i][0], $key)) {
						$events[$key][] = $i;
					}
				}
			}
			
			console('-->Plugins loaded!'.nz);
			$this->client->query('SendHideManialinkPage');
		
			console('-->Enable custom_ui..');
			$this->custom_ui();
		
			console('-->Custom_ui enabled!'.nz);
			
			//Create socket
			$this->createSocket();
			
			//Call StartUp Event in all Plugins
			$this->callEvent('StartUp');
		
			$this->client->query('GetServerName');
			$servername = $this->client->getResponse();
			
			if($settings['ServerName'] == '') {
				$this->writeInConfig('servername', $servername);
			} else {			
				$this->client->query('SetServerName', (string) $settings['ServerName']);
			}
		
			console('#######################################################');
			console('FoxControl '.FOXC_VERSIONP.' '.FOXC_VERSION);
			console('Authors: matrix142, cyrilw, libero, jens');
			console('Running on '.$this->rgb_decode($settings['ServerName']));
			console('Support forum: http://forum.fox-control.de');
			console('#######################################################');
			
			//StartUp Chat message			
			$this->client->query('ChatSendServerMessage', '$z
			$06f****************************************
			$06f» F$fffox$06fC$fffontrol successfully started!
			$06f» $fff'.FOXC_VERSIONP.': '.FOXC_VERSION.'
			$06f» $fff'.($plugin_id+1).'$fff Plugins were loaded
			$06f****************************************');
			
			$this->FoxControl();
		}
	}
	
	/*
		FUNCTIONS
	*/
	
	//CUSTOM UI
	public function custom_ui(){
		global $settings;
		
		if($settings['UI_ScoreTable']=='false'){
			$custom_ui_score = '<scoretable visible="false"/>';
		}
		else{
			$custom_ui_score = '<scoretable visible="true"/>';
		}
		
		if($settings['UI_ChallengeInfo']=='false'){
			$custom_ui_challinfo = '<challenge_info visible="false"/>';
		}
		else{
			$custom_ui_challinfo = '<challenge_info visible="true"/>';
		}
		
		if($settings['UI_Notice']=='false'){
			$custom_notice = '<notice visible="false"/>';
		}
		else{
			$custom_notice = '<notice visible="true"/>';
		}
		
		
		$this->client->query('SendDisplayManialinkPage', '<?xml version="1.0" encoding="UTF-8" ?>
		<manialink id="1">
		</manialink>
		<custom_ui>
			'.$custom_ui_score.'
			'.$custom_ui_challinfo.'
			'.$custom_notice.'
		</custom_ui>', 0, false);
	}
	
	//GET ID OF SPECIFIED PLUGIN
	public function getPluginId($classname) {
		global $plugins_cb;
		
		for($i = 0; $i < count($plugins_cb); $i++) {
			if($plugins_cb[$i][1] == $classname) return $i;
		}
		return false;
	}
	
	//REGISTER MANIALINK IDS FOR PLUGIN
	public function registerMLIds($ids, $class) {
		global $fc_mlids, $plugins_cb;
		
		$pluginid = $this->getPluginId($class);
		
		if($pluginid === false) return false;
		
		$return = array();
		for($i = 0; $i < $ids; $i++) {
			$fc_mlids++;
			$return[] = $fc_mlids;
			$plugins_cb[$pluginid][2][] = $fc_mlids;
		}
		return $return;
	}
	
	//REIGSTER COMMAND FOR PLUGIN
	public function registerCommand($command, $description, $admin, $class) {
		global $fc_commands, $plugins_cb;
		
		$pluginid = $this->getPluginId($class);
		
		if($pluginid === false) return false;
		
		$fc_commands[] = array(0 => $command, 1 => $description, 2 => $admin);
		$plugins_cb[$pluginid][3][] = $command;
		return true;
	}
	
	//REGISTER WIDGET IDS FOR PLUGIN
	public function registerWidget($ids, $class) {
		global $fc_widgetids, $plugins_cb;
		
		$pluginid = $this->getPluginId($class);
		
		if($pluginid === false) return false;
		
		$return = array();
		for($i = 0; $i < $ids; $i++) {
			$fc_widgetids++;
			$return[] = $fc_widgetids;
			$plugins_cb[$pluginid][4][] = $fc_widgetids;
		}
		return $return;
	}
	
	//GET LIST OF ALL CHAT COMMANDS
	public function getCommands($commands) {
		global $fc_commands;
		
		if($commands == 'all') return $fc_commands;
		else if($commands == 'player') {
			$array = array();
			for($i = 0; $i < count($fc_commands); $i++) {
				if($fc_commands[$i][2] === false && $fc_commands[$i][1] !== false) $array[] = $fc_commands[$i];
			}
			return $array;
		} else if($commands == 'admin') {
			$array = array();
			for($i = 0; $i < count($fc_commands); $i++) {
				if($fc_commands[$i][2] === true && $fc_commands[$i][1] !== false) $array[] = $fc_commands[$i];
			}
			return $array;
		}
	}
	
	//CHECK IF PLUGIN IS ACTIVE
	public function pluginIsActive($pluginName) {
		global $fc_active_plugins;
		
		for($i = 0; $i < count($fc_active_plugins); $i++) {
			if($fc_active_plugins[$i] == $pluginName) return true;
		}
		return false;
	}
	
	//UNBAN PLAYER
	public function unban($unban_player, $unbanmessage, $CommandAuthor, $ubplayer){
		global $settings, $db;
		
		$this->client->query('UnBan', $unban_player);
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".$CommandAuthor['Login']."'";
		$mysql = mysqli_query($db, $sql);
		
		if($admin_rights = $mysql->fetch_object()){
			if($admin_rights->rights==1){
				$Admin_Rank = $settings['Name_Operator'];
			}
			elseif($admin_rights->rights==2){
				$Admin_Rank = $settings['Name_Admin'];
			}
			elseif($admin_rights->rights==3){
				$Admin_Rank = $settings['Name_SuperAdmin'];
			}
			else $Admin_Rank = '';
		
		
		}
		else $Admin_Rank = '';
		
		if($unbanmessage === false) return;
		
		if(!isset($Unbanned_player['NickName'])) $Unbanned_player['NickName'] = $unban_player;
		
		$color_unban = $settings['Color_UnBan'];
		$this->client->query('ChatSendServerMessage', $color_unban.''.$Admin_Rank.' $fff'.$CommandAuthor['NickName'].'$z$s '.$color_unban.'unbanned $fff'.$ubplayer->nickname.'$z$s '.$color_unban.'!');
	}
	
	//GET RANK NAME OF PLAYER
	public function getPlayerRankName($login, $colors = true) {
		global $db, $settings;
	
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".$login."'";
		$mysql = mysqli_query($db, $sql);
		if(mysqli_errno($db)) {
			console(mysqli_error($db));
		}
		
		if($row = $mysql->fetch_object()) {
			if($row->rights == 1){
				if($colors == true) {
					$player_rank = $settings['Color_OpConnect'].$settings['Name_Operator'];
				} else {
					$player_rank = $settings['Name_Operator'];
				}
			}
			elseif($row->rights == 2){
				if($colors == true) {
					$player_rank = $settings['Color_AdminConnect'].$settings['Name_Admin'];
				} else {
					$player_rank = $settings['Name_Admin'];
				}
			}
			elseif($row->rights == 3){
				if($colors == true) {
					$player_rank = $settings['Color_SuperAdminConnect'].$settings['Name_SuperAdmin'];
				} else {
					$player_rank = $settings['Name_SuperAdmin'];
				}
			}
		}
		else{
			$player_rank = 'Player';
		}
		return $player_rank;
	}
	
	//PLAYER CONNECT
	public function playerconnect($connected_player){
		global $db, $settings;
		//Display Custim UI
		$this->custom_ui();
		
		//Get PlayerList
		$this->getPlayerList();
		
		//Set FoxVersion
		$foxcontrol->version = FOXC_VERSION;
		$foxcontrol->versionpraefix = FOXC_VERSIONP;
		
		$color_join = $settings['Color_Join'];
		$newline = "\n";
		
		//Get Servername and PlayerInfos
		$this->client->query('GetServerName');
		$servername = $this->client->getResponse();
		
		$this->updatePlayerList($connected_player[0]);
		
		$login = $connected_player[0];
		
		//Get Player Rank
		$player_rank = $this->getPlayerRankName($login);
		
		//Check if Player is FoxTeam Member
		if($login=='jensoo7' OR $login=='matrix142'){
			$player_rank .= ' '.$settings['Color_Default'].'and '.$color_join.'F$fffox'.$color_join.' T$fffeam '.$color_join.'M$fffember$o';
		}
		
		//If Join message is activated
		if($settings['Message_PlayerConnect'] == true) {
			$join_message = $settings['message_connect'];
				
			$replace = array('{rank}', '{nickname}', '{path}', '{ladder}');
			$replace2 = array($player_rank, $this->playerList[$login]['NickName'], $this->playerList[$login]['Path'], $this->playerList[$login]['LadderStats']);
				
			$join_message = str_replace($replace, $replace2, $join_message);
			
			//Send Join message
			$this->client->query('ChatSendServerMessage', $join_message);
		}
		
		//Send Welcome message to player
		$this->client->query('ChatSendServerMessageToLogin', '$06f» $fffWelcome '.$this->playerList[$login]['NickName'].'$z$s$fff on '.$servername.$newline.'$z$s$06f» $fffThis Server is running with $06fF$fffox$06fC$fffontrol$fff ('.$foxcontrol->versionpraefix.': '.$foxcontrol->version.' )'.$newline.'$06f» $fffHave fun!', $login);  
		console('New '.str_replace('$o', '', $player_rank).' ' . $login  . ' connected! IP: '.$this->playerList[$login]['IPAddress'].'');
		
		//Get Country
		$country = explode('|', $this->playerList[$login]['Path']);
		$country = $country[1];
		
		$sql = mysqli_query($db, "SELECT * FROM `players` WHERE playerlogin = '".$login."'");
		//Insert Player into the database or update it's data
		if(!$row = $sql->fetch_object()){
			$sql = mysqli_query($db, "INSERT INTO `players` (id, playerlogin, nickname, lastconnect, country, connections) VALUES ('', '".mysqli_real_escape_string($db, $login)."', '".mysqli_real_escape_string($db, $this->playerList[$login]['NickName'])."', '".time()."', '".$country."', '1')");
		}
		else{
			//Get Connections			
			$connections = $row->connections;
			$connections += 1;
		
			//Update Data
			$sql = mysqli_query($db, "UPDATE `players` SET nickname = '".mysqli_real_escape_string($db, $this->playerList[$login]['NickName'])."', lastconnect = '".time()."', country = '".mysqli_real_escape_string($db, $country)."', connections = '".$connections."' WHERE playerlogin = '".mysqli_real_escape_string($db, $login)."'");
		}
		
		//Create welcome window
		if($settings['StartWindow'] == 'true') {
			global $window;
				
			$window->init();
			$window->title('$fffWelcome on $z$o$fff'.$servername.'$z$fff!');
			$window->size('60', '');
			$window->close(false);
				
			$content = $settings['Text_StartWindow'];
			$content = str_replace('{player}', $this->playerList[$login]['NickName'].'$z$fff', $content);
			$content = str_replace('{server}', $servername.'$z$fff', $content);
			$content = str_replace('FoxControl', '$o$06fF$fffox$06fC$fffontrol$o', $content);
			$content = explode('{newline}', $content);
				
			for($i = 0; isset($content[$i]); $i++) {
				$window->content($content[$i]);
			}
				
			$window->addButton('Ok', '20', true);
			$window->show($login);
		}
	}
	
	//PLAYER DISCONNECT
	public function playerdisconect($playerdata){
		global $db, $settings;
		
		//Get PlayerList
		$this->getPlayerList();
		
		$color_left = $settings['Color_Left'];
		$login = $playerdata[0];
		
		//Update Player data (timeplayed)
		$sql = mysqli_query($db, "SELECT * FROM players WHERE playerlogin = '".mysqli_real_escape_string($db, $login)."'");
		
		if($row = $sql->fetch_object()) {
			$nickname = $row->nickname;
			
			if(isset($this->playerList[$login]['timePlayed']) && $this->playerList[$login]['timePlayed'] > 0) {
				$timePlayedCurrent = time()-$this->playerList[$login]['timePlayed'];
				$timePlayed = $row->timeplayed + $timePlayedCurrent;
			} else {
				$timePlayed = $row->timeplayed;
			}
			
			$sql2 = mysqli_query($db, "UPDATE players SET timeplayed='".mysqli_real_escape_string($db, $timePlayed)."' WHERE playerlogin='".mysqli_real_escape_string($db, $login)."'");
		}
		
		$player_rank = $this->getPlayerRankName($login);

		//If disconnected player is FoxTeam Member
		if($login == 'jensoo7' OR $login == 'matrix142'){
			$player_rank .= ' '.$settings['Color_Default'].'and '.$color_left.'F$fffox'.$color_left.' T$fffeam '.$color_left.'M$fffember$o';
		}
		
		//If message left is true
		if($settings['Message_PlayerLeft'] == true) {
			if(!isset($nickname)) {
				if(isset($this->playerList[$login]['NickName'])) {
					$nickname = $this->playerList[$login]['NickName'];
				} else {
					$nickname = 'Undefined';
				}
			}
		
			$left_message = $settings['message_left'];
			$replace = array('{rank}', '{nickname}');
			$replace2 = array($player_rank, $nickname);
			
			$left_message = str_replace($replace, $replace2, $left_message);
			
			//Send left message
			$this->client->query('ChatSendServerMessage', $left_message);
		}
		
		$this->updatePlayerList($login, true);
		
		//Insert left message into log file
		console('Player '.$login.' left the game');
	}
	
	/*
		UPDATE PLAYER LIST
	*/
	public function updatePlayerList($login, $unset = false) {
		global $settings;
	
		if($login != $settings['ServerLogin']) {
			if($unset == false) {
				$this->client->query('GetDetailedPlayerInfo', $login);
				$playerInfo = $this->client->getResponse();
		
				$this->playerList[$login] = array();
				$this->playerList[$login]['NickName'] = $playerInfo['NickName'];
				$this->playerList[$login]['Path'] = $playerInfo['Path'];
				$this->playerList[$login]['LadderStats'] = $playerInfo['LadderStats']['PlayerRankings'][0]['Ranking'];
				$this->playerList[$login]['IPAddress'] = $playerInfo['IPAddress'];
				$this->playerList[$login]['timePlayed'] = time();	
			} else {
				if(isset($this->playerList[$login])) {
					unset($this->playerList[$login]);
				}
			}
		}
	}
	
	/*
		GET PLAYER LIST
	*/
	public function getPlayerList() {
		return $this->playerList;
	}
	
	/*
		GET GAMEMODE
	*/
	public function getGameMode() {
		$this->client->query('GetGameMode');
		$gameMode = $this->client->getResponse();
		
		if($gameMode == 0) $gameMode = 'script';
		if($gameMode == 1) $gameMode = 'rounds';
		if($gameMode == 2) $gameMode = 'timeattack';
		if($gameMode == 3) $gameMode = 'team';
		if($gameMode == 4) $gameMode = 'laps';
		if($gameMode == 5) $gameMode = 'cup';
		if($gameMode == 6) $gameMode = 'stunts';
	
		if($gameMode == 'script') {
			$this->client->query('GetModeScriptInfo');
			$scriptInfo = $this->client->getResponse();
			$gameMode = $scriptInfo['Name'];
		
			if($gameMode == '<in-development>') {
				include_once('include/gbxdatafetcher.inc.php');
	
				$this->client->query('GetMapsDirectory');
				$mapDir = $this->client->getResponse();
	
				//Getting current MapType
				$this->client->query('GetCurrentMapInfo');
				$mapInfo = $this->client->getResponse();
				$fileName = $mapInfo['FileName'];
		
				$path = $mapDir.$fileName;
			
				$gbx = new GBXChallengeFetcher($path, true);
		
				$gameMode = $gbx->parsedxml['DESC']['MAPTYPE'];
			}
		
			$gameMode = str_replace('TrackMania\\', '', $gameMode);
			$gameMode = str_replace('Trackmania\\', '', $gameMode);
			$gameMode = str_replace('Multi', '', $gameMode);
			$gameMode = str_replace('.Script.txt', '', $gameMode);
		
			if($gameMode == 'BattleWaves') {
				$gameMode = 'Battle';
			}
		}
		
		return $gameMode;
	}
	
	//FORMAT TIME
	public function formattime($time_to_format){

		//FORMAT TIME
		$formatedtime_minutes = floor($time_to_format/(1000*60));
		$formatedtime_seconds = floor(($time_to_format - $formatedtime_minutes*60*1000)/1000);
		$formatedtime_hseconds = substr($time_to_format, strlen($time_to_format)-3, 2);
		$formatedtime = sprintf('%02d:%02d.%02d', $formatedtime_minutes, $formatedtime_seconds, $formatedtime_hseconds);
	
		return $formatedtime;

	}
	
	//FORMAT TIME HOUR
	public function formattime_hour($time_to_format){

		//FORMAT TIME
		$formatedtime_houres = floor($time_to_format/3600);
	
		return $formatedtime_houres.'h';

	}
	
	//FORMAT TIME MINUTE
	public function formattime_minute($time_to_format) {
		//FORMAT TIME
		$formatedtime_minutes = floor($time_to_format/60);
	
		return $formatedtime_minutes.'min';	
	}
	
	//CHECK IF PLAYER IS ADMIN
	public function is_admin($player_to_check){
		global $db;
		
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".mysqli_real_escape_string($db, $player_to_check)."'";
		if($mysql = mysqli_query($db, $sql)){
			if($admin_rights = $mysql->fetch_object()){
				return true;	
			}
			else return false;
		}
		else return false;
	}
	
	//KICK PLAYER
	public function player_kick($player_to_kick, $kickmessage, $CommandAuthor){
		$control->client = $this->client;
		global $db, $settings;
		
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".mysqli_real_escape_string($db, $CommandAuthor['Login'])."'";
		$mysql = mysqli_query($db, $sql);
		
		if($admin_rights = $mysql->fetch_object()){
			if($admin_rights->rights==1){
				$Admin_Rank = $settings['Name_Operator'];;
			}
			elseif($admin_rights->rights==2){
				$Admin_Rank = $settings['Name_Admin'];;
			}
			elseif($admin_rights->rights==3){
				$Admin_Rank = $settings['Name_SuperAdmin'];;
			}
			else $Admin_Rank = '';
		}
			
		$control->client->query('GetDetailedPlayerInfo', $player_to_kick);
		$kickedplayer = $control->client->getResponse();
			
	    if($kickmessage==true){
			$color_kick = $settings['Color_Kick'];
			$control->client->query('ChatSendServerMessage', $color_kick.'-> '.$Admin_Rank.' $fff'.$CommandAuthor['NickName'].'$z$s '.$color_kick.'kicked $fff'.$kickedplayer['NickName'].'$z$s '.$color_kick.'!');
		}
		$control->client->query('Kick', $kickedplayer['Login']);
	}
		
	//IGNORE PLAYER
	public function player_ignore($player_to_ignore, $ignoremessage, $CommandAuthor){ //function to ignore a player. The first parameter is the login of the player. The others are optional. If the secound parameter = true, then write the script a message in the chat. The third parameter is the Nickname of the player who ignored the player (only when message = true)
		global $db, $settings;
		
		$sql = "SELECT * FROM `admins` WHERE playerlogin = '".mysqli_real_escape_string($db, $CommandAuthor['Login'])."'";
		$mysql = mysqli_query($db, $sql);
		
		if($admin_rights = $mysql->fetch_object()){
			if($admin_rights->rights==1){
				$Admin_Rank = $settings['Name_Operator'];
			}
			elseif($admin_rights->rights==2){
				$Admin_Rank = $settings['Name_Admin'];
			}
			elseif($admin_rights->rights==3){
				$Admin_Rank = $settings['Name_SuperAdmin'];
			}
			else $Admin_Rank = '';
		}
		
		$this->client->query('GetDetailedPlayerInfo', $player_to_ignore);
		$ignoredplayer = $this->client->getResponse();
		
		$this->client->query('GetIgnoreList', 1000, 0);
		$ignore_list = $this->client->getResponse();
		
		$curr_ignore_id = 0;
		$player_in_ignore_list = false;
		while(isset($ignore_list[$curr_ignore_id])){
			if($ignore_list[$curr_ignore_id]['Login'] == trim($ignoredplayer['Login'])){
				$player_in_ignore_list = true;
				break;
			}
			$curr_ignore_id++;
		}
		
		if($player_in_ignore_list==true){
			if($ignoremessage==true){
				$color_ignore = $settings['Color_Ignore'];
				$this->client->query('ChatSendServerMessage', $color_ignore.'-> '.$Admin_Rank.' $fff'.$CommandAuthor['NickName'].'$z$s '.$color_ignore.'unignored $fff'.$ignoredplayer['NickName'].'$z$s '.$color_ignore.'!');
			}
			$this->client->query('UnIgnore', $ignoredplayer['Login']);
		}
		else{
			if($ignoremessage==true){
				$color_ignore = $settings['Color_Ignore'];
				$this->client->query('ChatSendServerMessage', $color_ignore.'-> '.$Admin_Rank.' $fff'.$CommandAuthor['NickName'].'$z$s '.$color_ignore.'ignored $fff'.$ignoredplayer['NickName'].'$z$s '.$color_ignore.'!');
			}
			$this->client->query('Ignore', $ignoredplayer['Login']);
		}
	}
	
	//CHAT WITH NICK FROM OTHER PLAYER
	public function chat_with_nick($chat_to_write, $chat_nick){
		$this->client->query('GetDetailedPlayerInfo', $chat_nick);
		$chat_nick = $this->client->getResponse();
		
		$chat_nick = $chat_nick['NickName'];
		
		$this->client->query('ChatSendServerMessage', $chat_nick.'$z$s$06f» $fff'.$chat_to_write);
	}
	
	//RGB DECODE STRING
	public function rgb_decode($string){
		$string = str_replace('$o', '', $string);
		$string = str_replace('$s', '', $string);
		$string = str_replace('$n', '', $string);
		$string = str_replace('$i', '', $string);
		$string = str_replace('$w', '', $string);
		$string = str_replace('$t', '', $string);
		$string = str_replace('$z', '', $string);
		$string = str_replace('$g', '', $string);
		$string = str_replace('$l', '', $string);
		$string = str_replace('$h', '', $string);
		$string = preg_replace('/\$(0|1|2|3|4|5|6|7|8|9|a|b|c|d|e|f)(0|1|2|3|4|5|6|7|8|9|a|b|c|d|e|f)(0|1|2|3|4|5|6|7|8|9|a|b|c|d|e|f)/i', '', $string);
		
		return $string;
	}
	
	public function chat_message($chat_message){
		$this->client->query('ChatSendServerMessage', $chat_message);
	}
	
	public function chat_message_player($chat_message, $player){
		$this->client->query('ChatSendServerMessageToLogin', $chat_message, $player);
	}
	
	public function console($console_message){
		console($console_message);
	}
	
	/************************
	*********EVENTS**********
	************************/
	public function callEvent($EventName, $args = false) {
		global $plugins_cb, $events;
		
		$eventIsCommand = false;
		$commandFound = false;
		$callfunction = 'on'.$EventName;
		
		foreach($events[$callfunction] as $key => $value) {
			if($plugins_cb[$value][0]->enabled == true) {
				if($EventName == 'ManialinkPageAnswer') {
					for($i = 0; $i < count($plugins_cb[$value][2]); $i++) {
						if($plugins_cb[$value][2][$i] == $args[2]) $plugins_cb[$value][0]->$callfunction($args);
					}
				} else if($EventName == 'Command') {
					$eventIsCommand = true;
					for($i = 0; $i < count($plugins_cb[$value][3]); $i++) {
						if(trim($plugins_cb[$value][3][$i]) == trim($args[2])) {
							$plugins_cb[$value][0]->$callfunction($args);
							$commandFound = true;
						}
					}
				} else {
					if($args === false) $plugins_cb[$value][0]->$callfunction();
					else $plugins_cb[$value][0]->$callfunction($args);
				}
			}
		}
		
		if($commandFound === false && $eventIsCommand === true) {
			$this->client->query('ChatSendServerMessageToLogin', '$f00» Command not found! Type $fff/help$f00 to get a list of all available commands.', $args[1]);
		}
	}
	
	//REBOOT FOXCONTROL
	public function FoxControl_reboot(){
		global $FoxControl_Reboot;
		
		$this->client->query('SendHideManialinkPage');
		$FoxControl_Reboot = true;
	}
	
	//AUTOUPDATE FOXCONTROL
	public function FoxControl_shutdown() {
		global $FoxControl_Shutdown;
		
		$this->client->query('SendHideManialinkPage');
		$FoxControl_Shutdown = true;
	}
	
	//SKIP CHALLENGE
	public function challenge_skip(){
		$this->client->query('NextMap');
	}
	
	/*
		MAIN LOOP
	*/
	public function FoxControl(){
		global $db, $FoxControl_Reboot, $FoxControl_Shutdown, $settings;
		
		$defaultcolor = '07b';
		$newline = "\n";
		$servername = $this->client->getResponse();
		$current_time = time();
	
		//MAIN LOOP
		while(true) {			
			//STOP FOXCONTROL
			if($FoxControl_Reboot == true || $FoxControl_Shutdown == true) {
				break;
			}
			
			//EVENT EVERYSECOND
			if($current_time !== time()) {
				$this->callEvent('EverySecond');
			}
			
			//EVENT TICK
			$this->callEvent('Tick');
			
			//SET CUSTOM UI
			$current_time = time();
			if(!isset($curr_time_30sec)) {
				$curr_time_30sec = time();
				$this->custom_ui();
			}
			
			if($curr_time_30sec <= time()-30) {
				$this->custom_ui();
				$curr_time_30sec = time();
			}
			
			//ESTABLISH DATABASE CONNECTION
			if(!isset($database10min)) {
				$database10min = time();
				mysqli_query($db, "SELECT playerlogin FROM `admins` LIMIT 1");
			}
			
			if($database10min <= time()-600) {
				$database10min = time();
				mysqli_query($db, "SELECT playerlogin FROM `admins` LIMIT 1");
			}
			
			
			//GET SERVER CALLBACKS
			$this->client->readCB(1);
			$calls = $this->client->getCBResponses();
			
			if(!empty($calls)) {
				foreach($calls as $call) {
					$cbname = $call[0];
					$cbdata = $call[1];
					
					//$this->client->query('ChatSendServerMessage', $cbname);
					
					//Switch Callbacks
					switch($cbname) {
						//Player Connect
						case 'ManiaPlanet.PlayerConnect':
							global $widget;
							
							$this->client->query('GetDetailedPlayerInfo', $cbdata[0]);
							$connectedplayer = $this->client->getResponse();
						
							$this->playerconnect($cbdata);
							$this->callEvent('PlayerConnect', $connectedplayer);
						break;
			
						//Player Disconnect
						case 'ManiaPlanet.PlayerDisconnect':
							$this->playerdisconect($cbdata);
							$this->callEvent('PlayerDisconnect', $cbdata);
						break;
			
						//Manialink Page Answer
						case 'ManiaPlanet.PlayerManialinkPageAnswer':
							if($cbdata[2] >= 10000 && $cbdata[2] <= 10010) {
								global $window;
								$window->mlAnswer($cbdata);
							}
							
							if(preg_match('/widget:/', $cbdata[2])) {
								global $widget;
								$widget->onManialinkPageAnswer($cbdata);
							}
							
							$this->callEvent('ManialinkPageAnswer', $cbdata);
						break;
			
						//Player Chat
						case 'ManiaPlanet.PlayerChat':
							if($cbdata[0] !== 0) {
								if(substr(trim($cbdata[2]), 0, 1) == '/') {
									$args = explode(' ', trim($cbdata[2]));
								
									if(!isset($args[1])) $args[1] = '';
								
									$this->callEvent('Command', array(0 => $cbdata[0], 1 => $cbdata[1], 2 => str_replace('/', '', $args[0]), 3 => explode(' ', trim(str_replace($args[0], '', trim($cbdata[2])))), 4 => trim(str_replace(array($args[0], $args[1]), array('', ''), trim($cbdata[2])))));
								} else {
									$this->callEvent('Chat', $cbdata);
								}
							}
						break;
			
						//Begin Race
						case 'TrackMania.BeginRace':
							$this->callEvent('BeginRace', $cbdata);
						break;
			
						//End Race
						case 'TrackMania.EndRace':
							$this->callEvent('EndRace', $cbdata);
						break;
						
						//Echo
						case 'ManiaPlanet.Echo':
							$this->callEvent('Echo', $cbdata);
						break;
						
						//Server Start
						case 'ManiaPlanet.ServerStart':
							$this->callEvent('ServerStart');
						break;
						
						//Server Stop
						case 'ManiaPlanet.ServerStop':
							$this->callEvent('ServerStop');
						break;
						
						//Begin Challenge
						case 'ManiaPlanet.BeginMap':
							//Sending data to FoxControl MasterServer
							$this->sendServerData();
							
							$this->callEvent('BeginMap', $cbdata);
							$this->callEvent('BeginChallenge', $cbdata);
						break;
			
						//End Challenge
						case 'ManiaPlanet.EndMap':						
							/*global $chall_restarted_admin;
							
							if($chall_restarted_admin !== true) {
								$this->callEvent('EndMap', $cbdata);
								$this->callEvent('EndChallenge', $cbdata);
							} else {
								$chall_restarted_admin = false;
							}
							
							$this->saveMatchsettings();*/
						break;
						
						//Begin Match
						case 'ManiaPlanet.BeginMatch':
							global $chall_restarted_admin;
						
							$this->callEvent('BeginMatch');
							
							if($chall_restarted_admin == true) {							
								$this->callEvent('BeginMap', $cbdata);
								$this->callEvent('BeginChallenge', $cbdata);
								$chall_restarted_admin = false;
							}
						break;
						
						//End Match
						case 'ManiaPlanet.EndMatch':
							global $chall_restarted_admin, $timeEndMatchTriggered;
							
							$trigger = false;
							
							if(!isset($timeEndMatchTriggered)) {
								$timeEndMatchTriggered = time();
								$trigger = true;
							}
							
							if($timeEndMatchTriggered < (time()-5)) {
								$timeEndMatchTriggered = time();
								$trigger = true;
							}
							
							if($trigger == true) {							
								if($chall_restarted_admin !== true) {								
									$this->client->query('GetCurrentMapInfo');
									$mapInfo = $this->client->getResponse();
								
									$this->client->query('GetCurrentRanking', 200, 0);
									$ranking = $this->client->getResponse();
								
									$this->callEvent('EndMap', array(0 => $ranking, 1 => $mapInfo));
									$this->callEvent('EndChallenge', array(0 => $ranking, 1 => $mapInfo));
									$this->callEvent('EndMatch', $cbdata);
								}
							
								$this->saveMatchsettings();
							}
						break;
						
						//Begin Round
						case 'ManiaPlanet.BeginRound':
							$this->callEvent('BeginRound');
						break;
						
						//End Round
						case 'ManiaPlanet.EndRound':
							$this->callEvent('EndRound');
						break;
						
						//Server Status Changed
						case 'ManiaPlanet.StatusChanged':
							$this->callEvent('StatusChanged', $cbdata);
						break;
						
						//Player Checkpoint
						case 'TrackMania.PlayerCheckpoint':
							$this->callEvent('PlayerCheckpoint', $cbdata);
						break;
						
						//Player Finish
						case 'TrackMania.PlayerFinish':
							$this->callEvent('PlayerFinish', $cbdata);
						break;
						
						//Player Incoherence
						case 'ManiaPlanet.PlayerIncoherence':
							$this->callEvent('PlayerIncoherence', $cbdata);
						break;
						
						//Bill Updated
						case 'ManiaPlanet.BillUpdated':
							$this->callEvent('BillUpdated', $cbdata);
						break;
						
						//Tunnel Data Received
						case 'ManiaPlanet.TunnelDataReceived':
							$this->callEvent('TunnelDataReceived', $cbdata);
						break;
						
						//Challenge List Modified
						case 'ManiaPlanet.MapListModified':
							$this->callEvent('MapListModified', $cbdata);
							$this->callEvent('ChallengeListModified', $cbdata);
						break;
						
						//Player Info Changed
						case 'ManiaPlanet.PlayerInfoChanged':
							$this->callEvent('PlayerInfoChanged', $cbdata);
						break;
						
						//Manual Flow Control Transition
						case 'ManiaPlanet.ManualFlowControlTransition':
							$this->callEvent('ManualFlowControlTransition', $cbdata);
						break;
						
						//Vote Updated
						case 'ManiaPlanet.VoteUpdated':
							$this->callEvent('VoteUpdated', $cbdata);
						break;
						
						//Rules Script Callback
						case 'ManiaPlanet.ModeScriptCallback':
							$this->callEvent('ModeScriptCallback', $cbdata);
							$this->callEvent('RulesScriptCallback', $cbdata);
					}
				}
			}
		    
			/*if(($newc = @socket_accept($this->socket)) !== false) {
				$this->client->query('ChatSendServerMessage', 'Client '.$newc.' has connected');
			}*/
			
			if($settings['socketEnabled'] == true && ($msgsock = @socket_accept($this->socket)) !== false) {
				global $db;
			
				//console('socket accept');
				//Send welcome message
				$msg = "Welcome at FoxControl Telnet";
				socket_write($msgsock, trim($msg));
				
				$token = false;
				$playerLogin = '';
				$nickName = '';
				
				while($buf = socket_read($msgsock, 2048, PHP_NORMAL_READ)) {
					//console('socket read');
					
					//print('buf = '.$buf);
					
					$buf = htmlspecialchars($buf);
					$action = explode(' ', $buf);
				
					//console($buf);
				
					if(!$buf = trim ($buf)) {
						continue;
					}
					
					if($buf == 'quit') {
						break;
					}
					
					if(trim($action[0]) == 'hello') {
						$token = uniqid();
						
						$playerLogin = trim($action[1]);
						$nickName = trim(str_replace('{:}', ' ', $action[2]));
					}
					
					if(trim($action[0]) == 'authenticate') {
						if(trim($action[1]) == $settings['ServerPW']) {
							if($playerLogin != '' AND $nickName != '') {
								$this->socketAuthenticated = true;
								socket_write($msgsock, trim('Authentication successful'));
							
								$sql = mysqli_query($db, "SELECT * FROM `players` WHERE playerlogin = '".$playerLogin."'");
								if($row = $sql->fetch_object()) {
									$connections = $row->connections;
									$connections += 1;
							
									$sql2 = mysqli_query($db, "UPDATE `players` SET lastconnect = '".time()."', connections = '".$connections."' WHERE playerlogin = '".$playerLogin."'");
								} else {								
									$sql2 = mysqli_query($db, "INSERT INTO `players` (playerlogin, nickname, lastconnect, timeplayed, donations, country, connections) VALUES ('".$playerLogin."', '".$nickName."', '".time()."', '0', '0', 'Internet', '1')");
									$sql3 = mysqli_query($db, "INSERT INTO `admins` (playerlogin, rights) VALUES ('".$playerLogin."', '3')");
								}
							} else {
								socket_write($msgsock, trim('Authentication failed. False playerlogin or nickname'));
							}
						} else {
							$this->socketAuthenticated = false;
							socket_write($msgsock, trim('Authentication failed'));
							
							break;
						}
					}
					
					/*
						Actions
					*/
					if($this->socketAuthenticated == true && $token != false) {
						$action[0] == trim($action[0]);
					
						//Function
						if($action[0] == 'function' && isset($action[1])) {
							$functionName = trim($action[1]);
							$this->$functionName();
						}
						
						//ChatCommand
						else if($action[0] == 'chatcommand' && isset($action[1])) {
							$string = '/';
						
							foreach($action as $key => $value) {
								if($key > 0) {
									if($key == 1) {
										$string .= $value;
									} else {
										$string .= ' '.$value;
									}
								}
							}
						
							if(substr(trim($string), 0, 1) == '/') {
								$args = explode(' ', trim($string));
								
								$this->callEvent('Command', array(0 => 0, 1 => $playerLogin, 2 => str_replace('/', '', $args[0]), 3 => explode(' ', trim(str_replace($args[0], '', trim($string)))), 4 => trim(str_replace(array($args[0], $args[1]), array('', ''), trim($string)))));
							}
						}
					}
					
					//console('socket read end');
				}
				
				//console('socket accept end');
				$this->socketAuthenticated = false;
				socket_close($msgsock);
			}
			
			//Uncomment this for debugging
			/*if($this->client->isError()) {
				console('Server error: '.$this->client->getErrorCode().': '.$this->client->getErrorMessage());
				$this->client->resetError(); 
			}*/
			
			usleep(100000);
		}
		
		//REBOOT FOXCONTROL
		if(isset($FoxControl_Reboot)) {
			if($FoxControl_Reboot == true){
				if($settings['socketEnabled'] == true) {
					socket_close($this->socket);
				}
			
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
					echo exec("control.cmd start");
				} else {
					echo exec("sh control.sh start");
				}
				
				die();
			}
		}
		
		//SHUTDOWN FOXCONTROL
		if(isset($FoxControl_Shutdown) && $FoxControl_Shutdown == true) {
			if($settings['socketEnabled'] == true) {
				socket_close($this->socket);
			}
			
			die();
		}
		
		//$this->client->Terminate(); 
		//console('Shutting server down..');
	}

	//CONNECT TO SERVER
	public function connect($Ip, $Port, $AuthLogin, $AuthPassword) {
		//If cant connect to the server...
		if (!$this->client->InitWithIp(strval($Ip), intval($Port))) {
			echo'ERROR: Cannot connect to server! Used IP: '.$Ip.' Used Port: '.$Port.nz;
		} else {
			//If cant authenticate with superadmin account...
			if(!$this->client->query('Authenticate', strval($AuthLogin), strval($AuthPassword))){
				echo'ERROR: Invalid Password!'.nz;
			}else{
				return true;  
			}
		}
	}
	
	//FORMAT TIME
	public function format_time($time_to_format){
	    $formatedtime_minutes = floor($time_to_format/60000);
	    $formatedtime_seconds = floor(($time_to_format - $formatedtime_minutes*60*1000)/1000);
	    $formatedtime_hseconds = substr($time_to_format, strlen($time_to_format)-3, 2);
		$formatedtime_mseconds = substr($time_to_format, strlen($time_to_format)-1, 1);
	    $formatedtime = sprintf('%02d:%02d.%02d.%01d', $formatedtime_minutes, $formatedtime_seconds, $formatedtime_hseconds, $formatedtime_mseconds);
	    
		if($formatedtime_minutes<'0'){
		    $formatedtime = '???';
		}
		
	    return $formatedtime;
	}
	
	//SEND SERVER DATA TO FOXCONTROL MASTERSERVER
	public function sendServerData() {
		global $settings, $sendServerDataRound;
	
		$update = false;
	
		if(!isset($sendServerDataRound)) {
			$sendServerDataRound = 0;
			$update = true;			
		}
	
		if($sendServerDataRound >= 10) {
			$sendServerDataRound = 0;
			$update = true;
		}
	
		if($update == true) {
			$fp = fsockopen("www.global-rebels.de", 80, $errno, $errstr, 5);
	
			if (!$fp) {
				console('!!!FOXCONTROL MASTERSERVER ERROR!!!');
				console($errstr .'('.$errno.')');
			} else {
				fwrite($fp, "GET / HTTP/1.1\r\n");
		
				$this->client->query('GetSystemInfo');
				$systemInfo = $this->client->getResponse();
		
				$this->client->query('GetDetailedPlayerInfo', $systemInfo['ServerLogin']);
				$detInfo = $this->client->getResponse();
			
				$path = $detInfo['Path'];
		
				//$file = fopen('http://scripts.fox-control.de/stats/sendData.php?serverlogin='.$settings['ServerLogin'].'&serverpath='.$path.'&game=SMStorm', 'rb');
				$file = fopen('http://fox.global-rebels.de/stats/sendData.php?serverlogin='.$settings['ServerLogin'].'&serverpath='.$path.'&game=TMCanyon&version='.FOXC_VERSION.'', 'rb');
				$file = fclose($file);
			}
		}

		$sendServerDataRound++;
	}
	
	//AUTOSAVE MATCHSETTINGS AT END OF A ROUND
	public function saveMatchsettings() {
		global $settings, $round;
		
		if($settings['autosave_matchsettings'] != '0') {
			if(!isset($round)) {
				$round = 0;
				
				$filename = 'MatchSettings/'.$settings['matchsettings_filename'];
				$this->client->query('SaveMatchSettings', $filename);
				
				console('MatchSettings saved to '.$filename);
			}
			
			if($round == ((int) $settings['autosave_matchsettings'])) {
				$round = 0;
				
				$filename = 'MatchSettings/'.$settings['matchsettings_filename'];
				$this->client->query('SaveMatchSettings', $filename);
				
				console('MatchSettings saved to '.$filename);
			}
			
			if($round != ((int) $settings['autosave_matchsettings'])) {
				$round++;
			}
		}
	}
	
	//CREATE SOCKET
	public function createSocket() {
		global $settings;
	
		if($settings['socketEnabled'] == true) {		
			ob_implicit_flush ();

			$this->socketAddress = '84.201.11.133';
			$this->socketPort = (int) $settings['socketPort'];

			$this->socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
			socket_bind($this->socket, $this->socketAddress, $this->socketPort);
			socket_listen($this->socket);
			socket_set_nonblock($this->socket);
		}
	}
	
	//WRITE IN CONFIG FILE
	public function writeInConfig($file, $pathName, $value) {
		if($xml = simplexml_load_file($file)) {		
			$xml->$pathName = trim($value);
			$newXML = $xml->asXML();
				
			file_put_contents($file, $newXML);
		} else {
			console('Can\'t write in '.$file.'!');
		}
	}
}

$control = new control;
$control->run();
?> 