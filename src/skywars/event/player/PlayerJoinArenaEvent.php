<?php

declare(strict_types=1);

namespace skywars\event\player;

use pocketmine\level\Location;
use skywars\player\SWPlayer;

class PlayerJoinArenaEvent extends PlayerEvent {

    /** @var Location */
    private $spawnLocation;

    /**
     * PlayerJoinArenaEvent constructor.
     *
     * @param SWPlayer $player
     * @param Location $spawnLocation
     */
    public function __construct(SWPlayer $player, Location $spawnLocation) {
        parent::__construct($player);

        $this->spawnLocation = $spawnLocation;
    }

    /**
     * @return Location
     */
    public function getSpawnLocation(): Location {
        return $this->spawnLocation;
    }
}