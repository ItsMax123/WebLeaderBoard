<?php

namespace Max\WebLeaderBoard\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;

use Max\WebLeaderBoard\Events\SendDataEvent;

class SendDataTask extends Task {

	public function __construct($pl) {
		$this->plugin = $pl;
	}

	public function onRun(int $currentTick) {
		$event = new SendDataEvent();
		$event->call();
		foreach (Server::getInstance()->getOnlinePlayers() as $player) {
			$this->plugin->data["Players"][$player->getName()]["time_played"] = $this->plugin->data["Players"][$player->getName()]["time_played"] + $this->plugin->config->get("send_data_interval") ?? "0";
		}
		$this->plugin->data["online_player_names"] = $this->plugin->getOnlinePlayerNames();
		$this->plugin->data["online_player_count"] = (string)count(Server::getInstance()->getOnlinePlayers());
		$this->plugin->data["last_edit"] = time();
		Server::getInstance()->getAsyncPool()->submitTask(new SendDataAsync($this->plugin->secret->get("secret_token"), $this->plugin->data));
	}
}