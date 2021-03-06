<?php

declare(strict_types=1);

namespace skywars;

use pocketmine\scheduler\Task;
use ReflectionClass;
use ReflectionException;

abstract class TaskHandlerStorage {

    /** @var SkyWars */
    protected $plugin;
    /** @var array<string, int> */
    private $taskStorage = [];

    /**
     * TaskHandlerStorage constructor.
     */
    public function __construct() {
        $this->plugin = SkyWars::getInstance();
    }

    /**
     * @param Task $task
     * @param int  $ticks
     */
    public function scheduleRepeatingTask(Task $task, int $ticks = 20): void {
        $this->plugin->getScheduler()->scheduleRepeatingTask($task, $ticks);

        $class = new ReflectionClass($task);

        $this->taskStorage[strtolower($class->getShortName())] = $task->getTaskId();
    }

    /**
     * @param string $className
     *
     * @phpstan-param class-string<Task> $className
     */
    public function cancelTask(string $className): void {
        try {
            $class = new ReflectionClass($className);

            if (is_a($className, Task::class, true) && !$class->isAbstract()) {
                return;
            }

            $taskId = $this->taskStorage[strtolower($class->getShortName())] ?? null;

            if ($taskId == null) {
                return;
            }

            $this->plugin->getScheduler()->cancelTask($taskId);

            unset($this->taskStorage[strtolower($class->getShortName())]);
        } catch (ReflectionException $e) {
            $this->plugin->getLogger()->logException($e);
        }
    }
}