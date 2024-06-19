<?php
//* chat.player.php - Player Chat Commands
//* Version:   0.4
//* Coded by:  libero6, cyrilw
//* Copyright: FoxRace, http://www.fox-control.de

class chat_player extends FoxControlPlugin {
	public $commandsPerPage = 17;
	public $helpUsers = array();
	
	public function onStartUp() {
		//Register Chat Commands
		$this->registerCommand('afk', 'Sets you in the AFK-mode', false);
		$this->registerCommand('lol', 'Displays $sLooOOooL$s message', false);
		$this->registerCommand('brb', 'Displays $sBe Right Back$s message', false);
		$this->registerCommand('gga', 'Displays $sGood Game All$s message', false);
		$this->registerCommand('gg', 'Displays $sGood Game$s message', false);
		$this->registerCommand('fox', 'Displays $sFox RulZzz$s message', false);
		$this->registerCommand('help', false, false);
		$this->registerCommand('me', 'Emotical chat message', false);
		$this->registerCommand('help', false, false);
		$this->registerCommand('ping', 'Shows your ping', false);
		$this->registerMLIds(1);
		
		//Set general Plugin information
		$this->name = 'Player Chat';
		$this->author = 'Cyril & Libero';
		$this->version = '0.4';
	}
	public function onCommand($args) {
		global $settings;
	
		//Get Player Infos
		$this->instance()->client->query('GetDetailedPlayerInfo', $args[1]);
		$CommandAuthor = $this->instance()->client->getResponse();
		
		//AFK
		if($args[2] == 'afk') {
			$this->instance()->chat_with_nick('$09fA$fffway $09fF$fffrom $09fK$fffeyboard', $CommandAuthor['Login']);
			$this->instance()->client->query('ForceSpectator', $CommandAuthor['Login'], 1);
			$this->instance()->client->query('ForceSpectator', $CommandAuthor['Login'], 0);
			
			$this->displayManialinkToLogin($CommandAuthor['Login'], '<quad posn="0 -27 1" sizen="25 4" halign="center" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[0].'" /><label posn="0 -28 2" halign="center" style="TextPlayerCardName" text="$o$fffClick here to play!" action="'.$this->mlids[0].'" />', $this->mlids[0]);
		
		//LOL
		} else if($args[2] == 'lol') {
			$this->instance()->chat_with_nick('$09fL$fffo$09f0$fffo$09fL', $CommandAuthor['Login']);
		
		//BRB
		} else if($args[2] == 'brb') {
			$this->instance()->chat_with_nick('$09fB$fffe $09fR$fffight $09fB$fffack', $CommandAuthor['Login']);
		
		//GGA
		} else if($args[2] == 'gga') {
			$this->instance()->chat_with_nick('$09fG$fffood $09fG$fffame $09fA$fffll', $CommandAuthor['Login']);
		
		//GG
		} else if($args[2] == 'gg') {
			$this->instance()->chat_with_nick('$09fG$fffood $09fG$fffame', $CommandAuthor['Login']);
		
		//FOX
		} else if($args[2] == 'fox') {
			$this->instance()->chat_with_nick('$09fF$fffOX $09fR$fffulZzz!', $CommandAuthor['Login']);
		
		//HELP
		} else if($args[2] == 'help') {
			if(!empty($args[3][0])) $site = ($args[3][0]-1);
			else $site = 0;
			$this->helpUsers[$args[1]] = $site;
			
			$window = $this->window;
			$window->init();
			$window->title('Help');
			$window->close(true);
			$window->displayAsTable(true);
			$window->size(70, '');
			$window->posY('40');
			$window->target('onButtonPressed', $this);
			
			$window->content('<td width="15">Command</td><td width="2"></td><td width="50">Description</td>');
			$window->content(' ');
			
			$help = $this->instance()->getCommands('player');
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
			
		//ME
		} else if($args[2] == 'me') {
			$message = '';
			for($i = 0; isset($args[3][$i]); $i++)
			{
				$message = $message.$args[3][$i].' ';
			}
			$message = $this->instance()->rgb_decode($message);
			$this->chat('$i$fff'.$CommandAuthor['NickName'].'$z$i$s$fff  '.$message, 'fff', false);
		} else if($args[2] == 'ping') { //ping
			$this->chatToLogin($args[1], 'Pong!');
		}
	}
	
	public function onManialinkPageAnswer($args) {
		if($args[2] == $this->mlids[0]) {
			$this->instance()->client->query('ForceSpectator', $args[1], 2);
			$this->instance()->client->query('ForceSpectator', $args[1], 0);
			$this->closeMl($this->mlids[0], $args[1]);
			
			$this->instance()->chat_with_nick('$09fI$fff\'m $09fB$fffack', $args[1]);
		}
	}
	
	public function onButtonPressed($args) {
		if($args[2] == 1) { //<
			$newargs = array(1 => $args[1], 2 => 'help', 3 => array(0 => $this->helpUsers[$args[1]]));
			$this->onCommand($newargs);
		} else if($args[2] == 3) { //>
			$newargs = array(1 => $args[1], 2 => 'help', 3 => array(0 => ($this->helpUsers[$args[1]] + 2)));
			$this->onCommand($newargs);
		}
	}
}
?>