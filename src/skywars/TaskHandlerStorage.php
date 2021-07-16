<?php

declare(strict_types=1);

namespace skywars;

use pocketmine\scheduler\Task;

abstract class TaskHandlerStorage {

    /** @var array<string, int> */
    private $taskStorage = [];

    /**
     * @param string $taskName
     * @param Task   $task
     * @param int    $ticks
     */
    public function scheduleRepeatingTask(string $taskName, Task $task, int $ticks = 20): void {
        $this->taskStorage[$taskName] = $task->getTaskId();

        SkyWars::getInstance()->getScheduler()->scheduleRepeatingTask($task, $ticks);
    }

    /**
     * @param string $taskName
     */
    public function cancelTask(string $taskName): void {
        $taskId = $this->taskStorage[$taskName] ?? null;

        if ($taskId == null) {
            return;
        }

        SkyWars::getInstance()->getScheduler()->cancelTask($taskId);

        unset($this->taskStorage[$taskName]);
    }
}