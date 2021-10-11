<?php

namespace Max\WebLeaderBoard;

use pocketmine\Player;
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\Listener;

use pocketmine\event\player\{PlayerJoinEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerChatEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent};
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\server\CommandEvent;


class EventListener implements Listener {
    public function __construct($pl) {
        $this->plugin = $pl;
        $pl->getServer()->getPluginManager()->registerEvents($this, $pl);
    }

    public function onJoin(PlayerJoinEvent $event) {
    	$playerName = $event->getPlayer()->getName();
		$this->plugin->jointime[$playerName] = time();
		$this->plugin->messages[$playerName] = "0";
		$this->plugin->commands[$playerName] = "0";
		$this->plugin->broken[$playerName] = "0";
		$this->plugin->placed[$playerName] = "0";
		$this->plugin->resetPlayerStats($playerName);
		$this->plugin->setPlayerStats($playerName, "status", "Online");
		$this->plugin->sendData();
	}

	public function onPlayerDeath(PlayerDeathEvent $event) {
		$victim = $event->getPlayer();
		$this->plugin->bumpPlayerStats($victim->getName(), "deaths", 1);
		$this->plugin->setPlayerStats($victim->getName(), "killstreak", "0");
		if ($victim->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
			$this->plugin->bumpPlayerStats($victim->getLastDamageCause()->getDamager()->getName(), "kills", 1);
			$this->plugin->bumpPlayerStats($victim->getLastDamageCause()->getDamager()->getName(), "killstreak", 1);
		}
		$this->plugin->sendData();
	}

	public function onChat(PlayerChatEvent $event) {
		++$this->plugin->messages[$event->getPlayer()->getName()];
	}

	public function onCommand(CommandEvent $event) {
    	$playerName = $event->getSender();
		if ($playerName instanceof Player) ++$this->plugin->commands[$playerName->getName()];
	}

	public function onBlockBreak(BlockBreakEvent $event) {
    	++$this->plugin->broken[$event->getPlayer()->getName()];
	}

	public function onBlockPlace(BlockPlaceEvent $event) {
		++$this->plugin->placed[$event->getPlayer()->getName()];
	}

	public function onLeave(PlayerQuitEvent $event) {
    	$playerName = $event->getPlayer()->getName();
		$this->plugin->setPlayerStats($playerName, "status", "Offline");
		$this->plugin->bumpPlayerStats($playerName, "messages", $this->plugin->messages[$playerName]);
		$this->plugin->bumpPlayerStats($playerName, "commands", $this->plugin->commands[$playerName]);
		$this->plugin->bumpPlayerStats($playerName, "broken", $this->plugin->broken[$playerName]);
		$this->plugin->bumpPlayerStats($playerName, "placed", $this->plugin->placed[$playerName]);
		$this->plugin->bumpPlayerStats($playerName, "time_played", time() - $this->plugin->jointime[$playerName]);
		$this->plugin->sendData(count($this->plugin->getServer()->getOnlinePlayers()) - 1);
	}

	public function onDisable(PluginDisableEvent $event) {
    	if ($event->getPlugin()->getName() == "WebLeaderBoard") {
			foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
				$playerName = $player->getName();
				$this->plugin->setPlayerStats($playerName, "status", "Offline");
				$this->plugin->bumpPlayerStats($playerName, "messages", $this->plugin->messages[$playerName]);
				$this->plugin->bumpPlayerStats($playerName, "commands", $this->plugin->commands[$playerName]);
				$this->plugin->bumpPlayerStats($playerName, "broken", $this->plugin->broken[$playerName]);
				$this->plugin->bumpPlayerStats($playerName, "placed", $this->plugin->placed[$playerName]);
				$this->plugin->bumpPlayerStats($playerName, "time_played", time() - $this->plugin->jointime[$playerName]);
			}
			$this->plugin->sendData(0);
		}
	}
}