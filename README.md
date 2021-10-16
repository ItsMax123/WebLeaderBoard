# WebLeaderBoard
A Pocketmine-MP (PMMP) leaderboard plugin that shows all sorts of statistics on a website.

# Setup Guide
1. To start using the plugin, download the WebLeaderBoard.phar file from poggit and put it into your server's plugins folder.
2. Then you can edit the config.yml file to your liking which can be found in the plugins_data/WebLeaderBoard folder.
5. After that, all you have to do is start your server and go to https://webleaderboard.pythonanywhere.com/ and search for your server.

# Support
Join the [discord server](https://discord.gg/YJZNhwhyMQ) for quick questions.<br>
For issues and suggestions, please create an issue on Github. Please provide as much details for bug reports. If there is a error report in your console, please copy paste it in the issue.

# FAQ
**How can I add more stats to my servers page on the website?**<br>
To add a custom statistics page with statistics from another plugin, you must nicely ask that plugin's developper to support this plugin. They must follow the developpers guide below.

# Developers
If you are a plugin developer and would like to add support for your own stats page to the website, please follow this guide.<br>
**NOTE**: This page will only appear for servers using your plugin.

1. The first step is to import these classes (just copy paste this):
```php
use Max\WebLeaderBoard\Events\{RequestPagesEvent, SendDataEvent};
```

2. You need to create the page. For this you need to supply the required information.<br>
To do this, you must use the `RequestPagesEvent` event. So simply copy paste this code into your EventListener or any class with the pocketmine Listener implemented.<br>
Then you need to replace the:
- PAGE_NAME -> the name of the page on the website. (NO SPACES! Only 1 word. Example: `SkyBlock`)
- PAGE_TITLE -> the title of the stats page. (Example: `SkyBlock Stats`)
- PAGE_TITLE_1-4 -> the titles of each data entry in the table on the website. (Example: `Rank`)
- TABLE_DEFAULT_VALUE_1-4 -> the default value of each data entry in the table on the website that will only be shown when nothing is set yet. (Example: `0`)
- PAGE_ICON_LINK -> a link to an image that will be used at the icon for your page. (Example: `https://bit.ly/3lIhWjH`)
```php
    public function onRequestPagesEvent(RequestPagesEvent $event) {
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

3. Now that your page is set and will appear on the website (only pages of servers that have your plugin installed), you need to make these stats update!<br>
To do this you will have to use the `setPageStat` function. Copy the code below and replace the following:
- PAGE_NAME -> the same page name used in step 2. (EXACTLY THE SAME NAME!)
- PLAYER_NAME -> the name of the player who's stat you want to change.
- TABLE_TITLE -> the title of the data entry which you want to change.
- TABLE_VALUE -> the value you want to set.
<br><br>**NOTE:** If you only want to update your pages stats every time the website's info reloads, you can use the `SendDataEvent` which is only called every time the website's info is reloaded (Example of this on step 4).
```php
Server::getInstance()->getPluginManager()->getPlugin("WebLeaderBoard")->setPageStat("PAGE_NAME", "PLAYER_NAME", "TABLE_TITLE", "TABLE_VALUE");
```

4. Done! If you still have any questions please look at this working example implementing a RedSkyBlock stats page:
```php
<?php

declare(strict_types=1);

namespace Max\WebRedSkyBlock;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\plugin\Plugin;

use pocketmine\event\PlayerJoinEvent;
use Max\WebLeaderBoard\Events\{RequestPagesEvent, SendDataEvent};

class Main extends PluginBase{
    public function onEnable() {
    	#Making sure that WebLeaderBoard plugin is installed:
    	if ($this->getServer()->getPluginManager()->getPlugin("WebLeaderBoard") instanceof Plugin) {
            $this->getServer()->getPluginManager()->registerEvents(new WebLeaderBoardListener($this), $this);
    	}
    }
}

class WebLeaderBoardListener implements Listener {
    public function onRequestPagesEvent(RequestPagesEvent $event) {
        $event->setPage("RedSkyBlock", "SkyBlock Stats", ["Island Name" => "N/A", "Island Size" => "N/A", "Island Value" => "N/A", "Island Rank" => "N/A"], "https://raw.githubusercontent.com/RedCraftGH/RedSkyBlock/master/icon.png");
    }

    public function onSendDataEvent(SendDataEvent $event) {
        $WebLeaderBoard = Server::getInstance()->getPluginManager()->getPlugin("WebLeaderBoard");
        $RedSkyBlock = Server::getInstance()->getPluginManager()->getPlugin("RedSkyBlock");

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Name", (string)$RedSkyBlock->getIslandName($player));
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Size", (string)$RedSkyBlock->getIslandSize($player));
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Value", (string)$RedSkyBlock->getIslandValue($player));
            $WebLeaderBoard->setPageStat("RedSkyBlock", $player->getName(), "Island Rank", (string)$RedSkyBlock->getIslandRank($player));
        }
    }
}
```

**Contact me on discord ItsMax123#6798 if you need any help intergrating this into your plugin.**
