<?php

declare(strict_types=1);

namespace skywars\arena\task;

use pocketmine\scheduler\Task;
use skywars\arena\SWArena;
use skywars\event\arena\ArenaStartEvent;
use skywars\SkyWars;

class GameCountDownUpdateTask extends Task {

    /** @var SWArena */
    private $arena;
    /** @var int */
    private $initialCountdown = 10;
    /** @var int */
    private $countdown = 10;

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

        $arena->getScoreboard()->setLines(SkyWars::translateScoreboard('waiting-scoreboard', [
            'event_name' => 'Start',
            'event_time' => $this->countdown,
            'title' => $joined > $arena->getMap()->getMinSlots() ? 'with' : 'without',
            'players_count' => $joined
        ], 'update'));

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

                $arena->getScoreboard()->removePlayer();
                $arena->getScoreboard()->addPlayer();

                $arena->setStatus(SWArena::STATUS_IN_GAME);

                $this->cancel();

                (new ArenaStartEvent($arena))->call();

                $arena->scheduleRepeatingTask(new GameMatchUpdateTask($arena));

                return;
            }

            $this->countdown--;
        } else if ($this->countdown !== $this->initialCountdown) {
            $arena->broadcastMessage('&cWe don\'t have enough players! Start cancelled.');

            $arena->setStatus(SWArena::STATUS_WAITING);

            $this->countdown = $this->initialCountdown;
        }
    }

    private function cancel(): void {
        $this->arena->cancelTask(self::class);
    }
}