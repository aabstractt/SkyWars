<?php

declare(strict_types=1);

namespace skywars\event\player;

use pocketmine\event\Event;
use skywars\player\SWPlayer;

class PlayerEvent extends Event {

    /** @var SWPlayer */
    private $player;

    /**
     * PlayerEvent constructor.
     *
     * @param SWPlayer $player
     */
    public function __construct(SWPlayer $player) {
        $this->player = $player;
    }

    /**
     * @return SWPlayer
     */
    public function getPlayer(): SWPlayer {
        return $this->player;
    }
}