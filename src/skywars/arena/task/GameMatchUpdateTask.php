<?php

declare(strict_types=1);

namespace skywars\arena\task;

use pocketmine\scheduler\Task;
use skywars\arena\SWArena;
use skywars\factory\SignFactory;

class GameMatchUpdateTask extends Task {

    /** @var SWArena */
    private $arena;
    /** @var int */
    private $time = 0;
    /** @var int */
    private $eventTime = 60;

    /**
     * GameMatchUpdateTask constructor.
     *
     * @param SWArena $arena
     */
    public function __construct(SWArena $arena) {
        $this->arena = $arena;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun(int $currentTick) {
        $arena = $this->arena;

        if (!$arena->worldWasGenerated()) {
            $arena->forceClose();

            $this->cancel();

            return;
        }

        if ($arena->getStatus() !== SWArena::STATUS_IN_GAME) {
            $this->cancel();

            return;
        }

        $this->time++;
        $this->eventTime--;

        if ($this->time == 5) {
            if (($sign = SignFactory::getInstance()->getSignRegistered($arena->signId)) !== null) {
                $sign->assignArena();

                $arena->signId = -1;
            }
        }
    }

    private function cancel(): void {
        $this->arena->cancelTask(get_class($this));
    }
}