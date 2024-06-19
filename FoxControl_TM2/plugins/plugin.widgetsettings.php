<?php
//* plugin.widgetsettings.php - Widgetsettings
//* Version:   0.5
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_widgetsettings extends FoxControlPlugin {
	public function onStartUp() {
		$this->registerMlids(100);
		$this->registerCommand('widgets', 'Manage your widgets', false);
	}

	public function onCommand($args) {
		if($args[2] == 'widgets') {
			$this->showWidgetList($args[1]);
		}
	}
	
	public function showWidgetList($login) {
		$classWidget = $this->getPluginInstance('widget');
		$widgets = $classWidget->getWidgetList();
			
		$window = $this->window;
			
		$window->init();
		$window->title('Widget Settings');
		$window->displayAsTable(true);
		$window->size(70, '');
		$window->posY('40');
			
		$window->addButton('', '15.5', false);
		$window->addButton('Close', '10', true);
		$window->addButton('', '15.5', false);
			
		$window->content('<td width="35">$iName</td><td width="35">$iOptions</td>');
			
		$noWidget = true;
		$id = 0;
		foreach($widgets as $key => $value) {				
			if($classWidget->userSettings[$login][$key]['Enabled'] == false) {
				$actionName = 'Enable';
			} else {
				$actionName = 'Hide';
			}
				
			$actionID1 = $this->mlids[0] + $key;
			$actionID2 = $this->mlids[50] + $key;
					
			if($widgets[$key]['Removed'] != true) {
				$noWidget = false;
			
				$window->content('<td width="35">'.$widgets[$key]['Title'].'</td><td width="6" id="'.$actionID1.'" align="center">'.$actionName.'</td><td width="6" id="'.$actionID2.'" align="center">Settings</td>');
			}
				
			$id++;
		}
			
		if($noWidget == true) {
			$window->content('<td width="35">No Widgets available</td>');
		}
			
		$window->show($login);
	}
	
	public function onManialinkPageAnswer($args) {
		//Enable/Hide Widget
		if($args[2] >= $this->mlids[0] && $args[2] < $this->mlids[50]) {
			$classWidget = $this->getPluginInstance('widget');
			$widgetID = $args[2] - $this->mlids[0];
				
			if($classWidget->userSettings[$args[1]][$widgetID]['Enabled'] == false) {
				$classWidget->onManialinkPageAnswer(array(1 => $args[1], 2 => 'widget:open:'.$widgetID.''));
			} else {
				$classWidget->onManialinkPageAnswer(array(1 => $args[1], 2 => 'widget:close:'.$widgetID.''));
			}
				
			$this->showWidgetList($args[1]);
		}
		//Edit Widget
		else if($args[2] >= $this->mlids[50] && $args[2] < $this->mlids[99]) {
			$classWidget = $this->getPluginInstance('widget');
			$widgetID = $args[2] - $this->mlids[50];
				
			$classWidget->editWidget($args[1], $widgetID);
		}
	}
}