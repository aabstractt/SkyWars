<?php

declare(strict_types=1);

namespace skywars\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use skywars\factory\ArenaFactory;
use skywars\factory\SignFactory;
use skywars\SkyWars;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        SignFactory::getInstance()->signRegister = array_diff(SignFactory::getInstance()->signRegister, [$player->getName()]);

        $player = ArenaFactory::getInstance()->getPlayer($player);

        if ($player == null) {
            return;
        }

        SkyWars::handlePlayerDeath($player, null);
    }
}