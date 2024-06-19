<?php
//* plugin.search.php - Search
//* Version:   0.5
//* Coded by:  cyrilw, matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_search extends FoxControlPlugin {
	public function onStartUp() {
		$this->name = 'Plugin Search';
		$this->author = 'matrix142';
		$this->version = '0.5';
		
		$this->registerCommand('search', 'Search various things. Type in $s/search help$s for further information', false);
	}
	
	public function onCommand($args) {
		if($args[2] == 'search' && isset($args[3][0])) {
			//HELP
			if($args[3][0] == 'help') {
				$window = $this->window;
				$window->init();
				$window->title('Help - Command: search');
				$window->close(true);
					
				$window->size(70, '');
				$window->posY('40');
					
				$window->content('You can search different things with the $s/search$s command:');
				$window->content('$o/search maps <searchterm>$o Searches a Map');
					
				$window->addButton('OK', 15, true);
					
				$window->show($args[1]);
			}
		
			//SEARCH MAPS
			if($args[3][0] == 'maps') {
				$pluginChallenges = $this->getPluginInstance('plugin_challenges');
				
				if($pluginChallenges !== false) {
					$pluginChallenges->onCommand(array(1 => $args[1], 2 => $args[3][0], 3 => array(0 => $args[2], 1 => $args[3][1])));
				}
			}
		}
	}
}
?>