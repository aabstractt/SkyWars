<?php

declare(strict_types=1);

namespace skywars\player;

use pocketmine\level\Level;
use pocketmine\utils\TextFormat;
use skywars\arena\SWArena;
use pocketmine\Player;
use pocketmine\plugin\PluginException;
use pocketmine\Server;

class SWPlayer {

    /** @var string */
    private $name;
    /** @var SWArena */
    private $arena;
    /** @var int */
    private $slot = -1;

    /**
     * SWPlayer constructor.
     *
     * @param string  $name
     * @param SWArena $arena
     */
    public function __construct(string $name, SWArena $arena) {
        $this->name = $name;

        $this->arena = $arena;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return SWArena
     */
    public function getArena(): SWArena {
        return $this->arena;
    }

    /**
     * @return Player|null
     */
    public function getInstance(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }

    /**
     * @return Player
     */
    public function getInstanceNonNull(): Player {
        $instance = $this->getInstance();

        if ($instance == null) {
            throw new PluginException('Player received null');
        }

        return $instance;
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message): void {
        $instance = $this->getInstance();

        if ($instance == null) {
            return;
        }

        $instance->sendMessage(TextFormat::colorize($message));
    }

    /**
     * @param int $slot
     */
    public function setSlot(int $slot): void {
        $this->slot = $slot;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        return $this->slot;
    }

    /**
     * @param Level|null $level
     * @return bool
     */
    public function isInsideArena(Level $level = null): bool {
        if ($level == null) {
            $level = $this->getInstanceNonNull()->getLevelNonNull();
        }

        return $level->getFolderName() == $this->arena->getWorldName();
    }

    /**
     * Give player attributes when join a arena
     */
    public function lobbyAttributes(): void {
        $instance = $this->getInstance();

        if ($instance == null) {
            return;
        }

        $slot = $this->arena->getFirstSlot();

        if ($slot == -1) {
            return;
        }

        $this->slot = $slot;

        try {
            $instance->teleport($this->arena->getMap()->getSpawnLocation($slot, $this->arena->getWorldNonNull()));
        } catch (PluginException $e) {
            $instance->sendMessage($e->getMessage());

            return;
        }

        $instance->getInventory()->clearAll();
        $instance->getArmorInventory()->clearAll();
        $instance->getCursorInventory()->clearAll();

        $instance->removeAllEffects();
        $instance->removeTitles();

        $instance->setFlying(false);
        $instance->setAllowFlight(false);
        $instance->setGamemode(Player::ADVENTURE);
    }

    public function matchAttributes(): void {

    }

    public function spectatorAttributes(): void {

    }

    public function defaultAttributes(): void {

    }

    public function forceRemove(): void {
        $instance = $this->getInstance();

        if ($instance == null) {
            return;
        }

        $arena = $this->arena;

        if ($arena->inArenaAsPlayer($instance)) {
            $arena->removePlayer($instance);
        } else if ($arena->inArenaAsSpectator($instance)) {
            $arena->removeSpectator($instance);
        }
    }
}