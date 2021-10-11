<?php

declare(strict_types=1);

namespace Max\WebLeaderBoard;

use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender, ConsoleCommandSender};

class Main extends PluginBase{
    public $jointime = [], $messages, $commands, $broken, $placed;

    public function onEnable() {
        $this->saveResource("config.yml");
		$this->saveResource("secret.yml");
		$this->saveResource("data.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->secret = new Config($this->getDataFolder() . "secret.yml", Config::YAML);
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
        $this->dataAll = $this->data->getAll();
	    
		new EventListener($this);

		if (!isset($this->secret->getAll()["secret_token"])) {
			$server_id = random_int(100000000, 999999999);
			$token_secret = bin2hex(random_bytes(30));
			$secret_key = $server_id.".".$token_secret;
			$this->secret->set("secret_token", $secret_key);
			$this->secret->save();
			$this->secret->reload();
		}

		$this->updateServerInfo();
    }

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
	    if ($command->getName() == "leaderboard") {
			$sender->sendMessage("§aHead over to §bshorturl.at/ouLOS§a and search for '§c". $this->config->get("server-name") ."§a' in the search bar or go to §bhttp://webleaderboard.pythonanywhere.com/server/".explode('.', $this->secret->get("secret_token"))[0]."/stats/players");
		}
	    return true;
	}

    public function sendData($online_players = NULL) {
		$secret = str_replace(".", "/", $this->secret->get("secret_token"));
		$url = 'http://webleaderboard.pythonanywhere.com/sendData/'.$secret;
		$data = $this->data->getAll();
		$data["last_edit"] = time();
		$data["online_players"] = (string)($online_players ?? count($this->getServer()->getOnlinePlayers()));
		$curl = curl_init();
		curl_setopt_array($curl,[CURLOPT_URL=>$url,CURLOPT_POSTFIELDS=>json_encode($data),CURLOPT_HTTPHEADER=>array('Content-Type:application/json'),CURLOPT_TIMEOUT_MS=>100,CURLOPT_RETURNTRANSFER=>TRUE]);
		curl_exec($curl);
		curl_close($curl);
	}

	public function updateServerInfo() {
		$this->data->set("name", $this->isset("server-name", $this->getServer()->getMotd()));
		$this->data->set("description", $this->isset("server-description", "Just another normal server."));
		$this->data->set("image", $this->isset("server-image-link", "https://static.wikia.nocookie.net/c9029f50-8210-4352-aece-6230999811a0"));
		$this->data->set("ip", $this->isset("server-ip", $this->getServer()->getIp()));
		$this->data->set("port", $this->isset("server-port", (string)$this->getServer()->getPort()));
		$this->data->set("max_players", (string)$this->getServer()->getMaxPlayers());
		$this->data->save();
		$this->sendData();
	}

	public function isset(string $value, string $default) {
    	$value = $this->config->get($value);
    	if ($value){
    		return $value;
		} else {
    		return $default;
		}
	}

	public function bumpPlayerStats(string $player, string $stat, int $bump) {
		$playerStats = $this->data->get("Players");
		$playerStats[$player][$stat] = (string)((int)$playerStats[$player][$stat] + $bump);
		$this->data->set("Players", $playerStats);
		$this->data->save();
	}

	public function setPlayerStats(string $player, string $stat, string $value) {
		$playerStats = $this->data->get("Players");
		$playerStats[$player][$stat] = $value;
		$this->data->set("Players", $playerStats);
		$this->data->save();
	}

	public function resetPlayerStats(string $playerName) {
		$playerStats = $this->data->get("Players");
		if (!array_key_exists($playerName, $playerStats)) {
			$playerStats[$playerName] = array(
				"kills" => "0",
				"deaths" => "0",
				"time_played" => "0",
				"status" => "Offline",
				"killstreak" => "0",
				"messages" => "0",
				"commands" => "0",
				"placed" => "0",
				"broken" => "0"
			);
			$this->data->set("Players", $playerStats);
			$this->data->save();
		}
	}
}
