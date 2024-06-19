<?php
//* plugin.currentchallenge.php - A Window with the current Challenge
//* Version:   0.6
//* Coded by:  cyrilw, libero6
//* Copyright: FoxRace, http://www.fox-control.de

class plugin_currentchallenge extends FoxControlPlugin {
	public $config;

	public function onStartUp() {
		$this->name = 'Current Challenge';
		$this->author = 'cyril & libero';
		$this->version = '0.5';
	
		$this->registerMLIds(1);
		
		$this->config = $this->loadConfig();
		
		$this->displayChallenge();
	}
	
	public function onBeginChallenge($args) {
		global $settings;

		$color_newchallenge = str_replace('$', '', $settings['Color_NewChallenge']);
		
		$this->chat('New map: $fff'.$args[0]['Name'].'$z$s $'.$color_newchallenge.'by $fff'.$args[0]['Author'], $color_newchallenge);
		
		console('New map: '.$args[0]['Name']);
		
		$this->displayChallenge();
	}
	
	public function onPlayerConnect($args) {
		$this->displayChallenge($args['Login']);
	}
	
	public function onEndChallenge($args) {
		$this->closeMl($this->mlids[0]);
	}
	
	public function displayChallenge($login = false) {
		global $settings;

		$posn = explode(' ', $this->config->posn);
		
		$this->instance()->client->query('GetCurrentMapInfo');
		$currentChallenge = $this->instance()->client->getResponse();
		
		$this->instance()->client->query('GetNextMapInfo');
		$nextChallenge = $this->instance()->client->getResponse();
		
		$code = '
		<frame posn="'.$posn[0].' '.$posn[1].'" id="currentChallenge">
			<quad id="arrowPrev" posn="0 0 0" sizen="20 9" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" ScriptEvents="1" />
			<quad posn="0 0 1" sizen="20 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'"/>
			<quad posn="0 0 1" sizen="20 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'"/>
			<label posn="0.5 -0.7 2" textsize="1" text="$o'.$this->config->settings->names->headline_current_challenge.'"/>
			<quad posn="1 -3 1" sizen="2 2" style="Icons128x128_1" substyle="NewTrack"/>
			<label posn="3 -3 1" textsize="1" sizen="15 2" text="'.$currentChallenge['Name'].'"/>
			<quad posn="1 -5 1" sizen="2 2" style="Icons64x64_1" substyle="Buddy"/>
			<label posn="3 -5 1" textsize="1" sizen="15 2" text="'.$currentChallenge['Author'].'"/>
			<quad posn="1 -7 1" sizen="2 2" style="Icons64x64_1" substyle="RestartRace"/>
			<label posn="3 -7 1" textsize="1" text="'.$this->instance()->format_time($currentChallenge['AuthorTime']).'"/>
		</frame>
		
		<frame posn="'.$posn[0].' '.$posn[1].'" id="nextChallenge">
			<quad id="arrowNext" posn="0 0 0" sizen="20 9" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" ScriptEvents="1" />
			<quad posn="0 0 1" sizen="20 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'"/>
			<quad posn="0 0 1" sizen="20 3" style="'.$settings['default_style1'].'" substyle="'.$settings['default_substyle1'].'"/>
			<label posn="0.5 -0.7 2" textsize="1" text="$o'.$this->config->settings->names->headline_next_challenge.'"/>
			<quad posn="1 -3 1" sizen="2 2" style="Icons128x128_1" substyle="NewTrack"/>
			<label posn="3 -3 1" textsize="1" sizen="15 2" text="'.$nextChallenge['Name'].'"/>
			<quad posn="1 -5 1" sizen="2 2" style="Icons64x64_1" substyle="Buddy"/>
			<label posn="3 -5 1" textsize="1" sizen="15 2" text="'.$nextChallenge['Author'].'"/>
			<quad posn="1 -7 1" sizen="2 2" style="Icons64x64_1" substyle="RestartRace"/>
			<label posn="3 -7 1" textsize="1" text="'.$this->instance()->format_time($nextChallenge['AuthorTime']).'"/>
		</frame>
	
		<script><!--
			#Include "MathLib" as MathLib

			Void Initialize() {	
				declare Boolean startAnimation for Page = False;
				declare Text animationDirection for Page = "Left";
				declare Real startPosnCurr for Page = Page.GetFirstChild("currentChallenge").PosnX;
				declare Real startPosnNext for Page = Page.GetFirstChild("nextChallenge").PosnX;
			
				(Page.GetFirstChild("nextChallenge") as CGameManialinkFrame).Hide();
			}

			Void Animate() {
				declare Text animationDirection for Page;
				declare Boolean startAnimation for Page;
				declare Real startPosnCurr for Page;
				declare Real startPosnNext for Page;
				
				if(animationDirection == "Left") {
					if((Page.GetFirstChild("nextChallenge").PosnX) <= (startPosnCurr - 49)) {
						startAnimation = False;
					}
				
					if(startAnimation == True) {
						Page.GetFirstChild("nextChallenge").PosnX = (Page.GetFirstChild("nextChallenge").PosnX) - 2;
					}
				} else if(animationDirection == "Right") {
					if((Page.GetFirstChild("nextChallenge").PosnX) >= startPosnNext) {
						startAnimation = False;
						
						(Page.GetFirstChild("nextChallenge") as CGameManialinkFrame).Hide();
					}
				
					if(startAnimation == True) {
						Page.GetFirstChild("nextChallenge").PosnX = (Page.GetFirstChild("nextChallenge").PosnX) + 2;
					}
				}
			}

			main() {
				Initialize();				
				while(True) {
					Animate();
        
					declare Boolean startAnimation for Page;
					declare Text animationDirection for Page;
		
					foreach(Event in PendingEvents) {
						switch(Event.Type) {
							case CGameManialinkScriptEvent::Type::MouseClick: {
								if(Event.ControlId == "arrowNext") {
									startAnimation = True;
									animationDirection = "Right";
								}
								else if(Event.ControlId == "arrowPrev") {									
									(Page.GetFirstChild("nextChallenge") as CGameManialinkFrame).Show();
								
									startAnimation = True;
									animationDirection = "Left";
								}
							}
						}
					}

					yield;
				}
			}
		--></script>';
		
		if($login === false) {
			$this->displayManialink($code, $this->mlids[0]);
		} else {
			$this->displayManialinkToLogin($login, $code, $this->mlids[0]);
		}
	}
}
?>