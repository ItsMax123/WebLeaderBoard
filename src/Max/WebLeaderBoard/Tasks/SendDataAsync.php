<?php

namespace Max\WebLeaderBoard\Tasks;

use pocketmine\scheduler\AsyncTask;

class SendDataAsync extends AsyncTask {

	public function __construct(string $secret_token, array $data) {
		$this->secret_token = $secret_token;
		$this->data = $data;
	}

	public function onRun() {
		$curl = curl_init();
		curl_setopt_array($curl, [
			#CURLOPT_URL => 'http://webleaderboard.pythonanywhere.com/sendData/' . $this->secret_token,
			CURLOPT_URL => 'http://127.0.0.1:5000/sendData/' . $this->secret_token,
			CURLOPT_POSTFIELDS => json_encode($this->data),
			CURLOPT_HTTPHEADER => array('Content-Type:application/json'),
			CURLOPT_TIMEOUT_MS => 5000,
			CURLOPT_RETURNTRANSFER => TRUE
		]);
		curl_exec($curl);
		curl_close($curl);
	}
}