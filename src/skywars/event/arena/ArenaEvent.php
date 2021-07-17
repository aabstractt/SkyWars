<?php

declare(strict_types=1);

namespace skywars\event\arena;

use pocketmine\event\Event;
use skywars\arena\SWArena;

class ArenaEvent extends Event {

    /** @var SWArena */
    private $arena;

    /**
     * ArenaEvent constructor.
     *
     * @param SWArena $arena
     */
    public function __construct(SWArena $arena) {
        $this->arena = $arena;
    }

    /**
     * @return SWArena
     */
    public function getArena(): SWArena {
        return $this->arena;
    }
}