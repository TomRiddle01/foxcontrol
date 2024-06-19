<?php
//* plugin.menu.php - FoxControl Main menu
//* Version:   0.4
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_menu extends FoxControlPlugin {
	public $items_count = 0;
	public $menu_items = array();
	public $config;
	
	/*
	ADD MENU ITEMS
	*/
	public function menuItems() {
		//Menu item name, Submenu Name, Rank (1 = all, 2 = OPs & Admins, 3 = Super Admins), actionID
		
		//Main Menu
		$this->registerItem('Fox', false, 0, $this->mlids[2]);
		$this->registerItem('Admin', false, 1, $this->mlids[3]);
		$this->registerItem('Maps', false, 0, $this->mlids[4]);
		$this->registerItem('Records', false, 0, $this->mlids[29]);
		$this->registerItem('Players', false, 0, $this->mlids[5]);
		$this->registerItem('Chat', false, 0, $this->mlids[6]);
		$this->registerItem('Info', false, 0, $this->mlids[7]);
		$this->registerItem('Help', false, 0, $this->mlids[26]);
		$this->registerItem('ManiaCommunity', false, 0, 'url:http://www.mania-community.de');
		
		//Fox Submenu
		$this->registerItem('Fox Website', 'Fox', 0, 'url:http://www.fox-control.de');
		$this->registerItem('Fox Forum', 'Fox', 0, 'url:http://forum.fox-control.de');
		
		//Admin Submenu
		$this->registerItem('Admins', 'Admin', 2, $this->mlids[11]);
		$this->registerItem('Maps', 'Admin', 1, $this->mlids[12]);
		$this->registerItem('Players', 'Admin', 1, $this->mlids[13]);
		$this->registerItem('Plugins', 'Admin', 3, $this->mlids[33]);
		$this->registerItem('Server Info', 'Admin', 2, $this->mlids[15]);
		$this->registerItem('Reboot', 'Admin', 3, $this->mlids[16]);
		$this->registerItem('Help', 'Admin', 1, $this->mlids[14]);
			
		//Tracks Submenu
		$this->registerItem('Maplist', 'Maps', 0, $this->mlids[24]);
		$this->registerItem('No Rank', 'Maps', 0, $this->mlids[28]);
		$this->registerItem('Jukebox', 'Maps', 0, $this->mlids[32]);
			
		//Records Submenu
		$this->registerItem('Local Records', 'Records', 0, $this->mlids[30]);
		$this->registerItem('Dedimania', 'Records', 0, $this->mlids[31]);
			
		//Chat Submenu
		$this->registerItem('FOX', 'Chat', 0, $this->mlids[19]);
		$this->registerItem('Good Game', 'Chat', 0, $this->mlids[20]);
		$this->registerItem('LOL', 'Chat', 0, $this->mlids[21]);
		$this->registerItem('BRB', 'Chat', 0, $this->mlids[22]);
		$this->registerItem('AFK', 'Chat', 0, $this->mlids[23]);
	}
	
	/*
	DISPLAY MENU AT STARTUP
	*/
	public function onStartUp() {
		$this->name = 'Menu';
		$this->author = 'matrix142';
		$this->version = '0.4';
		$this->registerMLIds(45);
		$this->menuItems();
		
		$this->config = $this->loadConfig();
		
		$this->displayMenuButton($this->mlids[0]);
	}
	
	/*
	DISPLAY MENU TO CONNECTED PLAYER
	*/
	public function onPlayerConnect($args) {
		$this->displayMenuButton($this->mlids[0], $args['Login']);
	}
	
	/*
	EXECUTE ACTIONS
	*/
	public function onManialinkPageAnswer($args) {
		//Open Main Menu
		if($args[2] == $this->mlids[0]){			
			$this->displayMenuButton($this->mlids[1], $args[1]);
			
			$this->displayItems(false, $args[1]);
		}
		
		//Close Menu
		else if($args[2] == $this->mlids[1]){
			$this->closeML($this->mlids[3], $args[1]);
			$this->closeML($this->mlids[2], $args[1]);
			$this->closeMl($this->mlids[1], $args[1]);
		
			$this->displayMenuButton($this->mlids[0], $args[1]);
		}
		
		//Open Fox Submenu
		else if($args[2] == $this->mlids[2]){
			$this->displayItems('Fox', $args[1]);
		}
		
		//Open Admin Submenu
		else if($args[2] == $this->mlids[3]){
			$this->displayItems('Admin', $args[1]);
		}
			//Show Admin List
			else if($args[2] == $this->mlids[11]){
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
				
				$pluginPlayers = $this->getPluginInstance('plugin_players');
				if($pluginPlayers === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$pluginPlayers->onCommand(array(1 => $args[1], 2 => 'admins', 3 => array(0 => 'all')));
				}
			}
			//Show Admin Track List
			else if($args[2] == $this->mlids[12]){
				$pluginChallenges = $this->getPluginInstance('plugin_challenges');
			
				if($pluginChallenges === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
				
					$pluginChallenges->displayList($args[1], true);
				}	
			}
			//Show Playerlist for Admins
			else if($args[2] == $this->mlids[13]){
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
				
				$pluginPlayers = $this->getPluginInstance('plugin_players');
				if($pluginPlayers === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$pluginPlayers->onCommand(array(1 => $args[1], 2 => 'players', 3 => array(0 => 'admin')));
				}
			}
			//Plugin Manager
			else if($args[2] == $this->mlids[33]){
				$managerPlugins = $this->getPluginInstance('manager_plugins');
				if($managerPlugins !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
				
					$managerPlugins->onCommand(array(1 => $args[1], 2 => 'plugins'));
				}
			}
			//Show Admin Commands
			else if($args[2] == $this->mlids[14]){
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
				$pluginAdminchat = $this->getPluginInstance('chat_admin');
				if($pluginAdminchat === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$pluginAdminchat->onCommand(array(1 => $args[1], 2 => 'adminhelp'));
				}
			}
			//Show Server Info
			else if($args[2] == $this->mlids[15]){
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
			
				$window = $this->window;
				$window->init();
				$window->title('Server Info');
				$window->displayAsTable(true);
				$window->size(60, '');
				
				$window->content('<td width="15">Betriebssystem:</td><td width="3"></td><td width="30">$o'.PHP_OS.'</td>');
				$window->content('<td width="15">PHP Version:</td><td width="3"></td><td width="30">$o'.phpversion().'</td>');
				$window->content('<td width="15">Memory Usage:</td><td width="3"></td><td width="30">$o'.memory_get_usage().'</td>');
				$window->content('<td width="15">Zend Version:</td><td width="3"></td><td width="30">$o'.zend_version().'</td>');
				
				$window->addButton('Close', '10', true);
				
				$window->show($args[1]);
			}
			//Reboot
			else if($args[2] == $this->mlids[16]){
				$this->instance()->FoxControl_reboot();
			}
			
		//Show Track Submenu
		else if($args[2] == $this->mlids[4]){
			$this->displayItems('Maps', $args[1]);
		}
			//Show Track List
			else if($args[2] == $this->mlids[24]){
				$pluginChallenges = $this->getPluginInstance('plugin_challenges');
			
				if($pluginChallenges === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
				
					$pluginChallenges->onCommand(array(1 => $args[1], 2 => 'list'));
				}
			}
			//Show No Rank Tracks
			else if($args[2] == $this->mlids[28]){
				$pluginNoRank = $this->getPluginInstance('plugin_norank');
			
				if($pluginNoRank === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
				
					$pluginNoRank->displayList($args[1]);
				}
			}
			//Show Jukebox
			else if($args[2] == $this->mlids[32]) {
				$pluginJukebox = $this->getPluginInstance('plugin_jukebox');
				
				if($pluginJukebox !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginJukebox->onCommand(array(1 => $args[1], 2 => 'jukebox'));
				}
			}
			
		//Show Records Submenu
		else if($args[2] == $this->mlids[29]) {
			$this->displayItems('Records', $args[1]);
		}
			//Show Local Records List
			else if($args[2] == $this->mlids[30]) {
				$pluginRecords = $this->getPluginInstance('plugin_records');
				if($pluginRecords === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
				
					$pluginRecords->onCommand(array(1 => $args[1], 2 => 'records'));
				}
			}
			//Show Dedimania Records List
			else if($args[2] == $this->mlids[31]) {
				$pluginDedimania = $this->getPluginInstance('plugin_dedimania');
				if($pluginDedimania === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginDedimania->onCommand(array(1 => $args[1], 2 => 'dedimania'));
				}
			}
			
		//Show Playerlist
		else if($args[2] == $this->mlids[5]){
			$this->closeML($this->mlids[3], $args[1]);
			$this->closeML($this->mlids[2], $args[1]);
			
			$pluginPlayers = $this->getPluginInstance('plugin_players');
			if($pluginPlayers === false) {
				$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
			} else {
				$pluginPlayers->onCommand(array(1 => $args[1], 2 => 'players'));
			}
		}
		
		//Open Chat Submenu
		else if($args[2] == $this->mlids[6]){
			$this->displayItems('Chat', $args[1]);
		}
			//Execute Chat Commands
			else if($args[2] == $this->mlids[19]){
				$this->instance()->chat_with_nick('$09fF$fffOX $09fR$fffulZzz!', $args[1]);
			}else if($args[2] == $this->mlids[20]){
				$this->instance()->chat_with_nick('$09fG$fffood $09fG$fffame $09fA$fffll', $args[1]);
			}else if($args[2] == $this->mlids[21]){
				$this->instance()->chat_with_nick('$09fL$fffo$09f0$fffo$09fL', $args[1]);
			}else if($args[2] == $this->mlids[22]){
				$this->instance()->chat_with_nick('$09fB$fffe $09fR$fffight $09fB$fffack', $args[1]);
			}else if($args[2] == $this->mlids[23]){
				$this->instance()->chat_with_nick('$09fA$fffway $09fF$fffrom $09fK$fffeyboard', $args[1]);
		    
				$this->instance()->client->query('ForceSpectator', $args[1], 1);
			
				$this->displayManialinkToLogin($args[1], '
				<quad posn="0 -27 1" sizen="25 4" halign="center" style="Bgs1InRace" substyle="NavButtonBlink" action="'.$this->mlids[25].'" />
				<label posn="0 -28 2" halign="center" style="TextPlayerCardName" text="$o$fffClick here to play!" action="'.$this->mlids[25].'" />',
				$this->mlids[25]);
			}else if($args[2] == $this->mlids[25]){
				$this->instance()->client->query('ForceSpectator', $args[1], 2);
				$this->closeMl($this->mlids[25], $args[1]);
				$this->instance()->chat_with_nick('$09fI$fff\'m $09fB$fffack', $args[1]);
			}
		
		//Show Info
		else if($args[2] == $this->mlids[7]){
			global $settings;
			
			$this->closeML($this->mlids[3], $args[1]);
			$this->closeML($this->mlids[2], $args[1]);
			
			$this->instance()->client->query('GetServerName');
			$servername=$this->instance()->client->getResponse();
		
			$this->instance()->client->query('GetServerComment');
			$comment=$this->instance()->client->getResponse();
		
			$this->instance()->client->query('GetMaxPlayers');
			$maxpl=$this->instance()->client->getResponse();
		
			$this->instance()->client->query('GetMaxSpectators');
			$maxspec=$this->instance()->client->getResponse();
		
			$this->instance()->client->query('IsChallengeDownloadAllowed');
			$challengedl=$this->instance()->client->getResponse();
		
			$this->instance()->client->query('GetSystemInfo');
			$systemInfo = $this->instance()->client->getResponse();
		
			$this->instance()->client->query('GetDetailedPlayerInfo', $systemInfo['ServerLogin']);
			$detInfo = $this->instance()->client->getResponse();
			
			$path = $detInfo['Path'];
		
			if($challengedl==1) $challengedl2= 'true';
			elseif($challengedl==0) $challengedl2= 'false';

			$window = $this->window;
			$window->init();
			$window->title('$fffInfo');
			$window->displayAsTable(true);
			$window->size(60, '');
			
			$window->addButton('Close', '10', true);
			
			$window->content('<td width="15">Servername:</td><td width="3"></td><td width="30">$o'.$servername.'</td>');
			$window->content('<td width="15">Server Comment:</td><td width="3"></td><td width="30">$o'.$comment.'</td>');
			$window->content('<td width="15">Login:</td><td width="3"></td><td width="30">$o'.$settings['ServerLogin'].'</td>');
			$window->content('<td width="15">Max. Players:</td><td width="3"></td><td width="30">$o'.$maxpl['CurrentValue'].'</td>');
			$window->content('<td width="15">Max. Spectators:</td><td width="3"></td><td width="30">$o'.$maxspec['CurrentValue'].'</td>');
			$window->content('<td width="15">Challenge Download:</td><td width="3"></td><td width="30">$o'.$challengedl2.'</td>');
			$window->content('<td width="15">Server Nation:</td><td width="3"></td><td width="30">$o'.$settings['Nation'].'</td>');
			$window->content('<td width="15">Server Location:</td><td width="3"></td><td width="30">$o'.$path.'</td>');
			$window->content('<td> </td>');
			$window->content('<td width="15">FoxControl Version:</td><td width="3"></td><td width="30">$o'.FOXC_VERSIONP.' '.FOXC_VERSION.'</td>');
			$window->content('<td width="15">Build:</td><td width="3"></td><td width="30">$o'.FOXC_BUILD.'</td>');
			
			$window->show($args[1]);
		}
		
		//Help
		else if($args[2] == $this->mlids[26]) {
			$chatPlayer = $this->getPluginInstance('chat_player');
			if($chatPlayer === false) {
				$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
			} else {
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
			
				$chatPlayer->onCommand(array(1 => $args[1], 2 => 'help'));
			}
		}
	}
	
	/*
	REGISTER MENU ITEMS
	*/
	public function registerItem($name, $submenu, $rank, $action) {	
		$this->menu_items[$this->items_count] = array();;
		$this->menu_items[$this->items_count]['name'] = $name;
		$this->menu_items[$this->items_count]['submenu'] = $submenu;
		$this->menu_items[$this->items_count]['rank'] = $rank;
		$this->menu_items[$this->items_count]['action'] = $action;
		
		$this->items_count++;
	}
	
	/*
	DISPLAY MENU BUTTON
	*/
	public function displayMenuButton($action, $login = '') {
		global $settings;
		
		$posn = explode(' ', $this->config->posn_menu_button);
		
		$code = '
		<quad posn="'.$posn[0].' '.$posn[1].' 1" sizen="13 3.8" halign="center" valign="center" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$action.'"/>
		<label posn="'.($posn[0]-5.5).' '.($posn[1]+0.5).' 2" text="$fff$o'.$settings['menu_name'].'" style="TextCardSmallScores2Rank" action="'.$action.'" />';
		
		if(!empty($login)){
			$this->displayManialinkToLogin($login, $code, $this->mlids[0]);
		}else{
			$this->displayManialink($code, $this->mlids[0]);
		}
	}
	
	/*
	DISPLAY MENU ITEMS
	*/
	public function displayItems($submenu, $login) {
		global $settings;
		
		$rank = $this->getRights($login);
		$posn = explode(' ', $this->config->posn_menu);
		
		$id = 0;
		$id2 = 0;
		$y = $posn[1];
		
		if($submenu == false){
			$mlid = $this->mlids[1];
			$x = $posn[0] - 0.4;
		}else{
			$mlid = $this->mlids[2];
			$x = ($posn[0]-14);
		}
		
		$code = '';
		
		while(isset($this->menu_items[$id])){
			if($this->menu_items[$id]['submenu'] == $submenu){
				if($rank[0] >= $this->menu_items[$id]['rank']){
					if(preg_match('/url:/', $this->menu_items[$id]['action'])){
						$link = 'url="'.str_replace('url:', '', $this->menu_items[$id]['action']).'"';
					}
					else if(preg_match('/ml:/', $this->menu_items[$id]['action'])){
						$link = 'manialink="'.str_replace('ml:', '', $this->menu_items[$id]['action']).'"';
					}
					else{
						$link = 'action="'.$this->menu_items[$id]['action'].'"';
					}
					$code .= '
					<quad posn="'.$x.' '.$y.' 21" sizen="13 3.8" valign="top" style="'.$settings['default_style2'].'" '.$link.' substyle="'.$settings['default_substyle2'].'" />
					<label posn="'.($x +1).' '.($y-1.35).' 22" valign="top" style="TextCardSmallScores2Rank" text="$o$fff'.$this->menu_items[$id]['name'].'" '.$link.' />';
				
					$y -= 4;
					$id2++;
				}
			}
			$id++;
		}
		$code .= '<quad posn="'.($x - 0.5).' 21.9 20" sizen="14 '.(($id2 * 4) + 0.8).'" valign="top" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />';
		
		if(!empty($login)){
			$this->displayManialinkToLogin($login, $code, $mlid);
		}else{
			$this->displayManialink($code, $mlid);
		}
	}
}
?>