<?php

declare(strict_types=1);

namespace skywars\listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\tile\Sign;
use skywars\factory\SignFactory;

class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $ev
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onBlockBreakEvent(BlockBreakEvent $ev): void {
        $player = $ev->getPlayer();

        if (!$player->hasPermission('sw.sign')) {
            return;
        }

        $instance = SignFactory::getInstance();

        if (!in_array($player->getName(), $instance->signRegister)) {
            return;
        }

        if ($player->getLevelNonNull() !== Server::getInstance()->getDefaultLevel()) {
            return;
        }

        $tile = $player->getLevelNonNull()->getTile($ev->getBlock());

        if (!$tile instanceof Sign) {
            return;
        }

        $instance->signRegister = array_diff($instance->signRegister, [$player->getName()]);

        $instance->registerNewSign(['x' => $tile->getFloorX(), 'y' => $tile->getFloorY(), 'z' => $tile->getFloorZ()]);
    }
}