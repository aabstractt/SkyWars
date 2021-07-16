<?php

declare(strict_types=1);

namespace skywars\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use skywars\factory\ArenaFactory;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        $arena = ArenaFactory::getInstance()->getPlayerArena($player);

        if ($arena == null) {
            return;
        }

        $arena->removePlayer($player, false);
    }
}