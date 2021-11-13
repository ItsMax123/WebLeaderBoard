# ! **This website I made for this plugin is broken and I do not have the intention on fixing it any time soon. Sorry!**

# WebLeaderBoard
A Pocketmine-MP (PMMP) leaderboard plugin that shows all sorts of statistics on a website.

# Setup Guide
1. To start using the plugin, download the WebLeaderBoard.phar file from poggit and put it into your server's plugins folder.
2. Then you can edit the config.yml file to your liking which can be found in the plugins_data/WebLeaderBoard folder.
5. After that, all you have to do is start your server and go to https://webleaderboard.pythonanywhere.com/ and search for your server.

# Support
Join the [discord server](https://discord.gg/YJZNhwhyMQ) for quick questions.<br>
For issues and suggestions, please create an issue on Github. Please provide as much details for bug reports. If there is a error in your console, please copy paste it in the issue.

# FAQ
**How can I add more stats to my servers page on the website?**<br>
To add a custom statistics page with statistics from another plugin, you must nicely ask that plugin's developper to support this plugin. They will have to follow the developpers guide down below. If they do not respond or they refuse to do it, let me know (ItsMax123#6798 on discord) and I will make an intergration plugin.

# Developers
If you are a plugin developer and would like to create your own stats page to the website, please follow this guide.<br>
**NOTE**: This page will only appear for servers using your plugin.

1. The first step is to import these classes (just copy paste this):
```php
use Max\WebLeaderBoard\Events\RequestPagesEvent;
use Max\WebLeaderBoard\WebLeaderBoard;
```

2. Then, you need to create the page. For this you need to supply the required information.<br>
To do this, you must use the `RequestPagesEvent` event. So simply copy paste this code into your EventListener or any class with the pocketmine Listener implemented.<br>
Then you need to replace the:
- PAGE_NAME -> the name of the page on the website. (NO SPACES! Only 1 word. Example: `Economy`)
- PAGE_TITLE -> the title of the stats page. (Example: `Economy Stats`)
- PAGE_TITLE_1-4 -> the titles of each data entry in the table on the website. (Example: `Money`)
- TABLE_DEFAULT_VALUE_1-4 -> the default value of each data entry in the table on the website that will only be shown when nothing is set yet. (Example: `0`)
- PAGE_ICON_LINK -> a link to an image that will be used at the icon for your page. (Example: `https://bit.ly/3lIhWjH`)
```php
    public function onRequestPages(RequestPagesEvent $event) {
        $event->setPage(
            "PAGE_NAME",
            "PAGE_TITLE",
            [	
                "TABLE_TITLE_1" => "TABLE_DEFAULT_VALUE_1", 
                "TABLE_TITLE_2" => "TABLE_DEFAULT_VALUE_2", 
                "TABLE_TITLE_3" => "TABLE_DEFAULT_VALUE_3", 
                "TABLE_TITLE_4" => "TABLE_DEFAULT_VALUE_4"
            ],
            "PAGE_ICON_LINK"
        );
    }
```

3. Now that your page is set and will appear on the website (only on servers that have your plugin installed), you need to make the stats update as the information is changed.<br>
To do this you will have to use the `setPageStat` function. Copy the code below and replace the following:
- PAGE_NAME -> the same page name used in step 2. (EXACTLY THE SAME NAME!)
- PLAYER_NAME -> the name of the player who's stat you want to change.
- TABLE_TITLE -> the title of the data entry which you want to change.
- TABLE_VALUE -> the value you want to set.
<br><br>**NOTE:** If you only want to update your pages stats every time the website's info reloads, you can use the `SendDataEvent` which is only called right before the website's info is reloaded.
```php
WebLeaderBoard::getInstance()->setPageStat("PAGE_NAME", "PLAYER_NAME", "TABLE_TITLE", "TABLE_VALUE");
```

4. Done! If you still have any questions please look at these working examples:
- This one using EconomyAPI:
```php
<?php

declare(strict_types=1);

namespace Max\WebEcoAPI;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use Max\WebLeaderBoard\Events\RequestPagesEvent;
use Max\WebLeaderBoard\WebLeaderBoard;
use onebone\economyapi\event\money\MoneyChangedEvent;

class Main extends PluginBase{
    public function onEnable() {
    	$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}

class EventListener implements Listener {
    public function onRequestPages(RequestPagesEvent $event) {
        $event->setPage("Economy", "Economy Stats", ["Money" => "0"], "https://raw.githubusercontent.com/poggit-orphanage/EconomyS/449b2cbd25c250aff680738ae2654ba11751e347/EconomyAPI/icon.png");
    }

    public function onMoneyChange(MoneyChangedEvent $event){
        WebLeaderBoard::getInstance()->setPageStat("Economy", $event->getUsername(), "Money", (string) $event->getMoney());
    }
}
```
- This one using RedSkyBlock:
```php
<?php

declare(strict_types=1);

namespace Max\WebRedSkyBlock;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Server;

use Max\WebLeaderBoard\Events\{RequestPagesEvent, SendDataEvent};
use Max\WebLeaderBoard\WebLeaderBoard;

class Main extends PluginBase{
    public function onEnable() {
    	$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}

class EventListener implements Listener {
    public function onRequestPages(RequestPagesEvent $event) {
        $event->setPage("RedSkyBlock", "SkyBlock Stats", ["Island Name" => "N/A", "Island Size" => "N/A", "Island Value" => "N/A", "Island Rank" => "N/A"], "https://raw.githubusercontent.com/RedCraftGH/RedSkyBlock/master/icon.png");
    }

    public function onSendData(SendDataEvent $event) {
        $WebLeaderBoard = WebLeaderBoard::getInstance();
        $RedSkyBlock = Server::getInstance()->getPluginManager()->getPlugin("RedSkyBlock");

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Name", (string) $RedSkyBlock->getIslandName($player));
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Size", (string) $RedSkyBlock->getIslandSize($player));
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Value", (string) $RedSkyBlock->getIslandValue($player));
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Rank", (string) $RedSkyBlock->getIslandRank($player));
        }
    }
}
```

**Contact me on discord ItsMax123#6798 if you need any help intergrating this into your plugin.**
