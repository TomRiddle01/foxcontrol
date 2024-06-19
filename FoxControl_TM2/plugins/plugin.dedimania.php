<?php
//* plugin.dedimania.php - Dedimania
//* Version:   0.6
//* Coded by:  slig, matrix142
//* Copyright: FoxRace, http://www.fox-control.de

//Including required files
require_once('include/GbxRemote.response.php');
require_once('include/web_access.php');
require_once('include/xmlrpc_db_access.php');
require_once('include/xml_parser.php');


/*
	SORT DEDI RECORDS
*/
function compareDediArrays($timeArray1, $timeArray2){
	if($timeArray1["Best"] == $timeArray2["Best"]){
		return 0;
	}
	return (($timeArray1["Best"] > $timeArray2["Best"])?1:-1);
}


class plugin_dedimania extends FoxControlPlugin {
	public $pageLogin = array();

	var $_webaccess;        // webaccess object
	var $_xmlrpcdb;         // xmlrpcdb object
	var $_dm;               // several dedimania values
	var $_gamedatadir;      // store GameDataDirectory
	var $_sysinfo;          // store sysinfo
	var $_srvinfo;          // store server login player info
	var $_options;          // store serveroptions
	var $_gameinfo;         // store gameinfo
	var $_mapinfo;          // store map info
	var $_players;          // store players info
	var $_playersinfo;      // store players detailed info
	var $_map;              // values originally returned by dedimania.GetChallengeRecords method
	var $_keep_best_vreplay;// set to true to store best vreplay locally
	var $_config;
	var $dediArray;
	var $_max_Rank = 30;	// Set Records Max Rank here

	public function onStartUp() {
		global $settings, $posn_dedi;
		
		$this->name = 'Dedimania';
		$this->author = 'Slig & matrix142';
		$this->version = '0.6';
		
		$this->registerMLIds(1);
		$this->registerWidgets(1);
		$this->registerCommand('dedimania', 'Shows Dedimania record list', false);
	
		//console("DM.onStartUp:: settings ".print_r($settings,true));
		
		//Set dedimania settings
		$this->_webaccess = new Webaccess();
		$this->_xmlrpcdb = null;
		$this->_gamedatadir = '';
		$this->_sysinfo = null;
		$this->_srvinfo = null;
		$this->_options = null;
		$this->_gameinfo = null;
		$this->_players = null;
		$this->_playersinfo = array();
		$this->_config = $this->loadConfig();
		
		$this->_dm = array();
		$this->_dm['top1time'] = -1;  // keep original top1 time for current map

		$this->_keep_best_vreplay = false; // set to true to store best vreplay locally

		$this->_dm['Game'] = 'TM2';   // only TM2 is supported for Dedimania²
		$this->_dm['Tool'] = 'FoxControl';
		$this->_dm['ToolVersion'] = '0.5 DEV';
		
		//Get Posn
		$posn_dedi = $this->getPosn('dedi_widget');
		
		/*
		CHANGE HERE THE STYLE OF THE WIDGET
		*/
		if($posn_dedi != false) {
			$widget = $this->widget;
			$widget->init();
		
			$widget->title('Dedimania');
			$widget->posn(($posn_dedi[0]+1.8), $posn_dedi[1]);
			$widget->size(16, 25);
			$widget->fontSize('1');
			$widget->icon('Icons64x64_1', 'ToolLeague1');
			
			$widget->addCode('	<quad posn="0 -3 0" halign="center" sizen="16 7" style="{style2}" substyle="{substyle2}" />
								<quad posn="6.8 -22.2 0" halign="center" sizen="3 3" image="http://www.fox-control.de/fcimages/shutright.png" action="'.$this->mlids[0].'" />');
			
			$widget->alternativePosition('Left Top', ($posn_dedi[0] + 1.8), ($posn_dedi[1] + 15));
			$widget->alternativePosition('Left', ($posn_dedi[0] + 1.8), $posn_dedi[1]);
		
			$widget->saveWidget($this->widgetIDs[0], $this->mlids[0]);
		}
		
		$this->_dm['Code'] = ''.$this->_config->settings->general->dedi_code.'';
		$this->_dm['Url'] = $this->_config->settings->general->dedi_url;

		$this->instance()->client->query('GameDataDirectory');
		$this->_gamedatadir = $this->instance()->client->getResponse();

		$this->instance()->client->query('GetSystemInfo');
		$this->_sysinfo = $this->instance()->client->getResponse();

		$this->_dm['Login'] = strtolower($this->_sysinfo['ServerLogin']);
	
		$this->instance()->client->query('GetDetailedPlayerInfo', $this->_dm['Login']);
		$this->_srvinfo = $this->instance()->client->getResponse();
		$this->_dm['Path'] = $this->_srvinfo['Path'];
	
		$this->instance()->client->query('GetServerPackMask');
		$this->_dm['PackMask'] = $this->instance()->client->getResponse();
	
		$this->instance()->client->query('GetVersion');
		$version = $this->instance()->client->getResponse();

		$this->_dm['ServerVersion'] = $version['Version'];
		$this->_dm['ServerBuild'] = $version['Build'];
	
		$this->_dm['StartingUp'] = true;
	
		console(nz.'******** (Dedimania) ********');
		$this->dedi_connect();
		console('******** (Dedimania) ********'.nz);

		// get mapinfo at start, after we will get it in dedicated callbacks
		$this->instance()->client->query('GetCurrentChallengeInfo');
		$MapInfo = $this->instance()->client->getResponse();

		// simulate initial onPlayerConnect
		$this->instance()->client->query('GetPlayerList', 300, 0, 1);
		$this->_players = $this->instance()->client->getResponse();
		foreach($this->_players as $player){
			$this->instance()->client->query('GetDetailedPlayerInfo', $player['Login']);
			$playerinfo = $this->instance()->client->getResponse();
			
			$this->onPlayerConnect($playerinfo);
		}
		
		// simulate initial onBeginChallenge
		$this->onBeginChallenge(array($MapInfo,false,false));
	}
	

	/*
		BEGIN CHALLENGE
	*/
	public function onBeginChallenge($args) {
		$MapInfo = $args[0];
		$this->_mapinfo = $MapInfo;
		//console("DM.onBeginChallenge:: ChallengeInfo ".print_r($MapInfo,true));

		$this->_map = array();
		$this->_dm['top1time'] = -1;

		if(!isset($MapInfo['UId'])){
			// should never happen !
			return;
		}

		if(!isset($this->_xmlrpcdb) || $this->_xmlrpcdb === null || $this->_xmlrpcdb->isBad()){
			// Dedimania connection is not ok
			return;
		}

		// get current server options
		$this->instance()->client->query('GetServerOptions');
		$this->_options = $this->instance()->client->getResponse();

		// get current game info
		$this->instance()->client->query('GetCurrentGameInfo',1);
		$this->_gameinfo = $this->instance()->client->getResponse();

		// get current list of players and status
		$this->instance()->client->query('GetPlayerList', 300, 0, 1);
		$this->_players = $this->instance()->client->getResponse();
	
		if( ($gmode = $this->checkGameMode($this->_gameinfo,$MapInfo)) === false ){
			// unsupported gamemode
			return;
		}

		$players = $this->dedi_players();
		
		$serverInfo = $this->dedi_serverinfo();

		$callback = array(array($this,'cbGetChallengeRecords'),$MapInfo); 

		$mapinfo = array('UId'=>$MapInfo['UId'],
										 'Name'=>$MapInfo['Name'],
										 'Environment'=>$MapInfo['Environnement'],
										 'Author'=>$MapInfo['Author'],
										 'NbCheckpoints'=>$MapInfo['NbCheckpoints'],
										 'NbLaps'=>$MapInfo['NbLaps']);
		
		$this->_xmlrpcdb->addRequest($callback,'dedimania.GetChallengeRecords',$this->_dm['SessId'],$mapinfo,$gmode,$serverInfo,$players);

		//console("DM.onBeginChallenge:: send dedimania.GetChallengeRecords({$MapInfo['UId']},{$MapInfo['Name']})...");

		// send immediatly dedimania.GetChallengeRecords
		$this->_xmlrpcdb->sendRequests();
	}


	/*
		BEGIN CHALLENGE CB
	*/
	function cbGetChallengeRecords($response,$MapInfo){
		//console("DM.cbGetChallengeRecords::");

		if(!$this->checkCBerror($response,'DM.cbGetChallengeRecords'))
			return;

		$this->_dm['lastSent'] = time();
		$this->_dm['top1time'] = -1;
		$this->_map = $response['Data']['params'];
		
		// store top1 time
		if(isset($this->_map['Records'][0]['Best']))
			$this->_dm['top1time'] = $this->_map['Records'][0]['Best'];
		
		// get bestplayerschecks, make short name for records owners
		if(isset($this->_map['Records']) && is_array($this->_map['Records'])){

			foreach($this->_map['Records'] as $num => &$rec){
				if(!isset($rec['Game']))
					$rec['Game'] = 'TM2';
				
				if(is_string($rec['Checks']))
					$rec['Checks'] = explode(',',$rec['Checks']);
			}
		}

		// --------------------------------

		// Actual Dedimania records are in $this->_map['Records']

		// todo : show records in chat and/or manialink

		// todo if wanted : live update of records in  onPlayerFinish()

		//console("DM.cbGetChallengeRecords:: response: ".print_r($this->_map,true));
		
		// --------------------------------
		
		//Write records to array
		$this->writeDediArray();
		
		//Display Dedimania Widget
		$this->displayDediWidget();
	}


	/*
		END CHALLENGE
	*/
	public function onEndChallenge($args) {	
		$Ranking = $args[0];
		$MapInfo = $args[1];
		//console("DM.onEndChallenge:: ChallengeInfo: ".print_r($MapInfo,true)."\nRanking: ".print_r($Ranking,true));
		//console("DM.onEndChallenge({$MapInfo['UId']},{$MapInfo['Name']}) Ranking: ".print_r($Ranking,true));
	
		$this->closeMl($this->mlids[0]);
	
		if(!isset($MapInfo['UId'])){
			// should never happen !
			return;
		}

		if(!isset($this->_xmlrpcdb) || $this->_xmlrpcdb === null || $this->_xmlrpcdb->isBad()){
			// Dedimania connection is not ok
			return;
		}

		if( ($gmode = $this->checkGameMode($this->_gameinfo,$MapInfo)) === false ){
			// unsupported gamemode
			return;
		}

		if($MapInfo['AuthorTime'] < 5200){
			// dedimania will not store records under 5s
			return;
		}

		if($MapInfo['NbCheckpoints'] < 2){
			// dedimania will not store records without at least 1 checkpoint+finish (ie >=2)
			return;
		}

		$prankings = array();
		$ptimes = array();
		$withreplay = false;
		$replays = array('VReplay'=>'','Top1GReplay'=>'','VReplayChecks'=>'');
		
		// get best times of players (only ones with times > 0 and nb checks >= 2)
		foreach($Ranking as $key => $pranking){
			if(!$this->is_LAN_login($pranking['Login']) && $pranking['BestTime'] > 0){
				$login = $pranking['Login'];
				
				// add time only if no basic inconsistency
				// note: for Laps, 'Checks' should get best lap checkpoints times (starting from 0!)
				if(count($pranking['BestCheckpoints']) >= 2 && $pranking['BestTime'] == end($pranking['BestCheckpoints'])){
					$ptimes[] = array('Login'=>$login ,'Best'=>$pranking['BestTime']+0,'Checks'=>implode(',',$pranking['BestCheckpoints']));
					$prankings[$login] = $pranking; // keep direct access to player ranking for later use
				}
			}
		}

		if(count($ptimes) > 0){
			
			// sort ptimes (should not be useful in TA, but in other modes it is)
			usort($ptimes,array($this,'sortTimes'));
			
			// get replays of best player, skip first if validation replay is not ok
			$first_time_ok = false;
			while(!$first_time_ok && count($ptimes) > 0){

				$replays = array('VReplay'=>'','Top1GReplay'=>'','VReplayChecks'=>'');
				$login = $ptimes[0]['Login'];
				
				// --------------------------------------------------------------
				// get and check ValidationReplay for first time
				$vreplay = $this->getValidationReplay($ptimes[0],$prankings[$login],$this->_gameinfo,$MapInfo);
				if($vreplay === 'BADUID')
					return;
				else if($vreplay === false){
					array_shift($ptimes);
					continue;
				}
				
				// --------------------------------------------------------------
				// get and check GhostReplay for first time if better than old top1
				$greplay = $this->getGhostReplay($ptimes[0],$prankings[$login],$this->_gameinfo,$MapInfo);
				if($greplay === 'BADUID' || $greplay === 'FAILED')
					return;
				else if($greplay === false){
					array_shift($ptimes);
					continue;
				}else if($greplay !== true){
					// if true, then no need for greplay, else add it in data
					$replays['Top1GReplay'] = new IXR_Base64($greplay);
					$withreplay = true;
				}
				
				// --------------------------------------------------------------
				// add VR in datas to send
				$replays['VReplay'] = new IXR_Base64($vreplay);
			
				// If Laps mode then add also full race Checkpoints of vreplay
				if($this->_gameinfo['GameMode'] == 4){
					// $replays['VReplayChecks'] = implode(',',$players[$login]['BestCheckpoints']);
				}
				
				// --------------------------------------------------------------
				// store VR locally if asked
				if($this->_keep_best_vreplay){
					if(!file_exists('VReplays'))
						@mkdir('VReplays');
					$vrfile = sprintf('VReplays/vreplay.%s.%d.%07d.%s.Replay.Gbx',$MapInfo['UId'],$this->_gameinfo['GameMode'],$ptimes[0]['Best'],$login);
					@file_put_contents($vrfile,$vreplay);
					console(nz."DM.onEndChallenge:: ValidationReplay {$vrfile} stored ({$login},{$ptimes[0]['Best']}) !".nz);
				}

				// --------------------------------------------------------------
				// all was ok
				$first_time_ok = true;
			}
		}
		
		// send times etc.
		$callback = array(array($this,'cbSetChallengeTimes'),$MapInfo); 

		$map_info = array('UId'=>$MapInfo['UId'],
											'Name'=>$MapInfo['Name'],
											'Environment'=>$MapInfo['Environnement'],
											'Author'=>$MapInfo['Author'],
											'NbCheckpoints'=>$MapInfo['NbCheckpoints'],
											'NbLaps'=>$MapInfo['NbLaps']);
		
		$this->_xmlrpcdb->addRequest($callback,'dedimania.SetChallengeTimes',$this->_dm['SessId'],$map_info,$gmode,$ptimes,$replays);

		console(nz."DM.onEndChallenge:: send dedimania.GetChallengeRecords({$MapInfo['UId']},{$MapInfo['Name']})...".nz);

		// send immediatly dedimania.SetChallengeTimes
		$this->_xmlrpcdb->sendRequests();
	}


	/*
		END CHALLENGE CB
	*/
	function cbSetChallengeTimes($response,$MapInfo){
		//console("DM.cbSetChallengeTimes::");

		if(!$this->checkCBerror($response,'DM.cbSetChallengeTimes'))
			return;

		$this->_dm['lastSent'] = time();

		// --------------------------------

		// todo eventually: show updated records

		//console("DM.cbSetChallengeTimes:: response ".print_r($response['Data']['params'],true));
		
		// --------------------------------
	}

	
	/*
		PLAYER CONNECT
	*/
	function onPlayerConnect($pinfo){
		if(!isset($pinfo['Login'])){
			// should never happen !
			return;
		}
		$login = $pinfo['Login'];
		$this->_playersinfo[$login] = $pinfo;

		//console("DM.onPlayerConnect({$login}):: ");

		$callback = array(array($this,'cbPlayerConnect'),$pinfo); 

		$this->_xmlrpcdb->addRequest($callback,'dedimania.PlayerConnect',$this->_dm['SessId'],$login,$pinfo['NickName'],$pinfo['Path'],$pinfo['IsSpectator']);

		console(nz."DM.onPlayerConnect({$login}):: will send dedimania.PlayerConnect...".nz);
	}


	/*
		PLAYER CONNECT CB
	*/
	function cbPlayerConnect($response,$pinfo){
		$login = $pinfo['Login'];
		//console("DM.cbPlayerConnect({$login})::");

		if(!$this->checkCBerror($response,"DM.cbPlayerConnect({$login})"))
			return;

		$this->_playersinfo[$login]['DM'] = $response['Data']['params'];

		// --------------------------------

		// todo if used by Tool to allow global customization for player: 
		//   get $this->_playersinfo[$login]['DM']['Options']
		// (string stored in onPlayerDisconnect for player)

		//console("DM.cbPlayerConnect({$login}):: pinfo ".print_r($this->_playersinfo[$login],true));
		
		//console("DM.cbPlayerConnect({$login}):: pinfo['DM'] ".print_r($this->_playersinfo[$login]['DM'],true));
		
		// --------------------------------
		
		$this->displayDediWidget($login);
	}


	/*
		PLAYER DISCONNECT
	*/
	function onPlayerDisconnect($args){
		$login = $args[0];
		console("DM.onPlayerDisconnect({$login}):: ");

		$callback = array(array($this,'cbPlayerDisconnect'),$login); 

		if(isset($this->_playersinfo[$login]['DM']['OptionsEnabled']) && $this->_playersinfo[$login]['DM']['OptionsEnabled']){

			// todo: may store player customisation for Tool, if used and have changed (string)
			$option = '';

		}else
			$option = '';

		$this->_xmlrpcdb->addRequest($callback,'dedimania.PlayerDisconnect',$this->_dm['SessId'],$login,$option);

		console(nz."DM.onPlayerDisconnect({$login}):: will send dedimania.PlayerDisconnect...".nz);
	}


	/*
		PLAYER DISCONNECT CB
	*/
	function cbPlayerDisconnect($response,$login){
		//console("DM.cbPlayerDisconnect({$login})::");

		if(!$this->checkCBerror($response,"DM.cbPlayerDisconnect({$login})"))
			return;

		//console("DM.cbPlayerConnect({$login}):: response ".print_r($response['Data']['params'],true));
	}

	
	/*
		WRITE DEDI ARRAY
	*/
	function writeDediArray(){
		$this->dediArray = array();
	
		foreach($this->_map['Records'] as $key => $value) {
			if($key < $this->_max_Rank) {
				$this->dediArray[] = array('Login' => $this->_map['Records'][$key]['Login'], 'NickName' => $this->_map['Records'][$key]['NickName'], 'Best' => $this->_map['Records'][$key]['Best']);
			}
		}
	}
	
	
	/*
		PLAYER FINISH
	*/
	public function onPlayerFinish($PlayerFinish) {
		global $posn_dedi;
	
		if(trim($PlayerFinish[2] > 0)) {		
			if($posn_dedi != false) {
				//Get general information
				$this->instance()->client->query('GetDetailedPlayerInfo', $PlayerFinish[1]);
				$playerInfo = $this->instance()->client->getResponse();
			
				$color_newdedi = $this->_config->settings->color->color_newdedi;
				
				//Check if player already has a dedi record
				$in_array = false;
				
				foreach($this->dediArray as $key => $value) {
					if(in_array($PlayerFinish[1], $value)) {
						$in_array = true;
						break;
					} else {
						$in_array = false;
					}
				}
				
				//If player already has a record...
				if($in_array == true) {
					$id = 0;
					while(isset($this->dediArray[$id])) {
						if($this->dediArray[$id]['Login'] == $PlayerFinish[1]) {
							if($PlayerFinish[2] < $this->dediArray[$id]['Best']) {
								$time2 = $this->instance()->format_time($this->dediArray[$id]['Best'] - $PlayerFinish[2]);
								
								//Sort Dedi Records Array
								$this->dediArray[$id]['Best'] = $PlayerFinish[2];
								uasort($this->dediArray, 'compareDediArrays');
								$this->dediArray = $this->sortDediArray($this->dediArray);
								
								//Get new ID
								foreach($this->dediArray as $key => $value) {
									if($PlayerFinish[1] == $value['Login']) {
										$rank = $key + 1;
										break;
									}
								}
								
								$time = $this->instance()->format_time($PlayerFinish[2]);
								
								$this->chat($playerInfo['NickName'].'$z$s '.$color_newdedi.'claimed the $fff'.$rank.'. '.$color_newdedi.'Dedimania record! Time: $fff'.$time.'$z $s$n$fff(- '.$time2.')', $color_newdedi);
							
								$this->displayDediWidget();
							}							
							break;
						}
						$id++;
					}
				//If player has no record...
				} else {
					if(isset($this->dediArray[$this->_max_Rank - 1]) && $PlayerFinish[2] > $this->dediArray[$this->_max_Rank - 1]['Best']) {
						return;
					}
					
					$this->dediArray[] = array('Login' => $PlayerFinish[1], 'NickName' => $playerInfo['NickName'], 'Best' => $PlayerFinish[2]);
					uasort($this->dediArray, 'compareDediArrays');
					$this->dediArray = $this->sortDediArray($this->dediArray);
					
					$time = $this->instance()->format_time($PlayerFinish[2]);
					
					foreach($this->dediArray as $key => $value) {
						if($PlayerFinish[1] == $value['Login']) {
							$rank = $key + 1;
							break;
						}
					}
					
					$this->chat($playerInfo['NickName'].'$z$s '.$color_newdedi.'claimed the $fff'.$rank.'. '.$color_newdedi.'Dedimania record! Time: $fff'.$time, $color_newdedi);
				
					$this->displayDediWidget();
				}
			}
		}
	}
	
	
	/*
		ON COMMAND
	*/
	public function onCommand($args) {
		if($args[2] == 'dedimania') {
			$this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
		}
	}
	
	
	/*
		ON PAGES (WINDOW CLASS)
	*/
	public function onPages($args) {
		if($args[2] == 1) $this->pageLogin[$args[1]] = 0; // <<
		elseif($args[2] == 2 && $this->pageLogin[$args[1]] > 0) $this->pageLogin[$args[1]]--; // <
		elseif($args[2] == 6) $this->pageLogin[$args[1]]++; // >
		elseif($args[2] == 7) $this->pageLogin[$args[1]] = floor(count($this->dediArray) / 25);
		
		$this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
	}
	
	
	/*
		MANIALINK PAGE ANSWER
	*/
	public function onManialinkPageAnswer($args) {
		if($args[2] == $this->mlids[0]) {
			if(!isset($this->pageLogin[$args[1]])) $this->pageLogin[$args[1]] = 0;
			$currentID = $this->pageLogin[$args[1]] * 25;
			
			//Create Window
			$window = $this->window;
			$window->init();
			
			$window->title('Dedimania Records');
			
			$window->displayAsTable(true);
			$window->size(70, '');
			$window->posY(40);
			$window->target('onPages', $this);
			
			//DISPLAY PREV PAGE BUTTON
			if(isset($this->dediArray[$currentID - 25])) {
				$window->addButton('<<<', '7', false);
				$window->addButton('<', '7', false);
			} else {
				$window->addButton('', '7', false);
				$window->addButton('', '7', false);
			}
			
			$window->addButton('', '15.5', false);
			$window->addButton('Close', '10', true);
			$window->addButton('', '15.5', false);
		
			//DISPLAY NEXT PAGE BUTTON
			if(isset($this->dediArray[$currentID + 25])){
				$window->addButton('>', '7', false);
				$window->addButton('>>>', '7', false);
			}else {
				$window->addButton('', '7', false);
				$window->addButton('', '7', false);
			}
			
			$window->content('<td width="5">$iRank</td><td width="12">$iTime</td><td width="25">$iNickName</td><td width="15">$iLogin</td>');
			
			for($i=0; isset($this->dediArray[$currentID]) && $i<=24; $i++) {
				$window->content('<td width="5">$o$09f'.($currentID + 1).'</td><td width="12">'.$this->instance()->format_time($this->dediArray[$currentID]['Best']).'</td><td width="25">'.htmlspecialchars($this->dediArray[$currentID]['NickName']).'</td><td width="15">'.$this->dediArray[$currentID]['Login'].'</td>');
			
				$currentID++;
			}
			
			$window->show($args[1]);
		}
	}	
	
	
	/*
		DISPLAY DEDIMANIA WIDGET
	*/
	function displayDediWidget($login = ''){
		global $posn_dedi;
		
		//Check if current GameMode is supported
		if(($gmode = $this->checkGameMode($this->_gameinfo,$this->_mapinfo)) === false ) {
			return;
		}
		
		//Check if widget is enabled
		if($posn_dedi == false) {
			return;
		}
		
		$this->instance()->client->query('GetPlayerList', 200, 0);
		$players = $this->instance()->client->getResponse();
		
		$widget = $this->widget;
		
		//display the widget for every player on the server individualy
		foreach($players as $player) {
			$widget->clearContent($player['Login'], $this->widgetIDs[0]);
		
			$x = $posn_dedi[0] - 4.1;
			$y = $posn_dedi[1] - 4;
			$z = 3;
			
			//Player Bar
			$playerBar = 0;
			$playerBar2 = 0;
			
			//get the rank of the player
			$rank = null;
			if(!empty($this->dediArray)) {
				foreach($this->dediArray as $key => $value) {
					if($value['Login'] == $player['Login']) {
						$rank = $key + 1;
						break;
					}
				}
				
				//set the start id of the recods
				if($rank === null || $rank > count($this->dediArray) - 2 || $rank < 4) {
					$startId = count($this->dediArray) - 6;
				} else {
					$startId = $rank - 3;
				}
				$startId -= 1;
				if($startId < 3) {
					$startId = 3;
				}
				
				for($i=0; isset($this->dediArray[$i]) && $i<3; $i++) {
					if($this->dediArray[$i]['Login'] == $player['Login']) {
						$playerBar = $i + 1;
					}
				
					if($i == 0) $rank_icon = 'First';
					else if($i == 1) $rank_icon = 'Second';
					else if($i == 2) $rank_icon = 'Third';
					
					if(preg_match('/$s/', $this->dediArray[$i]['NickName'])) $nickname = $this->dediArray[$i]['NickName'];
					else $nickname = '$s'.$this->dediArray[$i]['NickName'];
					
					$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.($i + 1).'</td><td width="4.5">'.$this->instance()->format_time($this->dediArray[$i]['Best']).'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $player['Login'], $this->widgetIDs[0]);
				}
				
				//individually records
				$indRecs = array();
				if(isset($this->dediArray[$startId])) {
					//first individually record
					$indRecs[$startId] = $this->dediArray[$startId];
				}
				if(isset($this->dediArray[$startId + 1])) {
					//second individually record
					$indRecs[$startId + 1] = $this->dediArray[$startId + 1];
				}
				if(isset($this->dediArray[$startId + 2])) {
					//second individually record
					$indRecs[$startId + 2] = $this->dediArray[$startId + 2];
				}
				if(isset($this->dediArray[$startId + 3])) {
					//second individually record
					$indRecs[$startId + 3] = $this->dediArray[$startId + 3];
				}
				if(isset($this->dediArray[$startId + 4])) {
					//second individually record
					$indRecs[$startId + 4] = $this->dediArray[$startId + 4];
				}
				if(isset($this->dediArray[count($this->dediArray) - 2]) && count($this->dediArray) > 8) {
					//second last record
					$indRecs[count($this->dediArray) - 2] = $this->dediArray[count($this->dediArray) - 2];
				}
				if(isset($this->dediArray[count($this->dediArray) - 1]) && count($this->dediArray) > 8) {
					//last record
					$indRecs[count($this->dediArray) - 1] = $this->dediArray[count($this->dediArray) - 1];
				}
				
				//create code for the individually records
				$i = 0;
				foreach($indRecs as $key => $value) {
					if($this->dediArray[$key]['Login'] == $player['Login']) {
						$playerBar2 = $i + 4;
					}
					
					if(preg_match('/$s/', $this->dediArray[$key]['NickName'])) $nickname = $this->dediArray[$key]['NickName'];
					else $nickname = '$s'.$this->dediArray[$key]['NickName'];
				
					$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.($key + 1).'</td><td width="4.5">'.$this->instance()->format_time($this->dediArray[$key]['Best']).'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $player['Login'], $this->widgetIDs[0]);
					
					$i++;
				}
				
				$widget->addCodeToLogin(false, $player['Login'], $this->widgetIDs[0]);
				
				//GET PLAYER BEST BAR POSITION
				if($playerBar <= 3 && $playerBar != 0 && $playerBar2 == 0) {
					$pb_y = 2 * ($playerBar) + 1.7;
					
					$playerBarCode = '<quad posn="-8.5 '.(-$pb_y).' 2" sizen="17.5 2" style="BgsPlayerCard" substyle="BgRacePlayerName"/><quad posn="7.5 '.(-$pb_y).' 3" sizen="2 2" style="Icons64x64_1" substyle="ShowLeft2"/>';
					$widget->addCodeToLogin($playerBarCode, $player['Login'], $this->widgetIDs[0]);
				} else if($playerBar2 != 0 && $playerBar == 0) {
					$pb_y = 2 * ($playerBar2) + 1.7;
				
					$playerBarCode = '<quad posn="-8.5 '.(-$pb_y).' 2" sizen="17.5 2" style="BgsPlayerCard" substyle="BgRacePlayerName"/><quad posn="7.5 '.(-$pb_y).' 3" sizen="2 2" style="Icons64x64_1" substyle="ShowLeft2"/>';
					$widget->addCodeToLogin($playerBarCode, $player['Login'], $this->widgetIDs[0]);
				}
			}
			
			$widget->displayWidget($player['Login'], $this->mlids[0], $this->widgetIDs[0]);
		}
	}
	
	
	/*
		dedimania.UpdateServerPlayers, should be called every 3/4 minutes
	 */
	function updateServerPlayers(){
		$MapInfo = $this->_mapinfo;

		if( ($gmode = $this->checkGameMode($this->_gameinfo,$MapInfo)) === false ){
			// unsupported gamemode
			return;
		}

		// get current list of players and status...  would be better to update, btw not so important
		//$this->instance()->client->query('GetPlayerList', 300, 0, 1);
		//$this->_players = $this->instance()->client->getResponse();

		$callback = array(array($this,'cbUpdateServerPlayers'),$MapInfo); 

		$srv_info = array('UId'=>$MapInfo['UId'],'GameMode'=>$gmode);
		
		$votes = array();

		$players = $this->dedi_players(true);

		$this->_xmlrpcdb->addRequest($callback,'dedimania.UpdateServerPlayers',$this->_dm['SessId'],$srv_info,$votes,$players);

		console(nz."DM.updateServerPlayers:: will send dedimania.UpdateServerPlayers({$MapInfo['UId']},{$MapInfo['Name']})...".nz);

		// send immediatly dedimania.GetChallengeRecords
		$this->_xmlrpcdb->sendRequests();

		$this->_dm['lastSent'] = time();
	}


	/*
		dedimania.UpdateServerPlayers CB
	*/
	function cbUpdateServerPlayers($response,$MapInfo){
		//console("DM.cbUpdateServerPlayers:: ({$MapInfo['UId']})");

		if(!$this->checkCBerror($response,'DM.cbUpdateServerPlayers'))
			return;

		$this->_dm['lastSent'] = time();
	}

	
	/*
		Every second
	 */
	public function onEverySecond(){

		// 'dedimania.UpdateServerPlayers'  should be called every 3/4 minutes
		if( time() - $this->_dm['lastSent'] > 210 )
			$this->updateServerPlayers();

		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// if $this->_xmlrpcdb->isBad() for more than 20 to 30 minutes then call $this->_xmlrpcdb->retry();
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


		// send not urgent pending requests every 8s
		if( (time() % 8) == 0 )
			$this->_xmlrpcdb->sendRequests();
	}


	/*
		Every tick
	 */
	public function onTick(){
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// todo: rename the function name by the real one 
		$read = null;
		$write = null;
		$except = null;
		$this->_webaccess->select($read, $write, $except, 0);
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	}


	/*
		get and check validation replay
		return validationreplay, or 'BADUID' if mismatch map uid, or false if player vreplay is not ok
	*/
	function getValidationReplay($ptime,$pranking,$GameInfo,$MapInfo){
		$login = $ptime['Login'];

		if(!$this->instance()->client->query('GetValidationReplay', $login)){
			console(nz."DM.getValidationReplay:: failed to get ValidationReplay of {$login}, skip {$login} time !".nz);
			return false;
		}
		$vreplay = $this->instance()->client->getResponse();

		// --------------------------------------------------------------
		// sanity checks: checks against vreplay values (optionnal but better)
		$vrdata = $this->getGbxXmlData($vreplay);
					
		//console("DM.getValidationReplay:: vreplay data: ".print_r($vrdata,true));
			
		if(!$vrdata || !isset($vrdata['header']['.attr.map']['uid'])){
			console(nz."DM.getValidationReplay:: failed to parse VReplay, skip {$login} time !".nz);
			return false;
		}
			
		// checks VR uid
		if($vrdata['header']['.attr.map']['uid'] != $MapInfo['UId']){
			console(nz."DM.getValidationReplay:: VReplay UId not same as current ! ({$vrdata['header']['.attr.map']['uid']},{$MapInfo['UId']}), skip {$login} time !".nz);
			// if vreplay has bad uid then no reason to think that next will ok : send nothing
			return 'BADUID';
		}
			
		// values to checks VR time and number of checks
		$racetime = end($pranking['BestCheckpoints']);
		$racenbchecks = count($pranking['BestCheckpoints']);

		// Note: if Laps, then should check VR time against the full race time and not the best lap time !!!

		// checks VR time
		if($vrdata['header']['.attr.times']['best'] != $racetime){
			console(nz."DM.getValidationReplay:: VReplay time not same as current ! ({$vrdata['header']['.attr.times']['best']},{$racetime}), skip {$login} time !".nz);
			// skip first time then text next first
			return false;
		}
					
		// checks VR nb of checkps
		if($vrdata['header']['.attr.checkpoints']['cur'] != $racenbchecks){
			console(nz."DM.getValidationReplay:: VReplay checks not same as current ! ({$vrdata['header']['.attr.checkpoints']['cur']},{$racenbchecks}), skip {$login} time ".nz);
			// skip first time then text next first
			return false;
		}

		return $vreplay;
	}


	/*
		get and check ghost replay
		return ghostreplay, or 'BADUID' if mismatch map uid, or 'FAILED' is failed to store or read it, or false if player greplay is not ok
	*/
	function getGhostReplay($ptime,$pranking,$GameInfo,$MapInfo){
		$login = $ptime['Login'];

		// if supposed top1, then add GR in datas to send 
		if($this->_dm['top1time'] <= 0 || $ptime['Best'] < $this->_dm['top1time']){
			// if better than old top1, then get and send ghost replay of new one
						
			$rfile = sprintf('replay.%s.%d.%07d.%s.Replay.Gbx',$MapInfo['UId'],$GameInfo['GameMode'],$ptime['Best'],$login);
			if(!$this->instance()->client->query('SaveBestGhostsReplay', $login, 'dm.top1/'.$rfile)){
				console(nz."DM.getGhostReplay:: failed to store GhostReplay for {$login},{$ptime['Best']} !".nz);
				// skip first time then test next first
				return 'FAILED';
			}
						
			// read top1 replay file then add it in data to send
			$frfile = $this->_gamedatadir."Replays/dm.top1/{$rfile}";
			console(nz."DM.getGhostReplay:: GhostReplay dm.top1/{$rfile} stored... try get {$frfile} !".nz);
			if($greplay = file_get_contents($frfile)){
				// theorically here the ghost replay xml should be tested like for the validation replay
				return $greplay;
					
			}else{
				console(nz."DM.getGhostReplay:: failed to read {$frfile} for {$login},{$ptimes[0]['Best']}, probably bad character in path, bad file permissions, or tried to use the script remotely ?!".nz);
				// if can't read the GhostReaply file then no reason to think that next will be able to : send nothing
				return 'FAILED';
			}
		}
		return true;
	}


	/*
		check and return gamemode value for dedimania, false if unsupported
	*/
	public function checkGameMode($GameInfo,$MapInfo){
		// GameMode handling (TM2 values)
		$gmode = 'TA';
		if($GameInfo['GameMode'] == 0 || $GameInfo['GameMode'] == 6){ // Script & Stunts
			// unsupported modes
			return false;

		}else if($GameInfo['GameMode'] == 3){ // Team
			// Team mode can be supported only if checkpoints and times have been stored,
			// because these are not in Ranking !
			$gmode = 'Rounds';
			return false;

		}else if($GameInfo['GameMode'] == 4){ // Laps
			// Laps mode can be supported as TA for best lap, so need to handle to check plain lap times,
			// relative checkpoints times, but also send all checkpoints times with the validation replay
			// note: several things has to be adapted in the end of that function to handle it.
			$gmode = 'TA';
			return false;

		}else if($GameInfo['GameMode'] == 1){  // Rounds
			if($MapInfo['LapRace'] && $GameInfo['RoundsForcedLaps'] > 0 && $GameInfo['RoundsForcedLaps'] != $MapInfo['NbLaps']){
				// Rounds mode with ForcedLaps is not supported
				return false;
			}
			$gmode = 'Rounds';
		}
		return $gmode;
	}


	/*
		sort times array
	*/
	function sortTimes($a,$b){
		// best a better than best b
		if($a['Best'] < $b['Best'])
			return -1;
		// best b better than best a
		else if($a['Best'] > $b['Best'])
			return 1;
		// same best, use rank
		else
			return ($a['Rank'] < $b['Rank']) ?  -1 : 1;
	}
	
	
	/*
		SORT ARRAY
	*/
	function sortDediArray($array) {
		//Sort the local records array after adding a new record to it or updating it
		$id = 0;
		foreach($array as $value){
			if($id < $this->_max_Rank) {
				$newID[] = $value;
			} else {
				break;
			}
			$id++;
		}
		$array = $newID;
		
		return $array;
	}
	
	
	/*
		SERVER INFO
	*/
	public function dedi_serverinfo() {
		$numplayers = 0;
		$numspecs = 0;
	
		$this->instance()->client->query('GetPlayerList', 300, 0, 1);
		$this->_players = $this->instance()->client->getResponse();
	
		$numplayers = 0;
		$numspecs = 0;
		foreach($this->_players as $player){
			$IsSpectator = (($player['SpectatorStatus'] % 10) > 0) || ((floor($player['SpectatorStatus']/100) % 10) > 0);
			if($IsSpectator)
				$numspecs++;
			else
				$numplayers++;
		}

		$serverinfo = array('SrvName' => $this->_options['Name'],
												'Comment' => $this->_options['Comment'],
												'Private' => ($this->_options['Password'] != ''),
												'NumPlayers' => $numplayers,
												'MaxPlayers' => $this->_options['CurrentMaxPlayers'],
												'NumSpecs' => $numspecs,
												'MaxSpecs' => $this->_options['CurrentMaxSpectators']);
	
		return $serverinfo;
	}


	/*
		PLAYERS
	*/
	public function dedi_players($withvotes=false) {
		$players = array();
	
		foreach($this->_players as $player){
			$IsSpectator = (($player['SpectatorStatus'] % 10) > 0) || ((floor($player['SpectatorStatus']/100) % 10) > 0);
			if($withvotes)
				$players[] = array('Login'=>$player['Login'],'IsSpec'=>$IsSpectator,'Vote'=>-1);
			else
				$players[] = array('Login'=>$player['Login'],'IsSpec'=>$IsSpectator);
		}
	
		return $players;
	}

	
	/*
		PLAYER INFO
	*/
	public function dedi_playerinfo($player){
		// update or get the info of a player 
		$login = $player['Login'];

		if(!isset($this->_playersinfo[$login]['Path']) || $this->_playersinfo[$login]['Path'] == ''){
			// new or incomplete
			$this->instance()->client->query('GetDetailedPlayerInfo', $player['Login']);
			$this->_playersinfo[$login] = $this->instance()->client->getResponse();

		}else{
			// just update info
			$IsSpectator = (($player['SpectatorStatus'] % 10) > 0) || ((floor($player['SpectatorStatus']/100) % 10) > 0);
			$this->_playersinfo[$login]['NickName'] = $player['NickName'];
			$this->_playersinfo[$login]['PlayerId'] = $player['PlayerId'];
			$this->_playersinfo[$login]['TeamId'] = $player['TeamId'];
			$this->_playersinfo[$login]['IsSpectator'] = $IsSpectator;
		}

		return $this->_playersinfo;
	}


	/*
		check dedimania CB error
	*/
	function checkCBerror($response,$funcname=''){
		// show dedimania warning
		if(isset($response['Data']['errors']) && is_string($response['Data']['errors']) && strlen($response['Data']['errors']) > 0) 
			console(nz."DM.checkCBerror/{$funcname}:: Webaccess warnings: ",$response['Data']['errors'].nz);

		// if response value then "ok" (else it was an error, not a warning)
		if(isset($response['Data']['params']))
			return true;

		// show webaccess connection error
		if(isset($response['Error']) && is_string($response['Error']) && strlen($response['Error']) > 0)
			console(nz."DM.checkCBerror/{$funcname}:: Webaccess connection error: ",$response['Error'].nz);

		//print_r($response);
		return false;
	}
	

	/*
	CONNECT TO DEDIMANIA
	*/
	function dedi_connect(){
		global $settings;
	
		$time = time();
	
		if(!isset($this->_xmlrpcdb)) {
			console('* Try connection on '.$this->_dm['Url'].' ...');

			$xmlrpcdb = new XmlrpcDB($this->_webaccess,$this->_dm['Url']);		

			$this->instance()->client->query('GetServerName');
			$this->_dm['ServerName'] = $this->instance()->client->getResponse();
			
			$this->instance()->client->query('GetServerComment');
			$this->_dm['ServerComment'] = $this->instance()->client->getResponse();

			$srvconnect = array('Game' => $this->_dm['Game'], 'Login' => $this->_dm['Login'], 'Code' => $this->_dm['Code'], 
													'Path' => $this->_dm['Path'], 'Packmask' => $this->_dm['PackMask'],
													'ServerVersion' => $this->_dm['ServerVersion'], 'ServerBuild' => $this->_dm['ServerBuild'],
													'Tool' => $this->_dm['Tool'], 'Version' => $this->_dm['ToolVersion']);

			$response = $xmlrpcdb->RequestWait('dedimania.OpenSession', $srvconnect);

			$this->_dm['lastSent'] = time();
		
			if($response===false){
				die('!!!!!! Error bad database response !\n  !!!!!!');
			}
			elseif(isset($response['Data']['params']['SessionId']) && $response['Data']['params']['SessionId'] != ''){		
				$this->_xmlrpcdb = $xmlrpcdb;
				$this->_dm['SessId'] = $response['Data']['params']['SessionId'];
		
				console('Connection and status ok !');
		
				if(isset($response['Data']['errors']) && is_string($response['Data']['errors']) && strlen($response['Data']['errors']) > 0) 
					console("!!!!!! ... with some authenticate warning: ",$response['Data']['errors']);
			}
			elseif(isset($response['Data']['errors'])){	
				console("!!!!!! Connection Error !!!!!! \n".$response['Data']['errors']."\n  !!!!!!");	
			}
			elseif(!isset($response['Code'])){
				console("!!!!!! Error no database response (".$url.")\n  !!!!!!");	
			}
			else{
				console("!!!!!! Error bad database response or contents (".$response['Code'].",".$response['Reason'].")\n  !!!!!!");
			}
		}else {
			return;
		}
		
		$this->_dm['XmlrpcDBbadTime'] = $time;
	}


	/*
		Test if a login is a LAN one, ie finishing with  _xxx.xxx.xxx.xxx_xxx)
	*/
	function is_LAN_login($login){
		$num = '(25[0-5]|2[0-4]\d|[01]?\d\d|\d)';
		if(preg_match("/(_{$num}\\.{$num}\\.{$num}\\.{$num}_\d+)$/", $login) > 0)
			return true;
		return false;
	}
	
	
	/*
		get data from xml of replay gbx
	*/
	function getGbxXmlData($rawgbx){
		$datas = false;
		$startxml = strpos($rawgbx,'<header type=');
		$endxml = strpos($rawgbx,'</header>');
		if($startxml !== false && $endxml !== false && $startxml < $endxml){
			$xml = substr($rawgbx,$startxml,$endxml + 9 - $startxml);
			//console("gbxxml: $xml");
			$datas = xml_parse_string($xml);
			//console("gbxdata: ".print_r($datas,true));
		}
		return $datas;
	}

}
?>