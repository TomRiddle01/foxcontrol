<?php
/*FUNCTIONS*/
function playerconnect($connected_player){
	$this->client->query('GetDetailedPlayerInfo', $cbdata[0]);
	$connectedplayer = $this->client->getResponse();
	if($player_message_connect==true) $this->client->query('ChatSendServerMessage', '$07bNew Player $fff'.$connectedplayer['NickName'].'$z$s$07b from $fff'.str_replace('World|', '', $connectedplayer['Path']).' $07bconnected!');
	$this->client->query('ChatSendServerMessageToLogin', '$fffWellcome '.$connectedplayer['NickName'].'$z$fff$s on '.$servername.$newline.'$z$s$fffThis Server running with $o$09fFOX Control$z'.$newline.'$fff$s$oHave fun!', $cbdata[0]);  
	echo 'New Player ' . $cbdata[0]  . ' connected! IP: '.$connectedplayer['IPAddress'].'' . nz;
	$sql = "SELECT * FROM `FoxControl`.`players` WHERE playerlogin = '".$connectedplayer['Login']."'";
	$mysql = mysql_query($sql);
	if(!mysql_fetch_object($mysql)){
	$sql = "INSERT INTO `FoxControl`.`players` (id, playerlogin, nickname, lastconnect) VALUES ('', '".$connectedplayer['Login']."', '".$connectedplayer['NickName']."', '".time()."')";
	$mysql = mysql_query($sql);
	}
	else{
	$sql = "UPDATE `FoxControl`.`players` SET nickname = '".$connectedplayer['NickName']."' WHERE playerlogin = '".$connectedplayer['Login']."'";
	$mysql = mysql_query($sql);
	$sql = "UPDATE `FoxControl`.`players` SET lastconnect = '".time()."' WHERE playerlogin = '".$connectedplayer['Login']."'";
	$mysql = mysql_query($sql);
	}
}

?>