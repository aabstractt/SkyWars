<?php

declare(strict_types=1);

namespace skywars\arena;

use pocketmine\level\Level as pocketLevel;
use pocketmine\utils\TextFormat;
use skywars\arena\api\Scoreboard;
use skywars\arena\task\GameCountDownUpdateTask;
use skywars\asyncio\FileCopyAsyncTask;
use skywars\SkyWars;
use skywars\player\SWPlayer;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\Player;
use pocketmine\Server;
use skywars\TaskHandlerStorage;

class SWArena extends TaskHandlerStorage {

    /** @var int */
    public const STATUS_WAITING = 1;
    public const STATUS_STARTING = 2;
    public const STATUS_IN_GAME = 3;

    /** @var int */
    private $id;
    /** @var SWMap */
    private $map;
    /** @var string */
    private $worldName;
    /** @var int */
    private $status = self::STATUS_WAITING;
    /** @var int */
    public $signId = -1;
    /** @var array<int, Player> */
    private $playersQueued = [];
    /** @var array<int, SWPlayer> */
    private $players = [];
    /** @var array<int, SWPlayer> */
    private $spectators = [];
    /** @var Scoreboard */
    private $scoreboard;
    /** @var array */
    private $slots;
    /** @var SkyWars */
    private $plugin;

    /**
     * SWArena constructor.
     *
     * @param int   $id
     * @param SWMap $map
     */
    public function __construct(int $id, SWMap $map) {
        $this->id = $id;

        $this->map = $map;

        $this->worldName = 'SW-' . $map->getMapName() . '(' . $id . ')';

        $this->slots = $map->getSlotsRegistered();

        $this->plugin = SkyWars::getInstance();

        $this->scoreboard = new Scoreboard($this,
            TextFormat::YELLOW . TextFormat::BOLD . strtoupper(SkyWars::getInstance()->getName()),
            Scoreboard::SIDEBAR);

        $this->scheduleRepeatingTask(GameCountDownUpdateTask::TASK_NAME, new GameCountDownUpdateTask($this));

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(
            $this->plugin->getDataFolder() . 'arenas/' . $map->getMapName(),
            Server::getInstance()->getDataPath() . 'worlds/' . $this->worldName,
            function () {
                Server::getInstance()->loadLevel($this->worldName);

                $level = Server::getInstance()->getLevelByName($this->worldName);

                if ($level == null) {
                    return;
                }

                $level->setTime(pocketLevel::TIME_DAY);
                $level->stopTime();

                $this->plugin->getLogger()->info('Map ' . $this->worldName . ' was generated.');
            }
        ));
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return SWMap
     */
    public function getMap(): SWMap {
        return $this->map;
    }

    /**
     * @return string
     */
    public function getWorldName(): string {
        return $this->worldName;
    }

    /**
     * @return Level
     */
    public function getWorldNonNull(): Level {
        $level = $this->getWorld();

        if ($level == null) {
            throw new LevelException('SkyWars level is null');
        }

        return $level;
    }

    /**
     * @return Level|null
     */
    public function getWorld(): ?Level {
        return Server::getInstance()->getLevelByName($this->getWorldName());
    }

    /**
     * @return Scoreboard
     */
    public function getScoreboard(): Scoreboard {
        return $this->scoreboard;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatusColor(): string {
        if ($this->status == self::STATUS_IN_GAME) {
            return TextFormat::RED . 'In-Game';
        } else if ($this->isFull()) {
            return TextFormat::DARK_PURPLE . 'Full';
        } else if ($this->status == self::STATUS_STARTING) {
            return TextFormat::YELLOW . 'Starting';
        }

        return TextFormat::GREEN . 'Waiting';
    }

    /**
     * @return int
     */
    public function getFirstSlot(): int {
        $slots = $this->slots;

        shuffle($slots);

        if (empty($slots)) {
            return -1;
        }

        $key = array_rand($slots);

        $slot = array_values($slots)[$key] ?? -1;

        if ($slot != -1) {
            unset($slots[$key]);

            $this->slots = $slots;
        }

        return $slot;
    }

    /**
     * @return bool
     */
    public function worldWasGenerated(): bool {
        return $this->getWorld() != null;
    }

    /**
     * @return bool
     */
    public function isAllowedJoin(): bool {
        return $this->status < self::STATUS_IN_GAME || $this->isFull();
    }

    /**
     * @return bool
     */
    public function isFull(): bool {
        return count($this->players) > $this->map->getMaxSlots();
    }

    /**
     * @return SWPlayer[]
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @param Player $player
     */
    public function addPlayer(Player $player): void {
        if (!$this->worldWasGenerated()) {
            $this->playersQueued[] = $player;

            return;
        }

        $this->removePlayerQueued($player);

        if (!$player->isConnected() || $this->inArenaAsPlayer($player) || $this->inArenaAsSpectator($player) || $this->status >= self::STATUS_IN_GAME) {
            return;
        }

        $player = new SWPlayer($player->getName(), $this);

        $player->lobbyAttributes();

        if ($player->getSlot() == -1) {
            return;
        }

        $this->players[$player->getSlot()] = $player;

        $this->broadcastMessage($player->getName() . ' joined (' . count($this->players) . '/' . $this->map->getMaxSlots() . ')');

        $this->getScoreboard()->addPlayer($player);

        $this->getScoreboard()->setLines(SkyWars::translateScoreboard('waiting-scoreboard', [
            'event_name' => 'Start',
            'event_time' => -1,
            'title' => count($this->players) > $this->map->getMinSlots() ? 'with' : 'without',
            'players_count' => count($this->players),
            'map' => $this->map->getMapName()
        ]), $player);
    }

    /**
     * @param Player $player
     * @param bool   $defaultAttributes
     */
    public function removePlayer(Player $player, bool $defaultAttributes = true): void {
        if (in_array($player, $this->playersQueued)) {
            $this->removePlayerQueued($player);
        }

        $player = $this->getPlayer($player);

        if ($player == null) {
            return;
        }

        if ($defaultAttributes) {
            $player->defaultAttributes();
        }

        if ($this->status == self::STATUS_WAITING) {
            $this->slots[] = $player->getSlot();
        }

        unset($this->players[$player->getSlot()]);
    }

    /**
     * @param Player $player
     *
     * @return SWPlayer|null
     */
    public function getPlayer(Player $player): ?SWPlayer {
        foreach ($this->players as $target) {
            if (strtolower($target->getName()) == strtolower($player->getName())) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function inArenaAsPlayer(Player $player): bool {
        return $this->getPlayer($player) != null;
    }

    /**
     * @return SWPlayer[]
     */
    public function getSpectators(): array {
        return $this->spectators;
    }

    /**
     * @param SWPlayer $player
     */
    public function addSpectator(SWPlayer $player): void {
        $player->spectatorAttributes();

        $this->spectators[$player->getSlot()] = $player;
    }

    /**
     * @param Player $player
     * @param bool   $defaultAttributes
     */
    public function removeSpectator(Player $player, bool $defaultAttributes = true): void {
        $player = $this->getSpectator($player);

        if ($player == null) {
            return;
        }

        if ($defaultAttributes) {
            $player->defaultAttributes();
        }

        unset($this->spectators[$player->getSlot()]);
    }

    /**
     * @param Player $player
     *
     * @return SWPlayer|null
     */
    public function getSpectator(Player $player): ?SWPlayer {
        foreach ($this->spectators as $target) {
            if (strtolower($target->getName()) == strtolower($player->getName())) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function inArenaAsSpectator(Player $player): bool {
        return $this->getSpectator($player) != null;
    }

    /**
     * @param Player $player
     *
     * @return SWPlayer|null
     */
    public function getPlayerOrSpectator(Player $player): ?SWPlayer {
        return $this->getPlayer($player) ?? $this->getSpectator($player);
    }

    /**
     * @return Player[]
     */
    public function getPlayersQueued(): array {
        return $this->playersQueued;
    }

    /**
     * @param Player $player
     */
    public function removePlayerQueued(Player $player): void {
        $this->playersQueued = array_diff($this->playersQueued, [$player]);
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function inArenaAsQueued(Player $player): bool {
        return in_array($player, $this->playersQueued);
    }

    /**
     * @return SWPlayer[]
     */
    public function getEveryone(): array {
        return array_merge($this->players, $this->spectators);
    }

    /**
     * @param string $message
     */
    public function broadcastMessage(string $message): void {
        foreach ($this->getEveryone() as $player) {
            $player->sendMessage($message);
        }
    }

    /**
     * @return SkyWars
     */
    protected function getPlugin(): SkyWars {
        return $this->plugin;
    }
}