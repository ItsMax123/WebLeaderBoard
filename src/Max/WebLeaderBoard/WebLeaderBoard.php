<?php

declare(strict_types=1);

namespace Max\WebLeaderBoard;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{Config, Internet};
use pocketmine\command\{Command, CommandSender};

use Max\WebLeaderBoard\Events\RequestPagesEvent;
use Max\WebLeaderBoard\Tasks\{GetDataAsync, SendDataTask};

class WebLeaderBoard extends PluginBase{
    public $data = [], $players = [];

    public function onEnable() {
        $this->saveResource("config.yml");
		$this->saveResource("secret.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->secret = new Config($this->getDataFolder() . "secret.yml", Config::YAML);

		#Create secret token if it doesnt already exist
		if (!isset($this->secret->getAll()["secret_token"])) {
			$server_id = random_int(100000000, 999999999);
			$token_secret = bin2hex(random_bytes(10));
			$secret_key = $server_id."/".$token_secret;
			$this->secret->set("secret_token", $secret_key);
			$this->secret->save();
			$this->secret->reload();
		}

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getServer()->getAsyncPool()->submitTask(new GetDataAsync($this->secret->get("secret_token"), $this->data));
    }

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
	    if ($command->getName() == "webleaderboard") {
			$sender->sendMessage("§aHead over to §bbit.ly/2YMxJWb§a and search for '§c". $this->getConfigOrDefault("server-name", $this->getServer()->getMotd()) ."§a' in the search bar OR go to §bhttp://webleaderboard.pythonanywhere.com/server/".explode('/', $this->secret->get("secret_token"))[0]);
		}
	    return true;
	}

    public function getConfigOrDefault(string $value, string $default): string {
        $value = $this->config->get($value);
        if ($value) {
            return $value;
        } else {
            return $default;
        }
    }

    public function getOnlinePlayerNames(): array {
        $online_player_names = [];
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            array_push($online_player_names, $player->getName());
        }
        return $online_player_names ?? [];
    }

	public function receivedData($data) {
    	$this->data = $data;
		$this->data["name"] = $this->getConfigOrDefault("server-name", $this->getServer()->getMotd());
		$this->data["description"] = $this->getConfigOrDefault("server-description", "Just another normal server.");
		$this->data["image"] = $this->getConfigOrDefault("server-image-link", "https://static.wikia.nocookie.net/c9029f50-8210-4352-aece-6230999811a0");
		$this->data["ip"] = $this->getConfigOrDefault("server-ip", (string)Internet::getIp());
		$this->data["port"] = (string)$this->getServer()->getPort();
		$this->data["max_online_players"] = (string)$this->getServer()->getMaxPlayers();

		$event = new RequestPagesEvent($this);
		$event->call();
		$this->getScheduler()->scheduleRepeatingTask(new SendDataTask($this), (int)($this->config->get("send_data_interval") ?? 30)*20);
	}

    //API SECTION

    public function setPageStat(string $pageName, string $playerName, string $stat, string $value): void {
        $this->data["Pages"][$pageName]["Players"][$playerName][$stat] = $value;
    }

    public function bumpPageStat(string $pageName, string $playerName, string $stat, int $bumpValue): void {
        $this->data["Pages"][$pageName]["Players"][$playerName][$stat] = (string)((int)$this->data["Pages"][$pageName]["Players"][$playerName][$stat] + $bumpValue);
    }

    public function getPageStat(string $pageName, string $playerName, string $stat): ?string{
        return $this->data["Pages"][$pageName][$playerName][$stat] ?? null;
    }
}