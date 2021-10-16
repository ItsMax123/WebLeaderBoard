<?php

declare(strict_types=1);

namespace Max\WebLeaderBoard;

use pocketmine\Player;
use pocketmine\event\Listener;

use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\player\{PlayerJoinEvent, PlayerDeathEvent, PlayerChatEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent};
use pocketmine\event\server\CommandEvent;

use Max\WebLeaderBoard\Events\RequestPagesEvent;

class EventListener implements Listener {
    public function __construct($pl) {
        $this->plugin = $pl;
    }

	public function onRequestPages(RequestPagesEvent $event) {
		$event->setPage(
			"Index",
			"Basic Player Stats",
			[
				"Messages" => "0",
				"Commands" => "0",
				"Blocks Broken" => "0",
				"Blocks Placed" => "0"
			],
			"https://icon-library.com/images/user-icon-png-transparent/user-icon-png-transparent-10.jpg"
		);
		$event->setPage(
			"Combat",
			"Player Combat Stats",
			[
				"Kills" => "0",
				"Deaths" => "0",
				"Killstreak" => "0"
			],
			"https://library.kissclipart.com/20180831/oce/kissclipart-crossed-swords-png-clipart-sword-clip-art-01979e13d09a42a6.png"
		);
	}

	public function onPlayerDeath(PlayerDeathEvent $event) {
		$victim = $event->getPlayer();
		$this->plugin->bumpPageStat("Combat", $victim->getName(), "Deaths", 1);
		$this->plugin->setPageStat("Combat", $victim->getName(), "Killstreak", "0");
		if ($victim->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
			$this->plugin->bumpPageStat("Combat", $victim->getLastDamageCause()->getDamager()->getName(), "Kills", 1);
			$this->plugin->bumpPageStat("Combat", $victim->getLastDamageCause()->getDamager()->getName(), "Killstreak", 1);
		}
	}

	#IMPORTANT: This is to set add player's to pages if they dont already exist.
	public function onJoin(PlayerJoinEvent $event) {
    	$playerName = $event->getPlayer()->getName();
    	foreach ($this->plugin->data["Pages"] as $pageName => $pageData) {
    		if (!isset($this->plugin->data["Pages"][$pageName]["Players"][$playerName])) {
    			$this->plugin->data["Pages"][$pageName]["Players"][$playerName] = $this->plugin->data["Pages"][$pageName]["default_layout"];
			}
		}
    	if (!isset($this->plugin->data["Players"][$playerName])) $this->plugin->data["Players"][$playerName] = array("time_played" => "0");
	}

	public function onChat(PlayerChatEvent $event) {
		$this->plugin->bumpPageStat("Index", $event->getPlayer()->getName(), "Messages", 1);
	}

	public function onCommand(CommandEvent $event) {
    	$playerName = $event->getSender();
		if ($playerName instanceof Player) $this->plugin->bumpPageStat("Index", $playerName->getName(), "Commands", 1);
	}

	public function onBlockBreak(BlockBreakEvent $event) {
		$this->plugin->bumpPageStat("Index", $event->getPlayer()->getName(), "Blocks Broken", 1);
	}

	public function onBlockPlace(BlockPlaceEvent $event) {
		$this->plugin->bumpPageStat("Index", $event->getPlayer()->getName(), "Blocks Placed", 1);
	}
}