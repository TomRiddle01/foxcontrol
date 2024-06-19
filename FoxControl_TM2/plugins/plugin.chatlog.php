<?php
/*****************PLUGIN******************
********************IS********************
*****************OUTDATED*****************
******************************************
******************DO NOT******************
******************USE IT*****************/

//* plugin.chatlog.php - Chat Log
//* Version:   0.3
//* Coded by:  cyrilw
//* Copyright: FoxRace, http://www.fox-control.de

control::RegisterEvent('Chat', 'chatlog_chat');
control::RegisterEvent('ManialinkPageAnswer', 'chatlog_mlanswer');
global $chatlog_writeconsole, $chatlog_writeall;

////////////////////
///   Settings   ///
////////////////////

/*      BEGIN      */
//Set true, if you want the Chatlog in the Console-Log
$chatlog_writeconsole = true;

//Set false, if you only want the chatmessages of the player, set true, if you want also the server messages
$chatlog_writeall = true;

/*       END       */




global $chatlog;
$chatlog = array();

function chatlog_chat($control, $PlayerChat){
	global $chatlog, $chatlog_writeconsole, $chatlog_writeall;
	$check_chat = substr($PlayerChat[2], 0, 1);
	if($check_chat!=='/'){
		$control->client->query('GetDetailedPlayerInfo', $PlayerChat[1]);
		$ChatAuthor = $control->client->getResponse();
		$control->client->query('GetPlayerList', 300, 0);
		$player_list = $control->client->getResponse();
		$curr_id = 0;
		$noservermessage = false;
		while(isset($player_list[$curr_id])){
			if($player_list[$curr_id]['Login']==$ChatAuthor['Login']){
				$noservermessage = true;
				break;
			}
			$curr_id++;
		}
		if($noservermessage==true) $chatlog[] = array('Chat' => $PlayerChat[2], 'NickName' => $ChatAuthor['NickName']);
		if($noservermessage==false AND $chatlog_writeall==true) console('[Chat] Server| '.$control->rgb_decode($PlayerChat[2]));
		elseif($noservermessage==true) console('[Chat] '.$ChatAuthor['Login'].'| '.$control->rgb_decode($PlayerChat[2]));
	}
	if($PlayerChat[2]=='/chatlog'){
		$Manialinkp = array();
		$Manialinkp[1] = $PlayerChat[1];
		$Manialinkp[2] = 3050;
		if(function_exists('chatlog_mlanswer')) chatlog_mlanswer($control, $Manialinkp);
	}
}

function chatlog_mlanswer($control, $ManialinkPageAnswer){
	global $chatlog;
	if($ManialinkPageAnswer[2]=='3050'){
		$chatline = 0;
		$chatlines = count($chatlog);
		if($chatlines >= 24) $chatline = $chatlines - 24;
		$chatlogml = '<quad posn="0 12 21" sizen="70 57" valign="center" halign="center" style="Bgs1InRace" substyle="NavButtonBlink"/>
		<quad posn="0 12 20" sizen="70 57" valign="center" halign="center" style="Bgs1InRace" substyle="BgList"/>
		<quad posn="0 39.5 23" sizen="69 2.5" halign="center" style="BgsPlayerCard" substyle="BgActivePlayerScore"/>
		<label posn="-34 39.25 24" textsize="2" text="$o$FFFChatLog:"/>
		<quad posn="31.75 39.5 24" sizen="2.5 2.5" style="Icons64x64_1" substyle="Close" action="3054"/>';
		$y = 36;
		while(isset($chatlog[$chatline])){
			$chatlogml .= '<label posn="-33 '.$y.' 24" textsize="2" sizen="10 2" text="'.htmlspecialchars($chatlog[$chatline]['NickName']).'"/>';
			$chatlogml .= '<label posn="-22 '.$y.' 24" textsize="2" sizen="55 2" text="'.str_replace("\n", '', htmlspecialchars($chatlog[$chatline]['Chat'])).'"/>';
			$y = $y-2;
			$chatline++;
		}
		if($chatlines == 0) $chatlogml .= '<label posn="0 36 24" textsize="2" align="center" text="$o$fffNo chat message!"/>';
		$control->display_manialink_to_login($chatlogml, 3050, 0, false, $ManialinkPageAnswer[1]);
	}
	elseif($ManialinkPageAnswer[2]=='3054'){
		$control->close_ml(3050, $ManialinkPageAnswer[1]);
	}
}


?>