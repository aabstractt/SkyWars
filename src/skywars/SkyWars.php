<?php

declare(strict_types=1);

namespace skywars;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use skywars\command\SWCommand;
use skywars\factory\ArenaFactory;
use skywars\factory\MapFactory;
use skywars\factory\SignFactory;
use skywars\listener\EntityLevelChangeListener;
use skywars\listener\PlayerQuitListener;
use skywars\listener\BlockBreakListener;
use skywars\player\SWPlayer;

class SkyWars extends PluginBase {

    /** @var SkyWars */
    private static $instance;
    /** @var array */
    private $scoreboard = [];

    /**
     * @return SkyWars
     */
    public static function getInstance(): SkyWars {
        return self::$instance;
    }

    public function onEnable() {
        self::$instance = $this;

        $this->saveConfig();
        $this->saveResource('scoreboard.yml');

        MapFactory::getInstance()->init();
        ArenaFactory::getInstance()->init();
        SignFactory::getInstance()->init();

        $this->scoreboard = (new Config($this->getDataFolder() . 'scoreboard.yml'))->getAll();

        $this->getServer()->getCommandMap()->register(SWCommand::class, new SWCommand());

        $this->getServer()->getPluginManager()->registerEvents(new EntityLevelChangeListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockBreakListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);
    }

    /**
     * @param SWPlayer      $deathPlayer
     * @param SWPlayer|null $killerPlayer
     * @param int           $cause
     */
    public static function handlePlayerDeath(SWPlayer $deathPlayer, ?SWPlayer $killerPlayer, int $cause = -1): void {
        $arena = $deathPlayer->getArena();

        if ($cause === -1) {
            $arena->removePlayer($deathPlayer->getInstanceNonNull());
        } else {
            // TODO: Add player to spectators (Before adding it to the spectators, it will try to remove it from the players)
            $arena->addSpectator($deathPlayer);
        }
    }

    /**
     * @param string      $type
     * @param array       $findAndReplace
     * @param string|null $typeExtra
     *
     * @return array
     */
    public static function translateScoreboard(string $type, array $findAndReplace, string $typeExtra = null): array {
        $firstType = $type;

        if ($typeExtra != null) {
            $type = $type . '-' . $typeExtra;
        }

        $scoreboard = self::$instance->scoreboard[$type] ?? [];

        if (empty($scoreboard)) {
            return [];
        }

        $replace = function (array $findAndReplace, string $text): string {
            foreach ($findAndReplace as $search => $replace) {
                $text = str_replace('$' . $search, $replace, $text);
            }

            return $text;
        };

        if (isset($findAndReplace['title'])) {
            $findAndReplace['title'] = $replace($findAndReplace, self::$instance->scoreboard[$firstType . '-title'][$findAndReplace['title']] ?? '');
        }

        foreach ($scoreboard as $slot => $text) {
            $scoreboard[$slot] = $replace($findAndReplace, $text);
        }

        return $scoreboard;
    }

    /**
     * @return bool
     */
    public static function isUnderDevelopment(): bool {
        return true;
    }
}