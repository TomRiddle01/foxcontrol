<?php
//* plugin.donate.php - Donate
//* Version:   0.4
//* Coded by:  matrix142, cyril, libero
//* Copyright: FoxRace, http://www.fox-control.de

global $bills, $donate1, $donate2, $donate3, $donate4, $donate5;
$bills = array();
$donate1 = 50;
$donate2 = 250;
$donate3 = 500;
$donate4 = 1000;
$donate5 = 3000;

class plugin_donate extends FoxControlPlugin {

	public function onStartUp () {
		$this->name = 'Donate';
		$this->author = 'matrix142 & cyril & libero';
		$this->version = '0.4';
		
		$this->registerMLIds(6);
		
		$this->displayDonatePanel();
	}
	
	public function onPlayerConnect ($args) {
		$this->displayDonatePanel($args['Login']);
	}
	
	public function onBeginChallenge ($args) {
		$this->displayDonatePanel();
	}
	
	public function onEndchallenge($args){
		$this->displayDonatePanel(false, array(0 => '42.25', 1 => '-80'));
	}
	
	public function displayDonatePanel ($login = false, $posn = array(0 => '0', 1 => '0')) {
		global $bills, $donate1, $donate2, $donate3, $donate4, $donate5, $settings;
	
		$code = '
		<frame posn="'.$posn[0].' '.$posn[1].' 1">
		<quad posn="-62 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[1].'" />
		<quad posn="-54 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[2].'" />
		<quad posn="-46 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[3].'" />
		<quad posn="-38 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[4].'" />
		<quad posn="-30 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[5].'" />
		<quad posn="-62 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[1].'" />
		<quad posn="-54 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[2].'" />
		<quad posn="-46 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[3].'" />
		<quad posn="-38 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[4].'" />
		<quad posn="-30 50 1" sizen="7.5 5" style="'.$settings['default_style2'].'" substyle="'.$settings['default_substyle2'].'" action="'.$this->mlids[5].'" />
		<label posn="-58.25 46.75 2" scale="0.8" halign="center" valign="center" text="$o$fff'.$donate1.'" style="TextCardSmallScores2Rank" action="'.$this->mlids[1].'"/>
		<label posn="-50.25 46.75 2" scale="0.8" halign="center" valign="center" text="$o$fff'.$donate2.'" style="TextCardSmallScores2Rank" action="'.$this->mlids[2].'"/>
		<label posn="-42.25 46.75 2" scale="0.8" halign="center" valign="center" text="$o$fff'.$donate3.'" style="TextCardSmallScores2Rank" action="'.$this->mlids[3].'"/>
		<label posn="-34.25 46.75 2" scale="0.8" halign="center" valign="center" text="$o$fff'.$donate4.'" style="TextCardSmallScores2Rank" action="'.$this->mlids[4].'"/>
		<label posn="-26.25 46.75 2" scale="0.8" halign="center" valign="center" text="$o$fff'.$donate5.'" style="TextCardSmallScores2Rank" action="'.$this->mlids[5].'"/>
		</frame>';
	
		if($login != false){
			$this->displayManialinkToLogin($login, $code, $this->mlids[0]);
		}else{
			$this->displayManialink($code, $this->mlids[0]);
		}
	}

	public function onManialinkPageAnswer($args){
		global $bills, $donate1, $donate2, $donate3, $donate4, $donate5;

		if($args[2] == $this->mlids[1]){
			$this->instance()->client->query('SendBill', $args[1], $donate1, '$0f0Do you want donate $fff'.$donate1.'$0f0 Planets?$z', '');
			$billid = $this->instance()->client->getResponse();
			$bills[] = array($args[1], $donate1, $billid);
		}
		
		else if($args[2] == $this->mlids[2]){
			$this->instance()->client->query('SendBill', $args[1], $donate2, '$0f0Do you want donate $fff'.$donate2.'$0f0 Planets?$z', '');
			$billid = $this->instance()->client->getResponse();
			$bills[] = array($args[1], $donate2, $billid);
		}
		
		elseif($args[2] == $this->mlids[3]){
			$this->instance()->client->query('SendBill', $args[1], $donate3, '$0f0Do you want donate $fff'.$donate3.'$0f0 Planets?$z', '');
			$billid = $this->instance()->client->getResponse();
			$bills[] = array($args[1], $donate3, $billid);
		}

		elseif($args[2] == $this->mlids[4]){
			$this->instance()->client->query('SendBill', $args[1], $donate4, '$0f0Do you want donate $fff'.$donate4.'$0f0 Planets?$z', '');
			$billid = $this->instance()->client->getResponse();
			$bills[] = array($args[1], $donate4, $billid);
		}
		elseif($args[2] == $this->mlids[5]){
			$this->instance()->client->query('SendBill', $args[1], $donate5, '$0f0Do you want donate $fff'.$donate5.'$0f0 Planets?$z', '');
			$billid = $this->instance()->client->getResponse();
			$bills[] = array($args[1], $donate5, $billid);
		}
	}

	public function onBillUpdated($BillId){
		global $bills, $donate1, $donate2, $donate3, $donate4, $donate5;
	
		$billid = $BillId[0];
	
		$curr_id = 0;
		$billid_is_don = false;
		while(isset($bills[$curr_id])){
			$b_curr_data = $bills[$curr_id];
			if($b_curr_data[2]==$billid){
				$billid_is_don = true;
				break;
			}
			$curr_id++;
		}
	
		if($billid_is_don==true){
			$billarray = $bills[$curr_id];
	
			$billlogin = $billarray[0];
			$billcoppers = $billarray[1];
			$this->instance()->client->query('GetDetailedPlayerInfo', $billlogin);
			$billpdata = $this->instance()->client->getResponse();
	
	
			if($BillId[1]=='4'){
				global $db;
				$this->chat('$fff'.$billpdata['NickName'].'$z$s$0f0 donated $fff'.$billcoppers.'$0f0 Planets! Thank you!');
				$sql = "SELECT * FROM players WHERE playerlogin='".trim($billlogin)."'";
				if($mysql = mysqli_query($db, $sql)){
					if($donsdata = $mysql->fetch_object()){
						$dons = $donsdata->donations;
						$dons = $dons+$billcoppers;
						$sql = "UPDATE players SET donations='".$dons."' WHERE playerlogin='".trim($billlogin)."'";
						if($mysql = mysqli_query($db, $sql)){
							$updated = true;
						}
					}
				}
			}
			elseif($BillId[1]=='5'){
				$this->chatToLogin($billlogin, '$f00Transaction refused!');
			}
			elseif($BillId[1]=='6'){
				$this->chatToLogin($billlogin, '$f00Transaction error!');
			}
		}
	}
}
?>