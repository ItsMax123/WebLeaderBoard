<?php

declare(strict_types=1);

namespace Max\WebLeaderBoard;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{Config, Internet};
use pocketmine\scheduler\{Task, AsyncTask};
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\Event;
use pocketmine\Server;


class WebLeaderBoard extends PluginBase{
    public $data = [];

    public function onEnable() {
        $this->saveResource("config.yml");
		$this->saveResource("secret.yml");
		$this->saveResource("players.json");
		$this->saveResource("Pages\\Index.json");
		$this->saveResource("Pages\\Combat.json");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->secret = new Config($this->getDataFolder() . "secret.yml", Config::YAML);
		$this->players = new Config($this->getDataFolder() . "players.json", Config::JSON);

		#Create secret token if it doesnt already exist
		if (!isset($this->secret->getAll()["secret_token"])) {
			$server_id = random_int(100000000, 999999999);
			$token_secret = bin2hex(random_bytes(10));
			$secret_key = $server_id."/".$token_secret;
			$this->secret->set("secret_token", $secret_key);
			$this->secret->save();
			$this->secret->reload();
		}

        #Load data from save
        foreach (array_diff(scandir(str_replace("/", "\\", $this->getDataFolder() . "Pages")), array('.', '..')) as $pageName) {
            $filePath = $this->getDataFolder() . "Pages\\" . $pageName;
            $dataEncoded = file_get_contents($filePath);
            $this->data["Pages"][str_replace(".json", "", $pageName)] = (array) json_decode($dataEncoded, true);
        }
		$this->data["name"] = $this->getConfigOrDefault("server-name", $this->getServer()->getMotd());
		$this->data["description"] = $this->getConfigOrDefault("server-description", "Just another normal server.");
		$this->data["image"] = $this->getConfigOrDefault("server-image-link", "https://static.wikia.nocookie.net/c9029f50-8210-4352-aece-6230999811a0");
		$this->data["ip"] = $this->getConfigOrDefault("server-ip", (string)Internet::getIp());
		$this->data["port"] = (string)$this->getServer()->getPort();
		$this->data["max_online_players"] = (string)$this->getServer()->getMaxPlayers();
		$this->data["online_player_count"] = (string)count($this->getServer()->getOnlinePlayers());
		$this->data["online_player_names"] = $this->getOnlinePlayerNames();
		$this->data["Players"] = $this->players->get("Players") ?? [];
		if (!isset($this->data["Players"])) $this->players["Players"] = [];
		foreach ($this->getServer()->getOnlinePlayers() as $player) {
			$this->data["Players"][$player->getName()]["time_played"] = $this->data["Players"][$player->getName()]["time_played"] + $this->config->get("send_data_interval") ?? "0";
		}

        $sendDataInterval = (int)($this->config->get("send_data_interval") ?? 30)*20;
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getScheduler()->scheduleDelayedTask(new RequestPagesTask($this), $sendDataInterval - 1);
		$this->getScheduler()->scheduleDelayedRepeatingTask(new SendDataTask($this), $sendDataInterval, $sendDataInterval);
    }

	public function onDisable() {
		$this->backupData();
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

    //API SECTION

    public function setPageStat(string $pageName, string $playerName, string $stat, string $value): void {
        $this->data["Pages"][$pageName]["Players"][$playerName][$stat] = $value;
    }

    public function setPageStats(string $pageName, string $playerName, array $value): void {
        $this->data["Pages"][$pageName]["Players"][$playerName] = $value;
    }

    public function bumpPageStat(string $pageName, string $playerName, string $stat, int $bumpValue): void {
        $this->data["Pages"][$pageName]["Players"][$playerName][$stat] = (string)((int)$this->data["Pages"][$pageName]["Players"][$playerName][$stat] + $bumpValue);
    }

    public function getPageStat(string $pageName, string $playerName, string $stat): ?string{
        return $this->data["Pages"][$pageName][$playerName][$stat] ?? null;
    }

    public function getPageStats(string $pageName, string $playerName): ?array {
        return $this->data["Pages"][$pageName][$playerName] ?? null;
    }



    public function backupData(): void {
		$this->players->set("Players", $this->data["Players"]);
		$this->players->save();
        foreach ($this->data["Pages"] as $pageName => $pageData) {
            $dataEncoded = json_encode($this->data["Pages"][$pageName],JSON_PRETTY_PRINT);
            file_put_contents(str_replace("/", "\\", $this->getDataFolder() . "Pages\\" . $pageName . ".json"), $dataEncoded);
        }
    }
}

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
		$this->plugin->backupData();
		Server::getInstance()->getAsyncPool()->submitTask(new SendDataAsync($this->plugin->secret->get("secret_token"), $this->plugin->data));
	}
}

class SendDataEvent extends Event {}

class SendDataAsync extends AsyncTask {

	public function __construct(string $secret_token, array $data) {
		$this->secret_token = $secret_token;
		$this->data = $data;
	}

	public function onRun() {
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => 'http://webleaderboard.pythonanywhere.com/sendData/' . $this->secret_token,
			#CURLOPT_URL => 'http://127.0.0.1:5000/sendData/' . $this->secret_token,
			CURLOPT_POSTFIELDS => json_encode($this->data),
			CURLOPT_HTTPHEADER => array('Content-Type:application/json'),
			CURLOPT_TIMEOUT_MS => 500,
			CURLOPT_RETURNTRANSFER => TRUE
		]);
		$response = curl_exec($curl);
		var_dump($response);
		var_dump(curl_error($curl));
		curl_close($curl);
	}
}

class RequestPagesTask extends Task {

	public function __construct($pl) {
		$this->plugin = $pl;
	}

	public function onRun(int $currentTick) {
		$event = new RequestPagesEvent($this->plugin);
		$event->call();
	}
}

class RequestPagesEvent extends Event {
	public function __construct($pl) {
		$this->plugin = $pl;
	}

	public function setPage(string $pageName, string $pageTitle, array $defaultLayout, string $iconURL): void {
		if (!isset($this->plugin->data["Pages"][$pageName])) {
			$this->plugin->data["Pages"][$pageName]["page_name"] = $pageName;
			$this->plugin->data["Pages"][$pageName]["page_title"] = $pageTitle;
			$this->plugin->data["Pages"][$pageName]["icon_url"] = $iconURL;
			$this->plugin->data["Pages"][$pageName]["default_layout"] = $defaultLayout;
			$this->plugin->data["Pages"][$pageName]["Players"] = [];
		}
	}
}