<?php

declare(strict_types=1);

namespace skywars\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use skywars\factory\ArenaFactory;
use skywars\factory\SignFactory;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        SignFactory::getInstance()->signRegister = array_diff(SignFactory::getInstance()->signRegister, [$player->getName()]);

        $arena = ArenaFactory::getInstance()->getPlayerArena($player);

        if ($arena == null) {
            return;
        }

        $arena->removePlayer($player, false);
    }
}