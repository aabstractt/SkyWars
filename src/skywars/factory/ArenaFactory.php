<?php

declare(strict_types=1);

namespace skywars\factory;

use pocketmine\Server;
use skywars\arena\SWArena;
use skywars\arena\SWMap;
use skywars\arena\SWSign;
use skywars\asyncio\FileDeleteAsyncTask;
use skywars\InstancePluginReference;
use pocketmine\Player;
use pocketmine\plugin\PluginException;
use skywars\player\SWPlayer;
use skywars\SkyWars;

class ArenaFactory {

    use InstancePluginReference;

    /** @var array<int, SWArena> */
    private $arenas = [];
    /** @var int */
    private $gamesPlayed = 1;

    public function init(): void {
        $matches = glob(Server::getInstance()->getDataPath() . 'worlds/SW-*', GLOB_ONLYDIR);

        if ($matches !== false) {
            foreach ($matches as $match) {
                FileDeleteAsyncTask::recurse_delete($match);
            }
        }
    }

    /**
     * @param SWSign|null $sign
     * @param SWMap|null  $map
     *
     * @return SWArena
     */
    public function registerNewArena(SWSign $sign = null, SWMap $map = null): SWArena {
        if ($sign === null) {
            $sign = SignFactory::getInstance()->getRandomSign();
        }

        if ($sign === null) {
            throw new PluginException('SWSign was received null after get a random sign');
        }

        if ($map === null) {
            $map = MapFactory::getInstance()->getRandomMap();
        }

        if ($map === null) {
            throw new PluginException('SWMap was received null after get a random level');
        }

        $arena = new SWArena($this->gamesPlayed++, $map);

        $sign->assignArena($arena);
        $arena->signId = $sign->getIdNonNull();

        return $this->arenas[$arena->getId()] = $arena;
    }

    /**
     * @return SWArena|null
     */
    public function getRandomArena(): ?SWArena {
        /** @var SWArena|null $betterArena */
        $betterArena = null;

        foreach ($this->arenas as $arena) {
            if (!$arena->isAllowedJoin()) {
                continue;
            }

            if ($betterArena == null) {
                $betterArena = $arena;

                continue;
            }

            if (count($betterArena->getPlayers()) > count($arena->getPlayers())) {
                continue;
            }

            $betterArena = $arena;
        }

        if ($betterArena == null && SkyWars::isUnderDevelopment()) {
            $betterArena = $this->registerNewArena();
        }

        return $betterArena;
    }

    /**
     * @param SWMap $map
     *
     * @return SWArena[]
     */
    public function getArenas(SWMap $map): array {
        /** @var SWArena[] $arenas */
        $arenas = [];

        foreach ($this->arenas as $arena) {
            if (strtolower($arena->getMap()->getMapName()) != strtolower($map->getMapName())) {
                continue;
            }

            $arenas[$arena->getId()] = $arena;
        }

        return $arenas;
    }

    /**
     * @param Player $player
     *
     * @return SWArena|null
     */
    public function getPlayerArena(Player $player): ?SWArena {
        foreach ($this->arenas as $arena) {
            if ($arena->inArenaAsPlayer($player) || $arena->inArenaAsQueued($player)) {
                return $arena;
            }
        }

        return null;
    }

    /**
     * @param Player $player
     *
     * @return SWPlayer|null
     */
    public function getPlayer(Player $player): ?SWPlayer {
        return ($arena = $this->getPlayerArena($player)) !== null ? $arena->getPlayer($player) : null;
    }
}