<?php

declare(strict_types=1);

namespace skywars\listener;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\Player;
use skywars\factory\ArenaFactory;

class EntityLevelChangeListener implements Listener {

    /**
     * @param EntityLevelChangeEvent $ev
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onEntityLevelChangeEvent(EntityLevelChangeEvent $ev): void {
        $entity = $ev->getEntity();

        if (!$entity instanceof Player) {
            return;
        }

        $arena = ArenaFactory::getInstance()->getPlayerArena($entity);

        if ($arena == null) {
            return;
        }

        $player = $arena->getPlayerOrSpectator($entity);

        if ($player == null) {
            return;
        }

        if (!$player->isInsideArena($ev->getTarget())) {
            $player->forceRemove();
        }
    }
}