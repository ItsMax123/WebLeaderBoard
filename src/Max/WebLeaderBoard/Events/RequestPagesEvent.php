<?php

namespace Max\WebLeaderBoard\Events;

use pocketmine\event\Event;

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
		$this->plugin->data["Pages"][$pageName]["status"] = True;
	}
}