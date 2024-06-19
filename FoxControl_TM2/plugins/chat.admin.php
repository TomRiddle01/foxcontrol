<?php
//* chat.admin.php - Admin Chat Commands
//* Version:   0.5
//* Coded by:  cyrilw, matrix142
//* Copyright: FoxRace, http://www.fox-control.de

global $chall_restarted_admin;
$chall_restarted_admin = false;

class chat_admin extends FoxControlPlugin {
	public $trackdir = 'Downloaded';
	public $commandsPerPage = 17;
	public $helpUsers = array();
	
	public function onStartUp() {
		$this->registerCommand('adminhelp', 'Shows the helpwindow for the admin commands.', false);
		$this->registerCommand('set', 'Sets different Server details. Type $s/adminhelp set$s for more details.', true);
		$this->registerCommand('add', 'Adds new Admins, Tracks and more. Type $s/adminhelp add$s for more details.', true);
		$this->registerCommand('remove', 'Removes Admins, Tracks and more. Type $s/adminhelp remove$s for more details.', true);
		$this->registerCommand('save', 'Saves the matchsettings. Type $s/adminhelp save$s for more details.', true);
		$this->registerCommand('kick', 'Kicks the specified player. /kick <login>', true);
		$this->registerCommand('warn', 'Warns the specified player. /warn <login>', true);
		$this->registerCommand('ban', 'Bans the specified player. /ban <login>', true);
		$this->registerCommand('blacklist', 'Blacklists the specified player. /blacklist <login>', true);
		$this->registerCommand('unblacklist', 'Removes the specified player from the blacklist. /unblacklist <login>', true);
		$this->registerCommand('unban', 'Unans the specified player. /unban <login>', true);
		$this->registerCommand('reboot', 'Reboots FoxControl.', true);
		$this->registerCommand('skip', 'Skips the current challenge.', true);
		$this->registerCommand('restart', 'Restarts the current challenge.', true);
		$this->registerCommand('res', 'Restarts the current challenge. Same command as $s/restart$s.', true);
		$this->registerCommand('replay', 'Queues the current challenge for restart.', true);
		$this->registerCommand('endround', 'Forces round end.', true);
		$this->registerCommand('planets', 'Shows the planets amount.', true);
		$this->registerCommand('pay', 'Pays planets to the specified login. /pay <amount> <login>', true);
		$this->registerCommand('mode', 'Sets the game mode to the specified mode. Type in $s/adminhelp mode$s for more details.', true);
		$this->registerCommand('forcespec', 'Forces a player into Spectator mode.  /forcespec <login>', true);
		$this->registerCommand('forceplayer', 'Forces a player into Player mode. /forceplayer <login>', true);
		
		$this->registerMLIds(1);
		
		$this->name = 'Admin chat';
		$this->author = 'matrix142 & cyrilw';
		$this->version = '0.6';
	}
	public function onCommand($args) {
		global $settings;
		$this->instance()->client->query('GetDetailedPlayerInfo', $args[1]);
		$CommandAuthor = $this->instance()->client->getResponse();
		$rights = $this->getRights($args[1]);
		if($rights[0] == 0) {
			$this->sendError($CommandAuthor['Login']);
			return;
		}
		else if($rights[0] == 1) require('include/op_rights.php');
		else if($rights[0] == 2) require('include/admin_rights.php');
		else if($rights[0] == 3) require('include/superadmin_rights.php');
		if($args[2] == 'adminhelp') {
			if(!empty($args[3][0])) {
				if(is_numeric($args[3][0])) $site = ($args[3][0]-1);
				else $command = $args[3][0];
			} else $site = 0;
			
			if(isset($site)) {
				$this->helpUsers[$args[1]] = $site;
				
				$window = $this->window;
				$window->init();
				$window->title('Adminhelp');
				$window->close(true);
				$window->displayAsTable(true);
				$window->size(70, '');
				$window->posY('40');
				$window->target('onButtonPressed', $this);
				
				$window->content('<td width="15">Command</td><td width="2"></td><td width="50">Description</td>');
				$window->content(' ');
				
				$help = $this->instance()->getCommands('admin');
				$commands = 0;
				
				for($i = ($site * $this->commandsPerPage); $i < count($help); $i++) {
					$window->content('<td width="15">/'.$help[$i][0].'</td><td width="2"></td><td width="50">'.$help[$i][1].'</td>');
					$commands++;
					if($commands >= $this->commandsPerPage) break;
				}
				if($site > 0) $window->addButton('<', 7, false);
				else $window->addButton('', 7, false);
				$window->addButton('Close', 15, true);
				if(($i+1) < count($help)) $window->addButton('>', 7, false);
				else $window->addButton('', 7, false);
				$window->show($args[1]);
			} else if(isset($command)) {
				if($command == 'set') {
					$window = $this->window;
					$window->init();
					$window->title('Adminhelp - Command: set');
					$window->close(true);
					
					$window->size(70, '');
					$window->posY('40');
					
					$window->content('You can set different things with the $s/set$s command:');
					$window->content('$o/set serverpw <pw>$o Sets the password for players.');
					$window->content('$o/set specpw <pw>$o Sets the password for spectators.');
					$window->content('$o/set servername <name>$o Sets the servername.');
					$window->content('$o/set comment <comment>$o Sets the comment of this server.');
					
					$window->addButton('OK', 15, true);
					
					$window->show($args[1]);
				} else if($command == 'add') {
					$window = $this->window;
					$window->init();
					$window->title('Adminhelp - Command: add');
					$window->close(true);
					
					$window->size(70, '');
					$window->posY('40');
					
					$window->content('You can add different things with the $s/add$s command:');
					$window->content('$o/add superadmin <login>$o Adds a new Superadmin.');
					$window->content('$o/add admin <login>$o Adds a new Admin.');
					$window->content('$o/add op <login>$o Adds a new Operator.');
					$window->content('$o/add map <id>$o Adds a new Map with the specified MX-Id.');
					
					$window->addButton('OK', 15, true);
					
					$window->show($args[1]);
				} else if($command == 'remove') {
					$window = $this->window;
					$window->init();
					$window->title('Adminhelp - Command: remove');
					$window->close(true);
					
					$window->size(70, '');
					$window->posY('40');
					
					$window->content('You can remove different things with the $s/remove$s command:');
					$window->content('$o/remove admin <login>$o Removes the Admin/Superadmin/OP with the specified login.');
					$window->content('$o/remove track <id|current>$o Removes a track with the specified id..');
					$window->content('..or if you write \'current\', it will remove the current map.');
					
					$window->addButton('OK', 15, true);
					
					$window->show($args[1]);
				} else if($command == 'save') {
					$window = $this->window;
					$window->init();
					$window->title('Adminhelp - Command: save');
					$window->close(true);
					
					$window->size(70, '');
					$window->posY('40');
					
					$window->content('You can save the matchsettings with the $s/save$s command:');
					$window->content('$o/save matchsetting <filename>$o Saves the matchsettings to the specified filename.');
					
					$window->addButton('OK', 15, true);
					
					$window->show($args[1]);
				} else if($command == 'mode') {
					$window = $this->window;
					$window->init();
					$window->title('Adminhelp - Command: mode');
					$window->close(true);
					
					$window->size(70, '');
					$window->posY('40');
					
					$window->content('You can set the game mode with the $s/mode$s command:');
					$window->content('$o/mode script$o Sets game mode to script.');
					$window->content('$o/mode rounds <points> <forceroundlaps> <pointsnewrules>$o Sets game mode to rounds.');
					$window->content('$o/mode timeattack <timelimit>$o Sets game mode to timeattack.');
					$window->content('$o/mode team <points> <maxpoints> <pointsnewrules>$o Sets game mode to team.');
					$window->content('$o/mode laps <numberoflaps> <timelimit>$o Sets game mode to laps.');
					$window->content('$o/mode cup <points> <roundsperchallenge> <numberwinners> <warmupduration>$o Sets game mode to cup.');
					
					$window->addButton('OK', 15, true);
					
					$window->show($args[1]);
				}
			}
		}
		
		//CHANGE GAME MODE
		else if($args[2] == 'mode') {
			if(isset($args[3][0]) AND isset($args[3][1])) {
				$gameInfosArray = array();
			
				$this->instance()->client->query('GetGameInfos');
				$gameInfos = $this->instance()->client->getResponse();
				$gameInfosCurrent = $gameInfos['CurrentGameInfos'];
			
				foreach($gameInfosCurrent as $key => $value) {
					$gameInfosArray[$key] = $value;
				}
			
				//ROUNDS MODE
				if($args[3][0] == 'rounds') {
					$gameInfosArray['GameMode'] = 1;
					$gameInfosArray['RoundsForcedLaps'] = (int) $args[3][2];
					
					//USE NEW RULES
					if(isset($args[3][3])) {
						$gameInfosArray['RoundsUseNewRules'] = true;
						$gameInfosArray['RoundsPointsLimitNewRules'] = (int) $args[3][3];
						
						$textNewRules = 'Yes';
					}else {
						$gameInfosArray['RoundsPointsLimit'] = (int) $args[3][1];
						$gameInfosArray['RoundsUseNewRules'] = false;
						$textNewRules = 'No';
					}
					
					$this->instance()->client->query('SetGameInfos', $gameInfosArray);
					
					$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 set GameMode to Rounds, Points '.$args[3][1].', ForcedLaps '.$args[3][2].', Use new Rules '.$textNewRules.'$z$s$f90!', '$f90');
				}
			
				//TIME ATTACK
				else if($args[3][0] == 'timeattack') {			
					$gameInfosArray['GameMode'] = 2;
					$gameInfosArray['TimeAttackLimit'] = ($args[3][1]*1000);
					
					$this->instance()->client->query('SetGameInfos', $gameInfosArray);
				
					$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 set GameMode to TimeAttack, Timelimit '.$args[3][1].' seconds$z$s$f90!', '$f90');
				}
				//TEAM MODE
				else if($args[3][0] == 'team') {
					$gameInfosArray['GameMode'] = 3;
					$gameInfosArray['TeamPointsLimit'] = (int) $args[3][1];
					$gameInfosArray['TeamMaxPoints'] = (int) $args[3][2];
				
					//USE NEW RULES
					if(isset($args[3][3])) {
						$gameInfosArray['TeamUseNewRules'] = true;
						$gameInfosArray['TeamPointsLimitNewRules'] = (int) $args[3][3];
						
						$textNewRules = 'Yes';
					}else {
						$gameInfosArray['TeamUseNewRules'] = false;
						$textNewRules = 'No';
					}
					
					$this->instance()->client->query('SetGameInfos', $gameInfosArray);
				
					$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 set GameMode to Team, Points '.$args[3][1].', Max Points '.$args[3][2].', Use new Rules '.$textNewRules.'$z$s$f90!', '$f90');
			
					$this->reloadWidgetPosns = true;
				}
				
				//LAPS MODE
				else if($args[3][0] == 'laps') {
					$gameInfosArray['GameMode'] = 4;
					$gameInfosArray['LapsNbLaps'] = (int) $args[3][1];
					$gameInfosArray['LapsTimeLimit'] = (int) $args[3][2];
					
					$this->instance()->client->query('SetGameInfos', $gameInfosArray);
					
					$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 set GameMode to Laps, Number of Laps '.$args[3][1].', Time Limit '.$args[3][2].'$z$s$f90!', '$f90');
				
					$this->reloadWidgetPosns = true;
				}
				
				//CUP MODE
				else if($args[3][0] == 'cup') {
					$gameInfosArray['GameMode'] = 5;
					$gameInfosArray['CupPointsLimit'] = (int) $args[3][1];
					$gameInfosArray['CupRoundsPerChallenge'] = (int) $args[3][2];
					$gameInfosArray['CupNbWinners'] = (int) $args[3][3];
					$gameInfosArray['CupWarmUpDuration'] = (int) $args[3][4];
					
					$this->instance()->client->query('SetGameInfos', $gameInfosArray);
					
					$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 set GameMode to Cup, Points '.$args[3][1].', Rounds '.$args[3][2].', Number of Winners '.$args[3][3].'$z$s$f90!', '$f90');
				
					$this->reloadWidgetPosns = true;
				}
			}
		}
		
		//SET VARIOUS SETTINGS
		else if($args[2] == 'set') {
			if(!empty($args[3][0])) {
			
				//SERVERPASSWORD
				if($args[3][0] == 'serverpw') {
					if($set_spectatorpw == true){
						$this->instance()->client->query('SetServerPassword', $args[3][1]);
						
						if(isset($args[3][1])) {
							$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_SetPW'].'sets the Serverpassword to $fff'.$args[3][1].'$z$s '.$settings['Color_SetPW'].'!', $settings['Color_SetPW']);
						} else {
							$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_SetPW'].'removed the Serverpassword!', $settings['Color_SetPW']);
						}
					} else $this->sendError($CommandAuthor['Login']);
				
				//SPECTATORPASSWORD
				} else if($args[3][0] == 'specpw') {
					if($set_serverpw == true){
						$this->instance()->client->query('SetServerPasswordForSpectator', $args[3][1]);
						
						if(isset($args[3][1])) {
							$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_SetPW'].'sets the Spectatorpassword to $fff'.$args[3][1].'$z$s '.$settings['Color_SetPW'].'!', $settings['Color_SetPW']);
						} else {
							$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_SetPW'].'removed the Spectatorpassword!', $settings['Color_SetPW']);
						}
					}
					else $this->sendError($CommandAuthor['Login']);
					
				//SERVERNAME
				} else if($args[3][0] == 'servername') {
					if($set_servername == true){
						$servername = '';
						for($i = 1; $i < count($args[3]); $i++) $servername .= $args[3][$i].' ';
						$this->instance()->client->query('SetServerName', $servername);
						$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_NewServername'].'sets the Servername to $fff'.$servername.'$z$s '.$settings['Color_NewServername'].'!', $settings['Color_NewServername']);
					} else $this->sendError($CommandAuthor['Login']);
					
				//SERVER COMMENT
				} else if($args[3][0] == 'comment') {
					if($set_servercomment == true){
						$servercomment = '';
						for($i = 1; $i < count($args[3]); $i++) $servercomment .= $args[3][$i].' ';
						$this->instance()->client->query('SetServerComment', $servercomment);
						$color_newservername = $settings['Color_NewServername'];
					
						$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_NewServername'].'sets the Servercomment to $fff'.$servercomment.'$z$s '.$settings['Color_NewServername'].'!', $settings['Color_NewServername']);
					} else $this->sendError($CommandAuthor['Login']);
				}
			}
			
		//ADD VARIOUS DATA
		} else if($args[2] == 'add') {
			if(!empty($args[3][0])) {
			
				//SUPERADMIN
				if($args[3][0] == 'superadmin') {
					if($add_new_superadmin == true){
						if(!empty($args[3][1])) {
							$adminAdded = $this->changeRights($args[3][1], 3);
							if($adminAdded !== false) {
								$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_NewAdmin'].'adds $fff'.$adminAdded.'$z$s '.$settings['Color_NewAdmin'].'as a new SuperAdmin!', $settings['Color_NewAdmin']);
							} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][1].'$f60\' not found!', 'f60');
						} else $this->chatToLogin($args[1], 'Player \'$fff $f60\' not found!', 'f60');
					} else $this->sendError($CommandAuthor['Login']);
					
				//ADMIN
				} else if($args[3][0] == 'admin') {
					if($add_new_admin == true){
						if(!empty($args[3][1])) {
							$adminAdded = $this->changeRights($args[3][1], 2);
							if($adminAdded !== false) {
								$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_NewAdmin'].'adds $fff'.$adminAdded.'$z$s '.$settings['Color_NewAdmin'].'as a new Admin!', $settings['Color_NewAdmin']);
							} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][1].'$f60\' not found!', 'f60');
						} else $this->chatToLogin($args[1], 'Player \'$fff $f60\' not found!', 'f60');
					} else $this->sendError($CommandAuthor['Login']);
				
				//OPERATOR
				} else if($args[3][0] == 'op') {
					if($add_new_op == true){
						if(!empty($args[3][1])) {
							$adminAdded = $this->changeRights($args[3][1], 1);
							if($adminAdded !== false) {
								$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_NewAdmin'].'adds $fff'.$adminAdded.'$z$s '.$settings['Color_NewAdmin'].'as a new Operator!', $settings['Color_NewAdmin']);
							} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][1].'$f60\' not found!', 'f60');
						} else $this->chatToLogin($args[1], 'Player \'$fff $f60\' not found!', 'f60');
					} else $this->sendError($CommandAuthor['Login']);
				
				//MAP
				} else if($args[3][0] == 'map') {
					if($admin_add_track == true) {
						if(!empty($args[3][1]) AND is_numeric($args[3][1])){
							include_once('gbxdatafetcher/gbxdatafetcher.inc.php');
						
							$mxid = $args[3][1];
							//Get Data of the Track from ManiaExchange
							$read = simplexml_load_string($this->getDataFromUrl('http://api.mania-exchange.com/tm/maps/'.$mxid.'?format=xml'));
							
							//Set Filename and Trackname
							if(!isset($read->TrackInfo->Name)) {
								$this->chatToLogin($CommandAuthor['Login'], 'The map with ID '.$mxid.' does not exist or MX is down!', 'f60');
								return;
							}
							
							$filename = $read->TrackInfo->Name.'.Map.Gbx';
							$trackname = $read->TrackInfo->Name;
							
							//Get the Trackfile
							$trackfile = $this->getDataFromUrl('http://tm.mania-exchange.com/tracks/download/'.$mxid.'');	
						
							if(!empty($trackfile) && !empty($filename)) {
								//Get Map Directory
								$this->instance()->client->query('GetMapsDirectory');
								$trackdir = $this->instance()->client->getResponse();							
									
								//Write Trackfile to the server
								$dir = $trackdir.$this->trackdir.'/'.$filename;
								file_put_contents($dir, $trackfile);
							
								//Get Current Script
								$scriptName = $this->instance()->getGameMode();
								
								//Get Maps required Script								
								$gbx = new GBXChallengeFetcher($dir, true);
								$mapGameMode = $gbx->parsedxml['DESC']['MAPTYPE'];
								
								//Clean GameMode Name
								$mapGameMode = str_replace('TrackMania\\', '', $mapGameMode);
								$mapGameMode = str_replace('Trackmania\\', '', $mapGameMode);
								$mapGameMode = str_replace('Multi', '', $mapGameMode);
								
								if($scriptName != $mapGameMode) {
									$this->instance()->client->query('InsertChallenge', $dir);
									$this->chatToLogin($CommandAuthor['Login'], '$z$s$0f0 Map $fff'.$trackname.'$0f0 (ID: $fff'.$mxid.'$0f0) has been downloaded but the GameMode might be wrong. Check with the Command $i/maps$i', '0f0');
								} else {							
									//Insert Map
									$this->instance()->client->query('InsertChallenge', $dir);
									$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$0f0 added $fff'.$trackname.'$0f0 (ID: $fff'.$mxid.'$0f0) from MX!', '0f0');
								}
							} else $this->chatToLogin($CommandAuthor['Login'], 'The map with ID '.$mxid.' does not exsit or MX is down!', 'f60');
						} else $this->chatToLogin($CommandAuthor['Login'], 'The ID must be numeric!', 'f60');
					} else $this->sendError($CommandAuthor['Login']);
				}
			}
			
		//REMOVE VARIOUS DATA
		} else if($args[2] == 'remove') {
			if(!empty($args[3][0])) {
			
				//ADMIN
				if($args[3][0] == 'admin') {
					if($remove_superadmin == true){
						if(!empty($args[3][1])) {
							$ralogin = trim($args[3][1]);
							$sql = "SELECT * FROM `admins` WHERE playerlogin = '".mysqli_real_escape_string($this->db, $ralogin)."'";
							$mysql = mysqli_query($this->db, $sql);
							if($raplayer = $mysql->fetch_object()){
								$sql = "DELETE FROM `admins` WHERE playerlogin = '".mysqli_real_escape_string($this->db, $ralogin)."'";
								$mysql = mysqli_query($this->db, $sql);
								$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s'.$settings['Color_RemoveAdmin'].' removed Superadmin $fff'.$ralogin.$settings['Color_RemoveAdmin'].' !', $settings['Color_RemoveAdmin']);
							} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][1].'$f60\' not found!', 'f60');
						} else $this->chatToLogin($args[3][1], 'Player \'$fff $f60\' not found!', 'f60');
					} else $this->sendError($CommandAuthor['Login']);
				
				//TRACK
				} else if($args[3][0] == 'track') {
					if($admin_delete_track == true){
						if(!empty($args[3][1])){
							$trackid = $args[3][1];
							
							//DELETE TRACK WITH ID
							if(is_numeric($trackid)){
								$trackid--;
								global $challenges;
								if(isset($challenges)){
									$remove_chall = $challenges[$trackid];
								
									$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 removed $fff'.$remove_chall['Name'].'$z$s$f90!', 'f90');
									$this->instance()->client->query('RemoveChallenge', $remove_chall['FileName']);
								
									$this->instance()->write_challenges();
								} else $this->chatToLogin($CommandAuthor['Login'], 'Plugin \'plugin.challenges.php\' isn\'t enabled!', 'f60');	
							}
							
							//DELETE CURRENT TRACK
							elseif($trackid=='current'){
								$this->instance()->client->query('GetCurrentChallengeInfo');
								$remove_chall = $this->instance()->client->getResponse();
								
								$this->chat($rights[1].' '.$CommandAuthor['NickName'].'$z$s$f90 removed $fff'.$remove_chall['Name'].'$z$s$f90!', 'f90');
								$this->instance()->client->query('RemoveChallenge', $remove_chall['FileName']);
							
							} else $this->chatToLogin($CommandAuthor['Login'], 'Invalid Track-ID!', 'f60');
						}
					} else $this->sendError($CommandAuthor['Login']);
				}
			}
			
		//SAVE MATCHSETTINGS
		} else if($args[2] == 'save') {
			if(!empty($args[3][0])) {
				if($args[3][0] == 'matchsettings') {
					if(!empty($args[3][1])) {
						$filename = 'MatchSettings/';
						$first = true;
						for($i = 1; !empty($args[3][$i]); $i++) {
							if($first == false) $filename .= ' ';
							$first = false;
							$filename .= $args[3][$i];
						}
						$this->chatToLogin($args[1], 'Saving the matchsettings to $fff'.$filename.'$0d0...', '0d0');
						$this->instance()->client->query('SaveMatchSettings', $filename);
						$this->chatToLogin($args[1], 'Matchsettings saved!', '0d0');
					}
				}
			}
			
		//KICK PLAYER
		} else if($args[2] == 'kick') {
			if($kick==true){
				$this->instance()->client->query('GetDetailedPlayerInfo', $args[3][0]);
				$kickedplayer = $this->instance()->client->getResponse();
				if(empty($kickedplayer['Login'])) {
					$this->chatToLogin($args[1], 'Player \'$fff'.$args[3][0].'$f60\' not found!', 'f60');
				} else {
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_Kick'].'kicked $fff'.$kickedplayer['NickName'].'$z$s '.$settings['Color_Kick'].'!', $settings['Color_Kick']);
					$this->instance()->client->query('Kick', $args[3][0]);
				}
			} else $this->sendError($CommandAuthor['Login']);
		
		//WARN PLAYER
		} else if($args[2] == 'warn') {
			if($warn==true){
				$this->instance()->client->query('GetDetailedPlayerInfo', $args[3][0]);
				$warnedplayer = $this->instance()->client->getResponse();;
				if(empty($warnedplayer['Login'])) {
					$this->chatToLogin($args[1], 'Player \'$fff'.$args[3][0].'$f60\' not found!', 'f60');
				} else {
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_Warn'].'warned $fff'.$warnedplayer['NickName'].'$z$s '.$settings['Color_Warn'].'!', $settings['Color_Warn']);
					$code = '<quad posn="-64 48 15" sizen="128 96" bgcolor="0006"/>
					<quad posn="0 15 18" sizen="40 21" valign="center" halign="center" style="Bgs1InRace" substyle="NavButtonBlink"/>
					<quad posn="0 15 17" sizen="40 21" valign="center" halign="center" style="Bgs1InRace" substyle="BgList"/>
					<quad posn="0 24.5 19" sizen="39 2.5" halign="center" style="BgsPlayerCard" substyle="BgActivePlayerScore"/>
					<label posn="0 24.25 20" textsize="2" halign="center" text="$o$f00WARNING!"/>
					<label posn="-18 20.75 20" textsize="2" sizen="36 2" autonewline="1" text="This is an administrator warning!'.nz.'What ever you wrote or made is against our server rights.'.nz.'An administrator or Operator can kick or ban you next time!'.nz.'Be fair."/>
					<quad posn="15.75 24.5 20" sizen="2.5 2.5" style="Icons64x64_1" substyle="Close" action="'.$this->mlids[0].'"/>';
					$this->displayManialinkToLogin($args[3][0], $code, $this->mlids[0]);
				}
			} else $this->sendError($CommandAuthor['Login']);
		
		//BAN PLAYER
		} else if($args[2] == 'ban') {
			if($ban==true){
				$sql = "SELECT * FROM `players` WHERE playerlogin = '".trim($args[3][0])."'";
				$mysql = mysqli_query($this->db, $sql);
				if($data = $mysql->fetch_object()){
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_Ban'].'banned $fff'.$data->nickname.$settings['Color_Ban'].' !', $settings['Color_Ban']);
					$this->instance()->client->query('Ban', $args[3][0]);
				} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][0].'$f60\' not found!', 'f60');
			} else $this->sendError($CommandAuthor['Login']);
		
		//UNBAN PLAYER
		} else if($args[2] == 'unban') {
			if($unban==true){
				$sql = "SELECT * FROM `players` WHERE playerlogin = '".trim($args[3][0])."'";
				$mysql = mysqli_query($this->db, $sql);
				if($data = $mysql->fetch_object()){
					$this->instance()->unban($args[3][0], false, $CommandAuthor, $data);
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_Ban'].'unbanned $fff'.$data->nickname.$settings['Color_Ban'].' !', $settings['Color_Ban']);
				} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][0].'$f60\' not found!', 'f60');
			} else $this->sendError($CommandAuthor['Login']);
		
		//BLACKLIST PLAYER
		} else if($args[2] == 'blacklist') {
			if($ban==true){
				$sql = "SELECT * FROM `players` WHERE playerlogin = '".trim($args[3][0])."'";
				$mysql = mysqli_query($this->db, $sql);
				if($data = $mysql->fetch_object()){
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_Ban'].'blacklisted $fff'.$data->nickname.$settings['Color_Ban'].' !', $settings['Color_Ban']);
					$this->instance()->client->query('BlackList', $args[3][0]);
					$this->instance()->client->query('Kick', $args[3][0]);
				} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][0].'$f60\' not found!', 'f60');
			} else $this->sendError($CommandAuthor['Login']);
		
		//UNBLACKLIST PLAYER
		} else if($args[2] == 'unblacklist') {
			if($unban==true){
				$sql = "SELECT * FROM `players` WHERE playerlogin = '".trim($args[3][0])."'";
				$mysql = mysqli_query($this->db, $sql);
				if($data = $mysql->fetch_object()){
					$this->instance()->client->query('UnBlackList', $args[3][0]);
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_Ban'].'unblacklisted $fff'.$data->nickname.$settings['Color_Ban'].' !', $settings['Color_Ban']);
				} else $this->chatToLogin($args[1], 'Player \'$fff'.$args[3][0].'$f60\' not found!', 'f60');
			} else $this->sendError($CommandAuthor['Login']);
		
		//FORCE SPECTATOR
		} else if($args[2] == 'forcespec') {
			if($forceSpec == true) {
				$this->instance()->client->query('GetDetailedPlayerInfo', $args[3][0]);
				$playerInfo = $this->instance()->client->getResponse();;
			
				$this->instance()->client->query('ForceSpectator', $args[3][0], 1);
				
				$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_ForceSpec'].'forced $fff'.$playerInfo['NickName'].'$z$g$s'.$settings['Color_ForceSpec'].' into Spectator mode!', $settings['Color_ForceSpec']);
			} else $this->sendError($CommandAuthor['Login']);
		
		//FORCE PLAYER
		} else if($args[2] == 'forceplayer') {
			if($forceSpec == true) {
				$this->instance()->client->query('GetDetailedPlayerInfo', $args[3][0]);
				$playerInfo = $this->instance()->client->getResponse();;
			
				$this->instance()->client->query('ForceSpectator', $args[3][0], 2);
				
				$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s '.$settings['Color_ForceSpec'].'forced $fff'.$playerInfo['NickName'].'$z$g$s'.$settings['Color_ForceSpec'].' into Player mode!', $settings['Color_ForceSpec']);
			} else $this->sendError($CommandAuthor['Login']);
		
		//SHOW PLAYERLIST FOR ADMINS
		} else if($args[2] == 'adminplayers') {
			$this->instance()->show_playerlist($CommandAuthor['Login'], true, 0);
		
		//REBOOT FOXCONTROL
		} else if($args[2] == 'reboot') {
			if($reboot_script==true){
				$this->instance()->FoxControl_reboot();
			} else $this->sendError($CommandAuthor['Login']);
		
		//SKIP TRACK
		} else if($args[2] == 'skip') {
			if($skip_challenge==true){
				$this->instance()->challenge_skip();
				$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s $f90skipped the map!', 'f90');
			} else $this->sendError($CommandAuthor['Login']);
		
		//RESTART TRACK
		} else if($args[2] == 'restart' || $args[2] == 'res') {
			if($restart_challenge==true){
				global $chall_restarted_admin;
				$chall_restarted_admin = true;
				$this->instance()->client->query('RestartChallenge');
				$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s $f90restarted the map!', 'f90');
			} else $this->sendError($CommandAuthor['Login']);
		
		//REPLAY TRACK
		} else if($args[2] == 'replay') {
			$this->instance()->client->query('GetCurrentChallengeInfo');
			$currentChallenge = $this->instance()->client->getResponse();
			
			$this->instance()->client->query('ChooseNextChallenge', $currentChallenge['FileName']);
			
			$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s $f90queues the current map for replay!', 'f90');
		
		//FORCE ENDROUND
		} else if($args[2] == 'endround') {
			if($force_end_round==true){
				$this->instance()->client->query('ForceEndRound');
				$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s $f90forced round end!', 'f90');
			} else $this->sendError($CommandAuthor['Login']);
		
		//SHOW PLANETS
		} else if($args[2] == 'planets') {
			$this->instance()->client->query('GetServerPlanets');
			$planets = $this->instance()->client->getResponse();
			$this->chatToLogin($CommandAuthor['Login'], 'This Server has $fff'.$planets.'$0f0 Planets', '0f0');
		
		//PAY PLANETS
		} else if($args[2] == 'pay') {
			if($admin_pay==true){
				global $settings;
				$coppers_tp = trim($args[3][0]);
				$coppers_tl = trim($args[3][1]);
				if(is_numeric($coppers_tp)!==true){
					$this->chatToLogin($CommandAuthor['Login'], 'The number of the Coppers to pay must be an integer!', 'f60');
				}
				elseif(trim($coppers_tl)==''){
					$this->chatToLogin($CommandAuthor['Login'], 'No login set to pay the Coppers!', 'f60');
				}
				else{
					$pay_message = $CommandAuthor['NickName'].'$z$s payed '.$coppers_tp.' to you from the Server '.$settings['ServerName'].'$z$s !';
					
					$this->instance()->client->query('Pay', trim($coppers_tl), intval($coppers_tp), $pay_message);
					
					$this->chat($rights[1].' $fff'.$CommandAuthor['NickName'].'$z$s $0f0payed $fff'.$coppers_tp.'$0f0 Planets to $fff'.$coppers_tl.'$0f0!', '0f0');	
				}
			} else $this->sendError($CommandAuthor['Login']);
		}
	}
	public function onManialinkPageAnswer($args) {
		if($args[2] == $this->mlids[0]) {
			$this->closeMl($this->mlids[0], $args[1]);
		}
	}
	public function onButtonPressed($args) {
		if($args[2] == 1) { //<
			$newargs = array(1 => $args[1], 2 => 'adminhelp', 3 => array(0 => $this->helpUsers[$args[1]]));
			$this->onCommand($newargs);
		} else if($args[2] == 3) { //>
			$newargs = array(1 => $args[1], 2 => 'adminhelp', 3 => array(0 => ($this->helpUsers[$args[1]] + 2)));
			$this->onCommand($newargs);
		}
	}
	public function sendError($login) {
		global $settings;
		$this->chatToLogin($login, $settings['Text_wrong_rights'], 'f00');
	}
	public function changeRights($login, $rights) {
		$sql = "SELECT * FROM `players` WHERE playerlogin = '".$login."'";
		$mysql = mysqli_query($this->db, $sql);
		if($admin = $mysql->fetch_object()) {
			$sql = "SELECT * FROM `admins` WHERE playerlogin = '".$login."'";
			$mysql = mysqli_query($this->db, $sql);
			if(!$if_admin = $mysql->fetch_object()) {
				$sql = "INSERT INTO `admins` (id, playerlogin, rights) VALUES ('', '".$login."', '".$rights."')";
				$mysql = mysqli_query($this->db, $sql);
				return $admin->nickname;
			} else {
				$sql = "UPDATE `admins` SET rights = '".$rights."' WHERE playerlogin = '".$login."'";
				$mysql = mysqli_query($this->db, $sql);
				return $admin->nickname;
			}	
		} else return false;
	}
	public function getDataFromUrl($url) {
		$options = array('http' => array('user_agent' => 'FoxControl', 'max_redirects' => 1000, 'timeout' => 1000));
		$context = stream_context_create($options);
		return @file_get_contents($url,true,$context );
    }
}

//Manialinks
/*function adminchat_mlanswer($control, $ManialinkPageAnswer){*/


	/********************************/
	/**** FOR PLUGIN.PLAYERS.PHP ****/
	/********************************/
	
	/*if($ManialinkPageAnswer[2]=='4000'){
		$control->close_ml('4000', $ManialinkPageAnswer[1]);
	}
	
	if($ManialinkPageAnswer[2]=='11'){
		$control->close_ml('10', $ManialinkPageAnswer[1]);
	}
	
	global $db, $settings;

	//Get Infos
	$control->client->query('GetDetailedPlayerInfo', $ManialinkPageAnswer[1]);
	$CommandAuthor = $control->client->getResponse();*/
	
	
	/***********************
	***CHECK ADMIN RIGHTS***
	***********************/
	/*$sql = "SELECT * FROM `admins` WHERE playerlogin = '".$CommandAuthor['Login']."'";
	$mysql = mysqli_query($db, $sql);
	if($admin_rights = $mysql->fetch_object()){
		if($admin_rights->rights==1){
			require('include/op_rights.php');
			$Admin_Rank = $settings['Name_Operator'];
		}
		elseif($admin_rights->rights==2){
			require('include/admin_rights.php');
			$Admin_Rank = $settings['Name_Admin'];
		}
		elseif($admin_rights->rights==3){
			require('include/superadmin_rights.php');
			$Admin_Rank = $settings['Name_SuperAdmin'];
		}
		
		
		
	
		if($ManialinkPageAnswer[2]>=250 AND $ManialinkPageAnswer[2]<=500){
			if($kick==true){
				$control->client->query('GetPlayerList', 300, 0);
				$player_list = $control->client->getResponse();
				$player_list_pid = $ManialinkPageAnswer[2]-250;
				if(isset($player_list[$player_list_pid])){
					$kickedplayer = $player_list[$player_list_pid];
					$control->player_kick($kickedplayer['Login'], true, $CommandAuthor);
				}
			}
			else $control->client->query('ChatSendServerMessageToLogin', '$f00»'.$text_false_rights, $CommandAuthor['Login']);
		}
		elseif($ManialinkPageAnswer[2]>500 AND $ManialinkPageAnswer[2]<=750){
			if($ignore==true){
				$control->client->query('GetPlayerList', 300, 0);
				$player_list = $control->client->getResponse();
				$player_list_pid = $ManialinkPageAnswer[2]-500;
				if(isset($player_list[$player_list_pid])){
					$ignoredplayer = $player_list[$player_list_pid];
					$control->player_ignore($ignoredplayer['Login'], true, $CommandAuthor);
					$control->show_playerlist($CommandAuthor['Login'], true, 0);
				}
			}
			else $control->client->query('ChatSendServerMessageToLogin', '$f00»'.$text_false_rights, $CommandAuthor['Login']);
		}
		elseif($ManialinkPageAnswer[2]>49748 AND $ManialinkPageAnswer[2]<=49999){
		if($warn==true){
			$control->client->query('GetPlayerList', 300, 0);
			$player_list = $control->client->getResponse();
			$player_list_pid = $ManialinkPageAnswer[2]-49749;
			$control->client->query('GetDetailedPlayerInfo', $player_list[$player_list_pid]['Login']);
			$warnedplayer = $control->client->getResponse();
			$color_warn = $settings['Color_Warn'];
			
			$control->client->query('ChatSendServerMessage', $color_warn.'»'.$Admin_Rank.' $fff'.$CommandAuthor['NickName'].'$z$s '.$color_warn.'warned $fff'.$warnedplayer['NickName'].'$z$s '.$color_warn.'!');
			$control->client->query('SendDisplayManialinkPageToLogin', $warnedplayer['Login'], '<?xml version="1.0" encoding="UTF-8" ?>
			<manialink id="4000">
				<quad posn="-64 48 15" sizen="128 96" bgcolor="0006"/>
				<quad posn="0 15 18" sizen="40 21" valign="center" halign="center" style="Bgs1InRace" substyle="NavButtonBlink"/>
				<quad posn="0 15 17" sizen="40 21" valign="center" halign="center" style="Bgs1InRace" substyle="BgList"/>
				<quad posn="0 24.5 19" sizen="39 2.5" halign="center" style="BgsPlayerCard" substyle="BgActivePlayerScore"/>
				<label posn="0 24.25 20" textsize="2" halign="center" text="$o$f00WARNING!"/>
				<label posn="-18 20.75 20" textsize="2" sizen="36 2" autonewline="1" text="This is an administrator warning!'.nz.'What ever you wrote or made is against our server rights.'.nz.'An administrator or Operator can kick or ban you next time!'.nz.'Be fair."/>
				<quad posn="15.75 24.5 20" sizen="2.5 2.5" style="Icons64x64_1" substyle="Close" action="4000"/>
			</manialink>', 0, false);
			//$control->show_playerlist($CommandAuthor['Login'], true, 0);
		}
		else $control->client->query('ChatSendServerMessageToLogin', '$f00»'.$text_false_rights, $CommandAuthor['Login']);
		}
		elseif($ManialinkPageAnswer[2]>=50000 AND $ManialinkPageAnswer[2]<=50250){
			if($ban==true){
				$control->client->query('GetPlayerList', 300, 0);
				$player_list = $control->client->getResponse();
				$player_list_pid = $ManialinkPageAnswer[2]-50000;
				$control->client->query('GetDetailedPlayerInfo', $player_list[$player_list_pid]['Login']);
				$bannedplayer = $control->client->getResponse();
				$control->client->query('ChatSendServerMessage', $color_ban.'»'.$Admin_Rank.' $fff'.$CommandAuthor['NickName'].'$z$s '.$color_ban.'banned $fff'.$bannedplayer['NickName'].$color_ban.' !');
				$control->client->query('Ban', $bannedplayer['Login']);
			}
			else $control->client->query('ChatSendServerMessageToLogin', '$f00»'.$text_false_rights, $Command[1]);
		}
		*/
		
		/*   FOR PLUGIN PLUGIN.PLAYERS.PHP !!   */
		
		//Normal players
		/*elseif($ManialinkPageAnswer[2] >= 5251 AND $ManialinkPageAnswer[2] <= 5270){
			//
			$id = $ManialinkPageAnswer[2] - 5251;
			$id = $id * 14;
			$nextid = $ManialinkPageAnswer[2] + 1;
			$previd = $ManialinkPageAnswer[2] - 1;
			$control->client->query('GetPlayerList', 300, 0);
			$player_list = $control->client->getResponse();
			$curr_pid = $id;
			$curr_pid2 = 0;
			$curr_y = '20';
			$playerarray = array();
			while(isset($player_list[$curr_pid])){
				
				$curr_ml_id = $playerlist_mlid+$curr_pid;
				$curr_pdata = $player_list[$curr_pid];
				$curr_nick = $curr_pdata['NickName'];
				$curr_login = $curr_pdata['Login'];
				$curr_ladder = $curr_pdata['LadderRanking'];
				
				$url = "http://fox-control.de/~skpfox/scripts/get_data.php?login=".trim($curr_login).""; 
				$file = fopen($url, "rb");
				$pl_content = stream_get_contents($file);
				$file = fclose($file);
				$pl_content = explode('{expl}', $pl_content);
				$playerarray[] = '<label posn="-34 '.$curr_y.' 4" text="'.htmlspecialchars($curr_nick).'" sizen="15 2" textsize="2"/>
				<label posn="-13 '.$curr_y.' 4" text="'.htmlspecialchars($curr_login).'" sizen="10 2" textsize="2"/>
				<label posn="1 '.$curr_y.' 4" text="'.$curr_ladder.'" sizen="10 2" textsize="2"/>
				<label posn="12 '.$curr_y.' 4" text="SKP:" textsize="2"/>
				<label posn="16 '.$curr_y.' 4" text="'.$pl_content[0].'" textsize="2" sizen="15 2"/>
				<label posn="23 '.$curr_y.' 4" text="LVL:" textsize="2"/>
				<label posn="26.5 '.$curr_y.' 4" text="'.$pl_content[1].'" textsize="2" sizen="15 2"/>
				<quad posn="-3.25 '.$curr_y.' 4" sizen="3 2.5" style="Icons128x128_1" substyle="LadderPoints"/>';
				if($curr_pid2==13) break;
				$curr_pid++;
				$curr_pid2++;
				$curr_y = $curr_y-2.5;
			}
			if(isset($playerarray[14])) $nextarrow = '<quad posn="31.75 -12 20" sizen="3 3" style="Icons64x64_1" substyle="ArrowNext" action="'.$nextid.'"/>';
			else $nextarrow = '';
			if($id!==0) $prevarrow = '<quad posn="-34.75 -12 20" sizen="3 3" style="Icons64x64_1" substyle="ArrowPrev" action="'.$previd.'"/>';
			else $prevarrow = '';
			$control->client->query('SendDisplayManialinkPageToLogin', $ManialinkPageAnswer[1], '<?xml version="1.0" encoding="UTF-8" ?>
			<manialink id="10">
			<quad posn="0 5 1" sizen="70 41" valign="center" halign="center" style="Bgs1InRace" substyle="NavButtonBlink"/>
			<quad posn="0 5 0" sizen="70 41" valign="center" halign="center" style="Bgs1InRace" substyle="BgList"/>
			<quad posn="0 24.5 3" sizen="69 2.5" halign="center" style="BgsPlayerCard" substyle="BgActivePlayerScore"/>
			<label posn="-34 24.25 4" textsize="2" text="$o$09fCurrent Players:"/>
			<quad posn="31.75 24.5 4" sizen="2.5 2.5" style="Icons64x64_1" substyle="Close" action="11"/>
			'.$playerarray[0].'
			'.$playerarray[1].'
			'.$playerarray[2].'
			'.$playerarray[3].'
			'.$playerarray[4].'
			'.$playerarray[5].'
			'.$playerarray[6].'
			'.$playerarray[7].'
			'.$playerarray[8].'
			'.$playerarray[9].'
			'.$playerarray[10].'
			'.$playerarray[11].'
			'.$playerarray[12].'
			'.$playerarray[13].'
			'.$nextarrow.'
			'.$prevarrow.'
			</manialink>', 0, false);
		}
		//Admins
		elseif($ManialinkPageAnswer[2] >= 5271 AND $ManialinkPageAnswer[2] <= 5290){
			$id = $ManialinkPageAnswer[2] - 5271;
			$id = $id * 14;
			$nextid = $ManialinkPageAnswer[2] + 1;
			$previd = $ManialinkPageAnswer[2] - 1;
			$curr_pid = $id;
			$curr_pid2 = 0;
			$curr_y = '20';
			$playerarray = array();
			$control->client->query('GetIgnoreList', 1000, 0);
			$ignore_list = $control->client->getResponse();
			$control->client->query('GetPlayerList', 300, 0);
			$player_list = $control->client->getResponse();
			while(isset($player_list[$curr_pid])){
				
				$curr_ml_id = $playerlist_mlid+$curr_pid;
				$curr_pdata = $player_list[$curr_pid];
				$curr_nick = $curr_pdata['NickName'];
				$curr_login = $curr_pdata['Login'];
				$curr_kick_id = 250+$curr_pid;
				$curr_ignore_id = 0;
				$curr_warn_id = 49749+$curr_pid;
				$curr_ban_id = 50000+$curr_pid;
				$curr_y_2 = $curr_y-0.25;
				$player_in_ignore_list = false;
				
				while(isset($ignore_list[$curr_ignore_id])){
					if($ignore_list[$curr_ignore_id]['Login'] == trim($curr_login)){
						$player_in_ignore_list = true;
						break;
					}
					$curr_ignore_id++;
				}
				if($player_in_ignore_list==true){
					$curr_ignore_text = 'UnIgnore';
				}
				else{
					$curr_ignore_text = 'Ignore';
				}
				$curr_ignore_id = 500+$curr_pid;
				
				$playerarray[] = '<label posn="-34 '.$curr_y.' 4" text="'.htmlspecialchars($curr_nick).'" sizen="15 2" textsize="2"/>
				<label posn="-13 '.$curr_y.' 4" text="'.htmlspecialchars($curr_login).'" sizen="10 2" textsize="2"/>
				<quad posn="0 '.$curr_y.' 4" sizen="7 2.5" style="Bgs1" substyle="NavButtonBlink" action="'.$curr_kick_id.'"/>
				<label posn="3.5 '.$curr_y_2.' 4" sizen="7 2.5" halign="center" style="TextPlayerCardName" textsize="2" text="$fff$oKick" action="'.$curr_kick_id.'"/>
				<quad posn="8 '.$curr_y.' 4" sizen="7 2.5" style="Bgs1" substyle="NavButtonBlink" action="'.$curr_ignore_id.'"/>
				<label posn="11.5 '.$curr_y_2.' 4" sizen="7 2.5" halign="center" style="TextPlayerCardName" textsize="2" text="$fff$o'.$curr_ignore_text.'" action="'.$curr_ignore_id.'"/>
				<quad posn="16 '.$curr_y.' 4" sizen="7 2.5" style="Bgs1" substyle="NavButtonBlink" action="'.$curr_warn_id.'"/>
				<label posn="19.5 '.$curr_y_2.' 4" sizen="7 2.5" halign="center" style="TextPlayerCardName" textsize="2" text="$fff$oWarn" action="'.$curr_warn_id.'"/>
				<quad posn="24 '.$curr_y.' 4" sizen="7 2.5" style="Bgs1" substyle="NavButtonBlink" action="'.$curr_ban_id.'"/>
				<label posn="27.5 '.$curr_y_2.' 4" sizen="7 2.5" halign="center" style="TextPlayerCardName" textsize="2" text="$fff$oBan" action="'.$curr_ban_id.'"/>';
				if($curr_pid2==13) break;
				$curr_pid++;
				$curr_pid2++;
				$curr_y = $curr_y-2.5;
			}
			if(isset($playerarray[14])) $nextarrow = '<quad posn="31.75 -12 4" sizen="3 3" style="Icons64x64_1" substyle="ArrowNext" action="'.$nextid.'"/>';
			else $nextarrow = '';
			if($id!==0) $prevarrow = '<quad posn="-34.75 -12 4" sizen="3 3" style="Icons64x64_1" substyle="ArrowPrev" action="'.$previd.'"/>';
			else $prevarrow = '';
			$control->client->query('SendDisplayManialinkPageToLogin', $ManialinkPageAnswer[1], '<?xml version="1.0" encoding="UTF-8" ?>
			<manialink id="10">
			<quad posn="0 5 1" sizen="70 41" valign="center" halign="center" style="Bgs1InRace" substyle="NavButtonBlink"/>
			<quad posn="0 5 0" sizen="70 41" valign="center" halign="center" style="Bgs1InRace" substyle="BgList"/>
			<quad posn="0 24.5 3" sizen="69 2.5" halign="center" style="BgsPlayerCard" substyle="BgActivePlayerScore"/>
			<label posn="-34 24.25 4" textsize="2" text="$o$09fCurrent Players:"/>
			<quad posn="31.75 24.5 4" sizen="2.5 2.5" style="Icons64x64_1" substyle="Close" action="11"/>
			'.$playerarray[0].'
			'.$playerarray[1].'
			'.$playerarray[2].'
			'.$playerarray[3].'
			'.$playerarray[4].'
			'.$playerarray[5].'
			'.$playerarray[6].'
			'.$playerarray[7].'
			'.$playerarray[8].'
			'.$playerarray[9].'
			'.$playerarray[10].'
			'.$playerarray[11].'
			'.$playerarray[12].'
			'.$playerarray[13].'
			'.$nextarrow.'
			'.$prevarrow.'
			</manialink>', 0, false);
		}

	}

}*/
?>