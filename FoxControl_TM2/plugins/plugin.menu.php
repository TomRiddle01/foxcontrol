<?php
//* plugin.menu.php - FoxControl Main menu
//* Version:   1.1
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_menu extends FoxControlPlugin {
	public $items_count = 0;
	public $menu_items = array();
	public $config;
	public $posn_button;
	public $posn_menu;
	
	/*
	ADD MENU ITEMS
	*/
	public function menuItems() {
		/************************
		**********SYNTAX*********
		*************************
		$this->registerItem('ItemName', 'SubMenuName', 'RequiredRank', 'ActionID');
		
		RequiredRank can be 0 = Player, 1 = Moderator, 2 = Admin, 3 = SuperAdmin
		ActionID is $this->mlids[ID];
		
		IDs which are already used for other items: 0 - 40
		*/
		
		//Main Menu
		$this->registerItem('Admin', false, 1, $this->mlids[4]);
		$this->registerItem('Map', false, 0, $this->mlids[5]);
		$this->registerItem('Records', false, 0, $this->mlids[36]);
		$this->registerItem('Players', false, 0, $this->mlids[6]);
		$this->registerItem('Widgets', false, 0, $this->mlids[7]);
		$this->registerItem('Info', false, 0, $this->mlids[8]);
		$this->registerItem('Chat Commands', false, 0, $this->mlids[9]);
		$this->registerItem('Fox Support', false, 0, $this->mlids[10]);
		$this->registerItem('ManiaCommunity', false, 0, 'url:http://www.mania-community.de');
		
		//Admin Submenu
		$this->registerItem('Admins', 'Admin', 1, $this->mlids[11]);
		$this->registerItem('Map', 'Admin', 1, $this->mlids[12]);
		$this->registerItem('Players', 'Admin', 1, $this->mlids[13]);
		$this->registerItem('Mode', 'Admin', 2, $this->mlids[28]);
		$this->registerItem('Plugins', 'Admin', 3, $this->mlids[14]);
		$this->registerItem('System Info', 'Admin', 2, $this->mlids[15]);
		$this->registerItem('Admin Commands', 'Admin', 1, $this->mlids[17]);
		$this->registerItem('Reboot', 'Admin', 3, $this->mlids[16]);
		
			//Admin Map Submenu
			$this->registerItem('Map List', 'Admin.Map', 1, $this->mlids[18]);
			$this->registerItem('Restart', 'Admin.Map', 1, $this->mlids[19]);
			$this->registerItem('Queue Restart', 'Admin.Map', 1, $this->mlids[20]);
			$this->registerItem('Round End', 'Admin.Map', 1, $this->mlids[21]);
			$this->registerItem('Skip', 'Admin.Map', 1, $this->mlids[22]);
			$this->registerItem('Remove Current', 'Admin.Map', 1, $this->mlids[23]);
		
			//Admin Mode Submenu
			$this->registerItem('Rounds', 'Admin.Mode', 2, $this->mlids[29]);
			$this->registerItem('TimeAttack', 'Admin.Mode', 2, $this->mlids[30]);
			$this->registerItem('Team', 'Admin.Mode', 2, $this->mlids[31]);
			$this->registerItem('Laps', 'Admin.Mode', 2, $this->mlids[32]);
			$this->registerItem('Cup', 'Admin.Mode', 2, $this->mlids[33]);
		
		//Map Submenu
		$this->registerItem('Map List', 'Map', 0, $this->mlids[24]);
		$this->registerItem('Best Maps', 'Map', 0, $this->mlids[35]);
		$this->registerItem('No Rank', 'Map', 0, $this->mlids[37]);
		$this->registerItem('Jukebox', 'Map', 0, $this->mlids[25]);
		
		//Records Submenu
		$this->registerItem('Dedimania', 'Records', 0, $this->mlids[38]);
		$this->registerItem('Local Records', 'Records', 0, $this->mlids[39]);
		$this->registerItem('Live Rankings', 'Records', 0, $this->mlids[40]);
		
		//Players Submenu
		$this->registerItem('Player List', 'Players', 0, $this->mlids[26]);
		$this->registerItem('Most Active', 'Players', 0, $this->mlids[27]);
		$this->registerItem('Top Donators', 'Players', 0, $this->mlids[34]);
		
		//Fox Submenu
		$this->registerItem('Fox Website', 'Fox Support', 0, 'url:http://www.fox-control.de');
		$this->registerItem('Fox Forum', 'Fox Support', 0, 'url:http://forum.fox-control.de');
	}
	
	/*
	DISPLAY MENU AT STARTUP
	*/
	public function onStartUp() {
		$this->name = 'Menu';
		$this->author = 'matrix142';
		$this->version = '1.1';
		$this->registerMLIds(200);
		$this->menuItems();
		
		$this->config = $this->loadConfig();
		
		$this->onBeginMap(false);
		$this->displayMenuButton($this->mlids[0]);
	}
	
	/*
		ON BEGIN MAP
	*/
	public function onBeginMap($args) {
		$this->posn_button = $this->getPosn('button');
		$this->posn_menu = $this->getPosn('menu');
		
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
		
		/*
			Admin Submenu
		*/
		else if($args[2] == $this->mlids[4]){
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
			/*
				Admin Map Submenu
			*/
			else if($args[2] == $this->mlids[12]) {
				$this->displayItems('Admin.Map', $args[1]);
			}
				//Admin Map Maplist
				else if($args[2] == $this->mlids[18]) {
					$pluginMaps = $this->getPluginInstance('plugin_challenges');
			
					if($pluginMaps === false) {
						$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
					} else {
						$this->closeML($this->mlids[3], $args[1]);
						$this->closeML($this->mlids[2], $args[1]);
				
						$pluginMaps->displayList($args[1], true);
					}
				}
				//Admin Map Actions
				else if($args[2] >= $this->mlids[19] && $args[2] <= $this->mlids[23]) {
					if($args[2] == $this->mlids[19]) $action = 'restart';
					else if($args[2] == $this->mlids[20]) $action = 'replay';
					else if($args[2] == $this->mlids[21]) $action = 'endround';
					else if($args[2] == $this->mlids[22]) $action = 'skip';
					else if($args[2] == $this->mlids[23]) $action = 'remove';
					
					$chatAdmin = $this->getPluginInstance('chat_admin');
					if($chatAdmin === false) {
						$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
					} else {
						$chatAdmin->onCommand(array(1 => $args[1], 2 => $action, 3 => array(0 => 'map', 1 => 'current')));
					}
				}
			
			/*
				Admin Mode Submenu
			*/
			else if($args[2] == $this->mlids[28]) {
				$this->displayItems('Admin.Mode', $args[1]);
			}
				else if($args[2] >= $this->mlids[29] && $args[2] <= $this->mlids[33]) {
					$pluginChatAdmin = $this->getPluginInstance('chat_admin');
					
					if($pluginChatAdmin !== false) {
						//rounds
						if($args[2] == $this->mlids[29]) {
							$pluginChatAdmin->onCommand(array(1 => $args[1], 2 => 'mode', 3 => array(0 => 'rounds')));
						}
						//timeattack
						else if($args[2] == $this->mlids[30]) {
							$pluginChatAdmin->onCommand(array(1 => $args[1], 2 => 'mode', 3 => array(0 => 'timeattack')));
						}
						//team
						else if($args[2] == $this->mlids[31]) {
							$pluginChatAdmin->onCommand(array(1 => $args[1], 2 => 'mode', 3 => array(0 => 'team')));
						}
						//laps
						else if($args[2] == $this->mlids[32]) {
							$pluginChatAdmin->onCommand(array(1 => $args[1], 2 => 'mode', 3 => array(0 => 'laps')));
						}
						//cup
						else if($args[2] == $this->mlids[33]) {
							$pluginChatAdmin->onCommand(array(1 => $args[1], 2 => 'mode', 3 => array(0 => 'cup')));
						}
					} else {
						$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
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
			else if($args[2] == $this->mlids[14]){
				$managerPlugins = $this->getPluginInstance('manager_plugins');
				if($managerPlugins !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
				
					$managerPlugins->onCommand(array(1 => $args[1], 2 => 'plugins'));
				}
			}
			//Show Admin Commands
			else if($args[2] == $this->mlids[17]){
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
			
		/*
			Map Submenu
		*/
		else if($args[2] == $this->mlids[5]){
			$this->displayItems('Map', $args[1]);
		}
			//Show Map List
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
			
			//Best Maps
			else if($args[2] == $this->mlids[35]) {
				$pluginBestMaps = $this->getPluginInstance('plugin_bestmaps');
				
				if($pluginBestMaps !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginBestMaps->showBestMaps($args[1]);
				} else {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
			
			//No Rank
			else if($args[2] == $this->mlids[37]) {
				$pluginNoRank = $this->getPluginInstance('plugin_norank');
				
				if($pluginNoRank !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginNoRank->displayList($args[1]);
				} else {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
			
			//Show Jukebox
			else if($args[2] == $this->mlids[25]) {
				$pluginJukebox = $this->getPluginInstance('plugin_jukebox');
				
				if($pluginJukebox !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginJukebox->onCommand(array(1 => $args[1], 2 => 'jukebox'));
				} else {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
		
		/*
			Records Submenu
		*/
		else if($args[2] == $this->mlids[36]) {
			$this->displayItems('Records', $args[1]);
		}
		
			//Dedimania
			else if($args[2] == $this->mlids[38]) {
				$pluginDedimania = $this->getPluginInstance('plugin_dedimania');
				
				if($pluginDedimania !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginDedimania->onCommand(array(1 => $args[1], 2 => 'dedimania'));
				} else {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
			
			//Local Records
			else if($args[2] == $this->mlids[39]) {
				$pluginRecords = $this->getPluginInstance('plugin_records');
				
				if($pluginRecords !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginRecords->onCommand(array(1 => $args[1], 2 => 'records'));
				} else {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
			
			//Live Rankings
			else if($args[2] == $this->mlids[40]) {
				$pluginRecords = $this->getPluginInstance('plugin_records');
				
				if($pluginRecords !== false) {
					$this->closeML($this->mlids[3], $args[1]);
					$this->closeML($this->mlids[2], $args[1]);
					
					$pluginRecords->onCommand(array(1 => $args[1], 2 => 'liveranks'));
				} else {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
		
		/*
			Player Submenu
		*/
		else if($args[2] == $this->mlids[6]){
			$this->displayItems('Players', $args[1]);
		}
		
			//Playerlist
			else if($args[2] == $this->mlids[26]) {
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
				
				$pluginPlayers = $this->getPluginInstance('plugin_players');
				if($pluginPlayers === false) {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				} else {
					$pluginPlayers->onCommand(array(1 => $args[1], 2 => 'players'));
				}
			}
			//Most Active
			else if($args[2] == $this->mlids[27]) {
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
			
				$pluginMostActive = $this->getPluginInstance('plugin_mostactive');
				
				if($pluginMostActive !== false) {
					$pluginMostActive->onCommand(array(1 => $args[1], 2 => 'mostactive'));
				} else  {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
			//Top Dons
			else if($args[2] == $this->mlids[34]) {
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
			
				$pluginMostActive = $this->getPluginInstance('plugin_topdons');
				
				if($pluginMostActive !== false) {
					$pluginMostActive->onCommand(array(1 => $args[1], 2 => 'topdons'));
				} else  {
					$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
				}
			}
		
		//Show Widgetlist
		else if($args[2] == $this->mlids[7]) {
			$pluginWidgetSettings = $this->getPluginInstance('plugin_widgetsettings');
			if($pluginWidgetSettings !== false) {
				$pluginWidgetSettings->onCommand(array(1 => $args[1], 2 => 'widgets'));
			} else {
				$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
			}
		}
		
		//Show Info
		else if($args[2] == $this->mlids[8]){
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
		
			$this->instance()->client->query('IsMapDownloadAllowed');
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
			$window->content('<td width="15">Map Download:</td><td width="3"></td><td width="30">$o'.$challengedl2.'</td>');
			$window->content('<td width="15">Server Nation:</td><td width="3"></td><td width="30">$o'.$settings['Nation'].'</td>');
			$window->content('<td width="15">Server Location:</td><td width="3"></td><td width="30">$o'.$path.'</td>');
			$window->content('');
			$window->content('<td width="15">FoxControl Version:</td><td width="3"></td><td width="30">$o'.FOXC_VERSIONP.' '.FOXC_VERSION.'</td>');
			$window->content('<td width="15">Build:</td><td width="3"></td><td width="30">$o'.FOXC_BUILD.'</td>');
			
			$window->show($args[1]);
		}
		
		//Help
		else if($args[2] == $this->mlids[9]) {
			$chatPlayer = $this->getPluginInstance('chat_player');
			if($chatPlayer === false) {
				$this->chatToLogin($args[1], 'Plugin not activated!', 'f60');
			} else {
				$this->closeML($this->mlids[3], $args[1]);
				$this->closeML($this->mlids[2], $args[1]);
			
				$chatPlayer->onCommand(array(1 => $args[1], 2 => 'help'));
			}
		}
		
		//Open Fox Submenu
		else if($args[2] == $this->mlids[10]){
			$this->displayItems('Fox Support', $args[1]);
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
		
		$posn = $this->posn_button;
		
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
		$posn = $this->posn_menu;
		
		$id = 0;
		$id2 = 0;
		$y = $posn[1];
		
		if(isset($submenu)) {
			$explode = explode('.', $submenu);
		}
		
		if($submenu == false) {
			$mlid = $this->mlids[1];
			$x = $posn[0] - 0.4;
		}else if(isset($explode[1])) {
			$mlid = $this->mlids[3];
			$x = ($posn[0] - 27.6);
		} else {
			$mlid = $this->mlids[2];
			$x = ($posn[0] - 14);
		}
		
		$code = '';
		
		while(isset($this->menu_items[$id])) {			
			if($this->menu_items[$id]['submenu'] == $submenu) {
				if($rank[0] >= $this->menu_items[$id]['rank']) {					
					if(preg_match('/url:/', $this->menu_items[$id]['action'])){
						$link = 'url="'.str_replace('url:', '', $this->menu_items[$id]['action']).'"';
					}
					else if(preg_match('/ml:/', $this->menu_items[$id]['action'])) {
						$link = 'manialink="'.str_replace('ml:', '', $this->menu_items[$id]['action']).'"';
					}
					else{
						$link = 'action="'.$this->menu_items[$id]['action'].'"';
					}
					$code .= '
					<quad posn="'.$x.' '.$y.' 21" sizen="13 3.8" valign="top" style="'.$settings['default_style2'].'" '.$link.' substyle="'.$settings['default_substyle2'].'" />
					<label posn="'.($x +1).' '.($y-1.35).' 22" textsize="2" valign="top" text="$o$fff'.$this->menu_items[$id]['name'].'" '.$link.' scale="0.6"/>';
				
					$y -= 4;
					$id2++;
				}
			}
			$id++;
		}
		$code .= '<quad posn="'.($x - 0.5).' '.($posn[1]+0.5).' 20" sizen="14 '.(($id2 * 4) + 0.8).'" valign="top" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" />';
		
		if(!empty($login)){
			$this->displayManialinkToLogin($login, $code, $mlid);
		}else{
			$this->displayManialink($code, $mlid);
		}
	}
}
?>