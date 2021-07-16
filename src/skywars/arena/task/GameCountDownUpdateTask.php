<?php

declare(strict_types=1);

namespace skywars\arena\task;

use pocketmine\scheduler\Task;
use skywars\arena\SWArena;

class GameCountDownUpdateTask extends Task {

    public const TASK_NAME = 'count_down_task';

    /** @var SWArena */
    private $arena;
    /** @var int */
    private $initialCountdown = 60;
    /** @var int */
    private $countdown = 60;

    /**
     * GameUpdateTask constructor.
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

        $playersQueued = $arena->getPlayersQueued();

        if ($arena->worldWasGenerated() && !empty($playersQueued)) {
            foreach ($playersQueued as $player) {
                $arena->addPlayer($player);
            }

            return;
        }

        if ($arena->getStatus() == SWArena::STATUS_IN_GAME) {
            $this->cancel();

            return;
        }

        $joined = count($arena->getPlayers());

        if ($joined >= $arena->getMap()->getMinSlots()) {
            if (in_array($this->countdown, [60, 50, 40, 30, 20, 15, 10]) || ($this->countdown > 0 && $this->countdown < 6)) {
                $arena->broadcastMessage('Game is starting in ' . $this->countdown . ' second');
            }

            if ($this->countdown < 11 && $arena->getStatus() == SWArena::STATUS_WAITING) {
                $arena->setStatus(SWArena::STATUS_STARTING);
            }

            if ($this->countdown == 1) {
                foreach ($arena->getPlayers() as $player) {
                    $player->matchAttributes();
                }

                $arena->setStatus(SWArena::STATUS_IN_GAME);

                $this->cancel();
            }

            $this->countdown--;
        } else if ($this->countdown !== $this->initialCountdown) {
            $arena->broadcastMessage('&cWe don\'t have enough players! Start cancelled.');

            $arena->setStatus(SWArena::STATUS_WAITING);

            $this->countdown = $this->initialCountdown;
        }
    }

    protected function cancel(): void {
        if (($handler = $this->getHandler()) != null) {
            $handler->cancel();
        }
    }
}