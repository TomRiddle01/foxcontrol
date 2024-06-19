<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Auto TimeLimit plugin.
 * Changes Timelimit for TimeAttack dynamically depending on the next
 * track's author time.
 *
 * Original by ck|cyrus
 * Rewrite by Xymph
 * Beaten into submission by Leigham
 *
 * Dependencies: none
 */

class plugin_timelimit extends FoxControlPlugin {
public function onStartUp() {
	$this->name = 'replay'; //- String, the name of the plugin. (Please set this at StartUp)
	$this->author ='original CK|cyrus rewrite by Xmyph , Leigham, ubm'; //- String, the name of the author of the plugin. (Please set this at StartUp)
	$this->version='0.0.1'; //- String, Version of the plugin. (Please set this at StartUp)
global $mintime, $maxtime, $multiplier;
//Config -----------------------------------------------------------------------------------
$mintime = 4;               // Minimum timelimit for a challenge in minutes.
$maxtime = 7;              // Maximum timelimit for a challenge in minutes.
$multiplier = 7;             // Number to multiply authortime by.
//Config -----------------------------------------------------------------------------------

}

// called @ EndRace
public function onEndRace($args) {
global $mintime, $maxtime, $multiplier;
	// get next game settings
	$this->instance()->client->query('GetNextMapInfo');
	$nextgame = $this->instance()->client->getResponse();
	$nexttime = intval($nextgame['AuthorTime']);
console ($nexttime);
	// compute new timelimit
	if ($nexttime <= 0) {
		$nexttime = $mintime * 60 * 1000;
		$tag = 'default';
	} else {
		$nexttime *= $multiplier;
		$nexttime -= ($nexttime % 1000);  // round down to seconds
		$tag = 'new';
	}
	// check for min/max times
	if ($nexttime < $mintime * 60 * 1000) {
		$nexttime = $mintime * 60 * 1000;
		$tag = 'min';
	} elseif ($nexttime > $maxtime * 60 * 1000) {
		$nexttime = $maxtime * 60 * 1000;
		$tag = 'max';
	}
		// set and log timelimit (strip .00 sec)
		console ($nexttime);
	$this->instance()->client->query('SetTimeAttackLimit', $nexttime);
}  // Timelimit
}
?>