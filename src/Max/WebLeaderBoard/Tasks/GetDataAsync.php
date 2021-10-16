<?php

namespace Max\WebLeaderBoard\Tasks;

use pocketmine\scheduler\AsyncTask;

class GetDataAsync extends AsyncTask {

	public function __construct(string $secret_token, array $data) {
		$this->secret_token = $secret_token;
		$this->data = $data;
	}

	public function onRun() {
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => 'http://webleaderboard.pythonanywhere.com/getData/' . $this->secret_token,
			#CURLOPT_URL => 'http://127.0.0.1:5000/getData/' . $this->secret_token,
			CURLOPT_TIMEOUT_MS => 15000,
			CURLOPT_RETURNTRANSFER => TRUE
		]);
		$response = curl_exec($curl);
		$this->setResult($response);
		curl_close($curl);
	}

	public function onCompletion($server) {
		$response = $this->getResult();
		$plugin = $server->getPluginManager()->getPlugin("WebLeaderBoard");
		if ($response != False) {
			if ($response != "Invalid secret") {
				$plugin->receivedData(json_decode($response, true));
			} else {
				$server->getLogger()->error("[WebLeaderBoard] Invalid secret token. Disabling plugin.");
				$server->getPluginManager()->disablePlugin($plugin);
			}
		} else {
			$server->getLogger()->error("[WebLeaderBoard] Failed to get response from website. Disabling plugin.");
			$server->getPluginManager()->disablePlugin($plugin);
		}
	}
}
