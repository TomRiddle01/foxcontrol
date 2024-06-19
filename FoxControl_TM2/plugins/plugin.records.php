<?php
//* plugin.records.php - Records
//* Version:   0.6
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de
	
//Sort Local Records array after adding new record to it or updating it
function compareTimeArrays($timeArray1, $timeArray2){
	if($timeArray1["time"] == $timeArray2["time"]){
		return 0;
	}
	return (($timeArray1["time"] > $timeArray2["time"])?1:-1);
}
	
class plugin_records extends FoxControlPlugin {
	public $maxRecs = 50;
	public $recsArray;
	public $config;
	public $recsListLogin = array();
	public $liveListLogin = array();
	public $gameMode;
	public $liveRankings = array();
	
	public function onStartUp() {		
		$this->name = 'Records Plugin';
		$this->author = 'matrix142';
		$this->version = '0.6';
		
		$this->registerCommand('records', 'Shows the Local record list', false);
		$this->registerMLIds(30);
		$this->registerWidgets(2);
		
		$this->gameMode = $this->instance()->getGameMode();
		
		$this->createWidgetCodes();
		$this->writeLocals();
		
		$this->refreshLocalWidgets();
		$this->refreshLiveWidgets();
	}
	
	public function createWidgetCodes() {
		global $settings, $posn_local, $posn_live;
	
		//Load config file (plugin.records.config.xml)
		$this->config = $this->loadConfig();
		//Get posns of local record widget
		$posn_local = $this->getPosn('local_recs');
		//Get psosn of live rankings widget
		$posn_live = $this->getPosn('live_ranks');
		
		if($posn_local != false) {
			$widget = $this->widget;
			$widget->init();
		
			$widget->title('Local Records');
			$widget->posn(($posn_local[0]+1.8), $posn_local[1]);
			$widget->size(16, 30);
			$widget->fontSize('1');
			$widget->icon('Icons128x128_1', 'Multiplayer');
			
			$widget->addCode('	<quad posn="0 -3 0" halign="center" sizen="16 7" style="{style2}" substyle="{substyle2}" />
								<quad posn="6.8 -27.2 0" halign="center" sizen="3 3" image="http://www.fox-control.de/fcimages/shutright.png" action="'.$this->mlids[0].'" />');
			
			$widget->alternativePosition('Left Top', ($posn_local[0] + 1.8), ($posn_local[1] + 15));
			$widget->alternativePosition('Left', ($posn_local[0] + 1.8), $posn_local[1]);
		
			$widget->saveWidget($this->widgetIDs[0], $this->mlids[0]);
		}
		
		if($posn_live != false) {
			$widget = $this->widget;
			$widget->init();
		
			$widget->title('Live Rankings');
			
			$widget->posn(($posn_live[0]-1.8), $posn_live[1]);
			$widget->size(16, 35);
			
			$widget->fontSize('1');
			$widget->icon('Icons128x128_1', 'Buddies');
		
			$widget->addCode('	<quad posn="0 -3 0" halign="center" sizen="16 7" style="{style2}" substyle="{substyle2}" />
								<quad posn="-6.8 -32.2 0" halign="center" sizen="3 3" image="http://www.fox-control.de/fcimages/shutleft.png" action="'.$this->mlids[1].'" />');
		
			$widget->alternativePosition('Right Bottom', ($posn_live[0]-1.8), ($posn_live[1] - 10));
			$widget->alternativePosition('Right', ($posn_live[0]-1.8), $posn_live[1]);
		
			$widget->saveWidget($this->widgetIDs[1], $this->mlids[1]);
		}
	}
	
	public function onPlayerConnect($args) {
		//Display records to connected player
		$this->refreshLiveWidgets();
		$this->refreshLocalWidgets();
	}
	
	public function onEndChallenge($args) {		
		//Close widgets
		$this->closeMl($this->mlids[0]);
		$this->closeMl($this->mlids[1]);
		
		$this->liveRankings = array();
	}
	
	public function onBeginChallenge($args) {	
		$gameMode = $this->instance()->getGameMode();
		
		if($gameMode != $this->gameMode) {
			$this->gameMode = $gameMode;
			
			$this->createWidgetCodes();
		}
		
		$this->writeLocals();
		$this->refreshLiveWidgets();
		$this->refreshLocalWidgets();
	}
	
	public function writeLocals($return = false) {
		//Writing the local records to the global array
		$this->recsArray = array();
		
		$this->instance()->client->query("GetCurrentChallengeInfo");
		$challengeinfo = $this->instance()->client->getResponse();
		
		$sql = "SELECT * FROM `records` WHERE challengeid = '".$challengeinfo['UId']."' ORDER BY time ASC LIMIT ".$this->maxRecs."";
		if($mysql = mysqli_query($this->db, $sql)) {
			while($row = $mysql->fetch_object()) {
				$sql2 = "SELECT nickname FROM `players` WHERE playerlogin = '".$row->playerlogin."'";
				if($mysql2 = mysqli_query($this->db, $sql2)) {
					$row2 = $mysql2->fetch_object();
					$nickname = $row2->nickname;
				}
				else {
					$nickname = $row->nickname;
				}
				$this->recsArray[] = array('Login' => $row->playerlogin, 'NickName' => $nickname, 'time' => $row->time, 'challengeid' => $row->challengeid);
			}
		}
		
		if($return == true) {
			return $this->recsArray;
		}
	}
	
	public function sortArray($array) {
		//Sort the local records array after adding a new record to it or updating it
		foreach($array as $value){
			$newID[] = $value;
		}
		$array = $newID;
		
		return $array;
	}
	
	public function onPlayerFinish($PlayerFinish) {	
		//Update the local records array and insert the new record into the database
		global $settings, $_Dedimania_recs, $posn_local;
		
		//Write local records
		$this->instance()->client->query('GetCurrentChallengeInfo');
		$records_challenge_info = $this->instance()->client->getResponse();
		
		$this->instance()->client->query('GetDetailedPlayerInfo', $PlayerFinish[1]);
		$player_info = $this->instance()->client->getResponse();
		
		$color_newlocal = $this->config->settings->color->color_newlocal;
		$gameMode = $this->instance()->getGameMode();
		
		$newRec = false;
		$newLive = false;
		
		if($PlayerFinish[2] > 0) {		
			/*
				LIVE RANKINGS
			*/
			$isInLiveArray = false;
			foreach($this->liveRankings as $key => $value) {
				if($value['Login'] == $PlayerFinish[1]) {
					$isInLiveArray = true;
					$liveArrayKey = $key;
					break;
				}
			}
			
			//Write Live Rankings
			if($isInLiveArray == true) {
				if($PlayerFinish[0] < $this->liveRankings[$liveArrayKey]['time']) {
					$this->liveRankings[$liveArrayKey]['time'] = $PlayerFinish[0];
					uasort($this->liveRankings, 'compareTimeArrays');
					
					$newLive = true;
				}
			} else {
				$this->liveRankings[] = array('Login' => $PlayerFinish[1], 'time' => $PlayerFinish[0], 'NickName' => $player_info['NickName']);
				uasort($this->liveRankings, 'compareTimeArrays');
				
				$newLive = true;
			}
		
			/*
				LOCAL RECORDS
			*/
			if($posn_local != false) {	
				$isInArray = false;
			
				foreach($this->recsArray as $key => $value) {
					if($value['Login'] == $PlayerFinish[1]) {
						$isInArray = true;
						$arrayKey = $key;
						break;
					} else {
						$isInArray = false;
					}
				}
					
				if($isInArray == true) {
					if($PlayerFinish[2] < $this->recsArray[$arrayKey]['time']) {
						$time2 = $this->instance()->format_time($this->recsArray[$arrayKey]['time'] - $PlayerFinish[2]);
							
						$this->recsArray[$arrayKey]['time'] = $PlayerFinish[2];
						uasort($this->recsArray, 'compareTimeArrays');
						$this->recsArray =  $this->sortArray($this->recsArray);
								
						foreach($this->recsArray as $key => $value) {
							if($PlayerFinish[1] == $value['Login']) {
								$rank = $key + 1;
								break;
							}
						}
							
						$time = $this->instance()->format_time($PlayerFinish[2]);
							
						if($this->config->settings->general->show_message == 1){
							$this->chat($player_info['NickName'].'$z$s '.$color_newlocal.'claimed the $fff'.$rank.'. '.$color_newlocal.'local record! Time: $fff'.$time.'$z $s$n$fff(- '.$time2.')', $color_newlocal);
						}
						if($this->config->settings->general->show_message == 2){
							$this->chatToLogin($PlayerFinish[1], $color_newlocal.'You claimed the $fff'.$rank.'. '.$color_newlocal.'local record! Time: $fff'.$time.'$z $s$n$fff(- '.$time2.')', $color_newlocal);
						}
							
						$sql = mysqli_query($this->db, "UPDATE `records` SET time = '".$PlayerFinish[2]."', nickname = '".mysqli_real_escape_string($this->db, $player_info['NickName'])."' WHERE challengeid = '".$records_challenge_info['UId']."' AND playerlogin = '".$PlayerFinish[1]."'");
							
						$newRec = true;
					}
				} else {
					if(isset($this->recsArray[$this->maxRecs]) AND $PlayerFinish[2] >= $this->recsArray[$this->maxRecs]['time']){
						return;
					}
				
					$this->recsArray[] = array('Login' => $PlayerFinish[1], 'NickName' => $player_info['NickName'], 'time' => $PlayerFinish[2], 'challengeid' => $records_challenge_info['UId']);
					uasort($this->recsArray, 'compareTimeArrays');
					$this->recsArray =  $this->sortArray($this->recsArray);
				
					$time = $this->instance()->format_time($PlayerFinish[2]);
	
					foreach($this->recsArray as $key => $value) {
						if($PlayerFinish[1] == $value['Login']) {
							$rank = $key + 1;
							break;
						}
					}
	
					if($this->config->settings->general->show_message == 1){
						$this->chat($player_info['NickName'].'$z$s '.$color_newlocal.'claimed the $fff'.$rank.'. '.$color_newlocal.'local record! Time: $fff'.$time, $color_newlocal);
					}
					elseif($this->config->settings->general->show_message == 2){
						$this->chatToLogin($PlayerFinish[1], $color_newlocal.'You claimed the $fff'.$rank.'. '.$color_newlocal.'local record! Time: $fff'.$time, $color_newlocal);
					}
			
					$sql = mysqli_query($this->db, "INSERT INTO `records` VALUES ('".$records_challenge_info['UId']."', '".$PlayerFinish[1]."', '".mysqli_real_escape_string($this->db, $player_info['NickName'])."', '".$PlayerFinish[2]."', '".date('Y.m.d H:i:s')."')");
				
					$newRec = true;
				}
			}
			
			if($newRec == true) {
				$this->refreshLocalWidgets();
				$this->refreshLiveWidgets();
			}
			else if($newRec == false && $newLive == true) {
				$this->refreshLiveWidgets();
			}
		}
	}
	
	/*
		REFRESH MANIALINKS (WIDGETS)
	*/
	public function refreshLiveWidgets() {
		global $posn_live;
	
		if($posn_live != false){
			$this->displayLiveWidget();
		} else {
			$this->closeMl($this->mlids[1]);
		}
	}
	
	/*
		DISPLAY WIDGETS
	*/
	public function refreshLocalWidgets() {
		global $posn_local, $posn_live, $settings;
		
		//DISPLAY LOCAL RECORDS
		if($posn_local != false){
			$this->displayLocalWidget();
		} else {
			$this->closeMl($this->mlids[0]);
		}
	}
	
	/*
		CREATE LOCAL RECORDS WIDGET CODE
	*/
	public function displayLocalWidget() {
		global $widget_code_local, $posn_local;
			
		$this->instance()->client->query('GetPlayerList', 200, 0);
		$playerList = $this->instance()->client->getResponse();
			
		$widget = $this->widget;
			
		foreach($playerList as $key => $value) {
			foreach($this->recsArray as $key2 => $value2) {
				if($playerList[$key]['Login'] == $this->recsArray[$key2]['Login']) {
					$playerRank = $key2;
					break;
				}
			}
			if(!isset($playerRank)) {
				$playerRank = 7;
			}
			else if($playerRank < 5) {
				$playerRank = 7;
			}
			else if($playerRank < 8 && $playerRank > 3) {
				$playerRank = 7;
			}
			else {
				$playerRank = $playerRank - 1;
			}
			
			$playerBar = 0;
			$playerBar2 = 0;
			
			$widget->clearContent($playerList[$key]['Login'], $this->widgetIDs[0]);
			
			for($run = 0; true; $run++){
				$rank = $run + 1;
				if(!isset($this->recsArray[$run])) break;				
				
				//DISPLAY RECORDS 1 - 3
				if($rank <= 3) {		
					//GET TIME AND EDIT NICKNAME
					$time = $this->instance()->format_time($this->recsArray[$run]['time']);
					if(preg_match('/$s/', $this->recsArray[$run]['NickName'])) $nickname = $this->recsArray[$run]['NickName'];
					else $nickname = '$s'.$this->recsArray[$run]['NickName'];
					
					$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.$rank.'</td><td width="4.5">'.$time.'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $playerList[$key]['Login'], $this->widgetIDs[0]);
				
					if($this->recsArray[$run]['Login'] == $playerList[$key]['Login']) {
						$playerBar = $run + 1;
					}
				} 
				
				//RECORDS OVER 3
				else {	
					//GET TIME AND EDIT NICKNAME
					$rank = ($playerRank - 4) + ($run - 2);
					$time = $this->instance()->format_time($this->recsArray[$rank - 1]['time']);
					if(preg_match('/$s/', $this->recsArray[$rank - 1]['NickName'])) $nickname = $this->recsArray[$rank - 1]['NickName'];
					else $nickname = '$s'.$this->recsArray[$rank - 1]['NickName'];
					
					$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.$rank.'</td><td width="4.5">'.$time.'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $playerList[$key]['Login'], $this->widgetIDs[0]);
				
					if($this->recsArray[$rank - 1]['Login'] == $playerList[$key]['Login']) {
						$playerBar2 = $run + 1;
					}
				}
				
				if(!isset($this->recsArray[$rank]) || $run >= 11) break;
			}
			
			$widget->addCodeToLogin(false, $playerList[$key]['Login'], $this->widgetIDs[0]);
			
			//GET PLAYER BEST BAR POSITION
			if($playerBar <= 3 && $playerBar != 0 && $playerBar2 == 0) {
				$pb_y = 2 * ($playerBar) + 1.7;
				$playerBarCode = '<quad posn="-8.5 '.(-$pb_y).' 2" sizen="17.5 2" style="BgsPlayerCard" substyle="BgRacePlayerName"/><quad posn="7.5 '.(-$pb_y).' 3" sizen="2 2" style="Icons64x64_1" substyle="ShowLeft2"/>';
				$widget->addCodeToLogin($playerBarCode, $playerList[$key]['Login'], $this->widgetIDs[0]);
			} else if($playerBar2 != 0 && $playerBar == 0) {
				$pb_y = 2 * ($playerBar2) + 1.7;
					
				$playerBarCode = '<quad posn="-8.5 '.(-$pb_y).' 2" sizen="17.5 2" style="BgsPlayerCard" substyle="BgRacePlayerName"/><quad posn="7.5 '.(-$pb_y).' 3" sizen="2 2" style="Icons64x64_1" substyle="ShowLeft2"/>';
				$widget->addCodeToLogin($playerBarCode, $playerList[$key]['Login'], $this->widgetIDs[0]);
			}
		
			$widget->displayWidget($playerList[$key]['Login'], $this->mlids[0], $this->widgetIDs[0]);
		}
	}
	
	//CREATE LIVE RANKINGS WIDGET CODE
	public function displayLiveWidget() {
		global $widget_code_live, $posn_live;
		
		$gameMode = $this->gameMode;
		
		$this->instance()->client->query('GetCurrentRanking', 200, 0);
		$current_ranking = $this->instance()->client->getResponse();
		
		$this->instance()->client->query('GetPlayerList', 200, 0);
		$playerList = $this->instance()->client->getResponse();

		$widget = $this->widget;
		
		foreach($playerList as $key => $value) {
			foreach($this->liveRankings as $key2 => $value2) {
				if($this->liveRankings[$key2]['Login'] == $playerList[$key]['Login']) {
					$playerRank = $key2;
					break;
				}
			}
			
			if(!isset($playerRank)) {
				$playerRank = 7;
			} 
			else if($playerRank < 5) {
				$playerRank = 7;
			} 
			else if($playerRank < 8 && $playerRank > 3) {
				$playerRank = 7;
			}
			else {
				$playerRank = $playerRank - 1;
			}
			
			$widget->clearContent($playerList[$key]['Login'], $this->widgetIDs[1]);
		
			$playerBar = 0;
			$playerBar2 = 0;
		
			for($run=0; $run<15 && isset($this->liveRankings[$run]); $run++) {
				$rank = $run+1;

				if($rank<4){					
					//Modify time and nickname
					$time = $this->instance()->format_time($this->liveRankings[$run]['time']);
					if(preg_match('/$s/', $this->liveRankings[$run]['NickName'])) $nickname = $this->liveRankings[$run]['NickName'];
					else $nickname = '$s'.$this->liveRankings[$run]['NickName'];
						
					if($gameMode['name'] == 'rounds') {
						$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.$current_ranking[$run]['Score'].' P</td><td width="4.5">'.$time.'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $playerList[$key]['Login'], $this->widgetIDs[1]);
					} else {
						$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.$rank.'</td><td width="4.5">'.$time.'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $playerList[$key]['Login'], $this->widgetIDs[1]);
					}
				
					if($this->liveRankings[$run]['Login'] == $playerList[$key]['Login']) {
						$playerBar = $run + 1;
					}
				}else{
					$rank = ($playerRank - 4) + ($run - 2);
					
					//Modify time and nickname
					$time = $this->instance()->format_time($this->liveRankings[$rank - 1]['time']);
					if(preg_match('/$s/', $this->liveRankings[$rank - 1]['NickName'])) $nickname = $this->liveRankings[$rank - 1]['NickName'];
					else $nickname = '$s'.$this->liveRankings[$rank - 1]['NickName'];
					
					if($gameMode['name'] == 'rounds') {
						$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.$current_ranking[$rank - 1]['Score'].' P</td><td width="4.5">'.$time.'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $playerList[$key]['Login'], $this->widgetIDs[1]);
					} else {
						$widget->addContent('<td width="0.5"></td><td width="2">$o$09f'.$rank.'</td><td width="4.5">'.$time.'</td><td width="9">'.htmlspecialchars(stripslashes($nickname)).'</td>', $playerList[$key]['Login'], $this->widgetIDs[1]);
					}
				
					if($this->liveRankings[$rank - 1]['Login'] == $playerList[$key]['Login']) {
						$playerBar2 = $run + 1;
					}
				}
			}
			
			$widget->addCodeToLogin(false, $playerList[$key]['Login'], $this->widgetIDs[1]);
			
			//GET PLAYER BEST BAR POSITION
			if($playerBar <= 3 && $playerBar != 0 && $playerBar2 == 0) {
				$pb_y = 2 * ($playerBar) + 1.7;
					
				$playerBarCode = '<quad posn="-9 '.(-$pb_y).' 2" sizen="17.5 2" style="BgsPlayerCard" substyle="BgRacePlayerName"/><quad posn="-9.5 '.(-$pb_y).' 3" sizen="2 2" style="Icons64x64_1" substyle="ShowRight2"/>';
				$widget->addCodeToLogin($playerBarCode, $playerList[$key]['Login'], $this->widgetIDs[1]);
			} else if($playerBar2 != 0) {
				$pb_y = 2 * ($playerBar2) + 1.7;
					
				$playerBarCode = '<quad posn="-9 '.(-$pb_y).' 2" sizen="17.5 2" style="BgsPlayerCard" substyle="BgRacePlayerName"/><quad posn="-9.5 '.(-$pb_y).' 3" sizen="2 2" style="Icons64x64_1" substyle="ShowRight2"/>';
				$widget->addCodeToLogin($playerBarCode, $playerList[$key]['Login'], $this->widgetIDs[1]);
			}
			
			$widget->displayWidget($playerList[$key]['Login'], $this->mlids[1], $this->widgetIDs[1]);
		}
	}
	
	/*
		GET LIVE RANKINGS
	*/
	public function getLiveRankings() {
		return $this->liveRankings;
	}
	
	public function onCommand($args) {
		if($args[2] == 'records') {
			$this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
		}
	}	
	
	public function onPagesLocal($args) {
		if($args[2] == 1) $this->recsListLogin[$args[1]] = 0; // <<
		elseif($args[2] == 2 && $this->recsListLogin[$args[1]] > 0) $this->recsListLogin[$args[1]]--; // <
		elseif($args[2] == 6) $this->recsListLogin[$args[1]]++; // >
		elseif($args[2] == 7) $this->recsListLogin[$args[1]] = floor(count($this->recsArray) / 25);
		
		$arr = array(1 => $args[1], 2 => $this->mlids[0]);
		$this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
	}
	
	public function onPagesLive($args) {	
		if($args[2] == 1) $this->liveListLogin[$args[1]] = 0; // <<
		elseif($args[2] == 2 && $this->liveListLogin[$args[1]] > 0) $this->liveListLogin[$args[1]]--; // <
		elseif($args[2] == 6) $this->liveListLogin[$args[1]]++; // >
		elseif($args[2] == 7) $this->liveListLogin[$args[1]] = floor(count($this->liveRankings) / 25);
		
		$arr = array(1 => $args[1], 2 => $this->mlids[1]);
		$this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[1]));
	}
	
	public function onManialinkPageAnswer($args) {
		//DELETE RECORD
		if($args[2] >= $this->mlids[4] && $args[2] <= $this->mlids[29]) {
			$pageID = $this->recsListLogin[$args[1]];
			$pageID = $pageID * 25;
			$recID = ($args[2] - $this->mlids[4] + $pageID);
		
			$this->instance()->client->query('GetDetailedPlayerInfo', $args[1]);
			$detailedPlayerInfo = $this->instance()->client->getResponse();
			$nickname = $detailedPlayerInfo['NickName'];
			
			$rankName = $this->getRankName($args[1], false);
			
			$this->chat('$s'.$rankName.' '.$nickname.'$z$s$f90 removed local record from $fff'.$this->recsArray[$recID]['NickName'].'$z$s$f90. Time: $fff'.$this->instance()->format_time($this->recsArray[$recID]['time']).'$f90!', 'f90');
		
			$sql = mysqli_query($this->db, 'DELETE FROM records WHERE challengeid = "'.$this->recsArray[$recID]['challengeid'].'" AND playerlogin = "'.$this->recsArray[$recID]['Login'].'"');
		
			$this->writeLocals();
			$this->refreshLocalWidgets();
			
			$this->onManialinkPageAnswer(array(1 => $args[1], 2 => $this->mlids[0]));
		}
	
		//DISPLAY RECORD LIST
		if($args[2] == $this->mlids[0]){
			if(!isset($this->recsListLogin[$args[1]])) $this->recsListLogin[$args[1]] = 0;
			
			$recsListID = $this->recsListLogin[$args[1]] * 25;
			
			if(isset($this->recsArray[$recsListID - 24])) $prev_page = true;
			else $prev_page = false;
			
			if(isset($this->recsArray[$recsListID + 24])) $next_page = true;
			else $next_page = false;
			
			$window = $this->window;
			$window->init();
			$window->title('$fffLocal records');
			$window->displayAsTable(true);
			$window->size(70, '');
			$window->posY('40');
			$window->target('onPagesLocal', $this);
			
			if($prev_page == true){
				$window->addButton('<<<', '7', false);
				$window->addButton('<', '7', false);
			} else {
				$window->addButton('', '7', false);
				$window->addButton('', '7', false);
			}
			
			$window->addButton('', '15.5', false);
			$window->addButton('Close', '10', true);
			$window->addButton('', '15.5', false);
			
			if($next_page == true){
				$window->addButton('>', '7', false);
				$window->addButton('>>>', '7', false);
			} else {
				$window->addButton('', '7', false);
				$window->addButton('', '7', false);
			}
			
			//CHECK ADMIN RIGHTS
			$rights = $this->getRights($args[1]);
			if($rights[0] == 1) require('include/op_rights.php');
			else if($rights[0] == 2) require('include/admin_rights.php');
			else if($rights[0] == 3) require('include/superadmin_rights.php');
		
			if($rights[0] >= 1 && $rights[0] <= 3) {
				if($admin_delete_record == true) {
					$admin = true;
				}else {
					$admin = false;
				}
			}else {
				$admin = false;
			}
			
			if($admin == true) {
				$window->content('<td width="4">$iRank</td><td width="2"></td><td width="10">$iTime</td><td width="3"></td><td width="22">$iNickname</td><td width="3"></td><td width="14">$iLogin</td><td width="6">Actions</td>');
			}else {
				$window->content('<td width="4">$iRank</td><td width="2"></td><td width="10">$iTime</td><td width="3"></td><td width="22">$iNickname</td><td width="3"></td><td width="14">$iLogin</td><td width="3"></td>');
			}
			
			for($i = 0; isset($this->recsArray[$recsListID]) && $i <= 24; $i++) {
				$recs_ml_id = $this->mlids[4] + $i;
			
				if($admin == true) {
					$window->content('<td width="4">$o$09f'.($recsListID+1).'</td><td width="2"></td><td width="10">'.$this->instance()->format_time($this->recsArray[$recsListID]['time']).'</td><td width="3"></td><td width="22">'.htmlspecialchars($this->recsArray[$recsListID]['NickName']).'</td><td width="3"></td><td width="14">'.$this->recsArray[$recsListID]['Login'].'</td><td width="6" id="'.$recs_ml_id.'" align="center">Delete</td>');
				}else {
					$window->content('<td width="4">$o$09f'.($recsListID+1).'</td><td width="2"></td><td width="10">'.$this->instance()->format_time($this->recsArray[$recsListID]['time']).'</td><td width="3"></td><td width="22">'.htmlspecialchars($this->recsArray[$recsListID]['NickName']).'</td><td width="3"></td><td width="14">'.$this->recsArray[$recsListID]['Login'].'</td>');
				}
				
				$recsListID++;
			}
			
			$window->show($args[1]);
			
		//DISPLAY LIVE RANKINGS
		} else if($args[2] == $this->mlids[1]) {		
			if(!isset($this->liveListLogin[$args[1]])) $this->liveListLogin[$args[1]] = 0;
			
			$liveListID = $this->liveListLogin[$args[1]] * 25;
			
			if(isset($this->liveRankings[$liveListID - 24])) $prev_page = true;
			else $prev_page = false;
			
			if(isset($this->liveRankings[$liveListID + 24])) $next_page = true;
			else $next_page = false;
			
			$window = $this->window;
			$window->init();
			$window->title('$fffLive Rankings');
			$window->displayAsTable(true);
			$window->size(70, '');
			$window->posY('40');
			$window->target('onPagesLive', $this);
			
			if($prev_page == true){
				$window->addButton('<<<', '7', false);
				$window->addButton('<', '7', false);
			} else {
				$window->addButton('', '7', false);
				$window->addButton('', '7', false);
			}
			
			$window->addButton('', '15.5', false);
			$window->addButton('Close', '10', true);
			$window->addButton('', '15.5', false);
			
			if($next_page == true){
				$window->addButton('>', '7', false);
				$window->addButton('>>>', '7', false);
			} else {
				$window->addButton('', '7', false);
				$window->addButton('', '7', false);
			}
			
			$window->content('<td width="4">$iRank</td><td width="2"></td><td width="10">$iTime</td><td width="3"></td><td width="22">$iNickname</td><td width="3"></td><td width="14">$iLogin</td><td width="3"></td>');
			
			//Display Live Rankings
			for($i = 0; isset($this->liveRankings[$liveListID]) && $i <= 24; $i++) {
				if($this->liveRankings[$liveListID]['time'] >= 0) {
					$time = $this->instance()->format_time($this->liveRankings[$liveListID]['time']);
					$window->content('<td width="4">$o$09f'.($liveListID+1).'</td><td width="2"></td><td width="10">'.$time.'</td><td width="3"></td><td width="22">'.htmlspecialchars($this->liveRankings[$liveListID]['NickName']).'</td><td width="3"></td><td width="14">'.$this->liveRankings[$liveListID]['Login'].'</td>');
				
					$liveListID++;
				}
			}
			
			$window->show($args[1]);
		}
	}
}
?>