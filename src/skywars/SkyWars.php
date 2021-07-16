<?php

namespace skywars;

use pocketmine\plugin\PluginBase;
use skywars\command\SWCommand;
use skywars\factory\ArenaFactory;
use skywars\factory\MapFactory;
use skywars\listener\EntityLevelChangeListener;
use skywars\listener\PlayerQuitListener;

class SkyWars extends PluginBase {

    /** @var SkyWars */
    private static $instance;

    /**
     * @return SkyWars
     */
    public static function getInstance(): SkyWars {
        return self::$instance;
    }

    public function onEnable() {
        self::$instance = $this;

        MapFactory::getInstance()->init();
        ArenaFactory::getInstance()->init();

        $this->getServer()->getCommandMap()->register(SWCommand::class, new SWCommand());

        $this->getServer()->getPluginManager()->registerEvents(new EntityLevelChangeListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);
    }

    /**
     * @return bool
     */
    public static function isUnderDevelopment(): bool {
        return true;
    }
}