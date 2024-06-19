<?php
/**
 *
 * @author toxn
 *
 */
class plugin_laps extends FoxControlPlugin {
	protected $bestLap;
	protected $bestLapPlayer;
	protected $colorBestLap;
	protected $playerTimes;
	protected $lapCpCount;
	protected $gameMode;

	/**
	 * fox RulEzZz
	 */
	public function onStartUp() {
		$this->name = 'Laps Plugin';
		$this->author = 'toxn';
		$this->version = '0.1';
		
		$this->onBeginMatch();
	}

	/**
	 * reset member variables on mapchange / maprestart
	 *
	 */
	public function onBeginMatch() {
		$this->gameMode = $this->getCurrentGameMode();

		if($this->gameMode != 'laps')
			return;

		$this->reset();
	}

	/**
	 * [0] int PlayerUid
	 * [1] string Login
	 * [2] int TimeOrScore
	 * [3] int CurLap
	 * [4] int CheckpointIndex);
	 * @param unknown $args
	 */
	public function onPlayerCheckpoint($args) {
		if($this->gameMode != 'laps')
			return;

		$tmpBestLap = PHP_INT_MAX;
		// (current lap * number of checkpoints) equals 'current checkpoint index + 1'
		// => lap completed
		if (($args[3] * $this->lapCpCount) == ($args[4] + 1)) {
			if ($this->bestLap == -1) { // no fastest lap stored
				$tmpBestLap = $args[2];
			} // player has no time yet
			elseif (!isset ($this->playerTimes[$args[1]])) {
				$this->playerTimes[$args[1]] = $args[2];
				return;
			}
			elseif( ($args[2] - $this->playerTimes[$args[1]]) < $this->bestLap) {
				$tmpBestLap = $args[2] - $this->playerTimes[$args[1]];
			}

			// lap finished -> store laptime per player
			$this->playerTimes[$args[1]] = $args[2];
		}
		else { // lap not complete -> return
			return;
		}

		// new best lap time driven?
		if($tmpBestLap < $this->bestLap || $this->bestLap == -1) {
			// get player info
			$this->instance()->client->query('GetDetailedPlayerInfo', $args[1]);
			$player_info = $this->instance()->client->getResponse();

			// refresh best lap time
			$this->bestLap = $tmpBestLap;
			// store login
			$this->bestLapPlayer = $args[1];
			// create chat message
			$message = '$z$s' . $this->colorBestLap . 'Best Lap: ' . '$fff' 
				. $this->instance()->format_time($this->bestLap) . '$z$s' . $this->colorBestLap
				. ' by ' . $player_info['NickName'];
			// print message on chat
			$this->chat($message);
		}
	}

	/**
	 * onEndChallenge
	 * once again show fastest lap time to celebrate speedy gonzalez :)
	 *
	 * @param unknown $args
	 */
	public function onEndMap($args) {
		if($this->gameMode != 'laps')
			return;

		if($this->bestLap > -1 && isset($this->bestLapPlayer)) {
			$this->instance()->client->query('GetDetailedPlayerInfo', $this->bestLapPlayer);
			$player_info = $this->instance()->client->getResponse();
			
// 			$message = $player_info['NickName'] . '$z$s'. $this->colorBestLap . ' has driven the Best Lap!'
// 				. '$z$s' . $this->colorBestLap . ' Time: $fff'. $this->instance()->format_time($this->bestLap);

			$message = '$z$s' . $this->colorBestLap . 'Overall Best Lap: ' . '$fff'
				. $this->instance()->format_time($this->bestLap) . '$z$s' . $this->colorBestLap
				. ' by ' . $player_info['NickName'];
			$this->chat($message);
		}
	}
	
	/**
	 * little helper function for getting currentMapInfo
	 * @return mapinfo structure
	 */
	private function getCurrentMapInfo() {
		$this->instance()->client->query('GetCurrentMapInfo');
		return $this->instance()->client->getResponse();
	}
	
	/**
	 * resets member variables
	 */
	private function reset() {
		$mapInfo = $this->getCurrentMapInfo();
		
		$this->bestLap = -1;
		$this->bestLapPlayer = null;
		$this->colorBestLap = '$0d0';
		$this->playerTimes = array();
		$this->lapCpCount = $mapInfo["NbCheckpoints"];
	}
}