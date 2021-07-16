<?php

declare(strict_types=1);

namespace skywars;

use pocketmine\scheduler\Task;
use ReflectionClass;

abstract class TaskHandlerStorage {

    /** @var array<string, int> */
    private $taskStorage = [];

    /**
     * @param Task $task
     * @param int  $ticks
     */
    public function scheduleRepeatingTask(Task $task, int $ticks = 20): void {
        SkyWars::getInstance()->getScheduler()->scheduleRepeatingTask($task, $ticks);

        $class = new ReflectionClass($task);

        $this->taskStorage[strtolower($class->getShortName())] = $task->getTaskId();
    }

    /**
     * @param string $className
     */
    public function cancelTask(string $className): void {
        try {
            $class = new ReflectionClass($className);

            $taskId = $this->taskStorage[strtolower($class->getShortName())] ?? null;

            if ($taskId == null) {
                return;
            }

            SkyWars::getInstance()->getScheduler()->cancelTask($taskId);

            unset($this->taskStorage[strtolower($class->getShortName())]);
        } catch (\ReflectionException $e) {
            SkyWars::getInstance()->getLogger()->logException($e);
        }
    }
}