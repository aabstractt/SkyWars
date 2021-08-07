<?php

declare(strict_types=1);

namespace skywars\arena\api;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat;
use skywars\arena\SWArena;
use skywars\player\SWPlayer;

class Scoreboard {

    /** @var string */
    public const LIST = 'list';
    public const SIDEBAR = 'sidebar';

    /** @var int */
    public const ASCENDING = 0;
    public const DESCENDING = 1;

    /** @var SWArena */
    private $arena;
    /** @var string */
    public $displayName;
    /** @var string */
    private $objectiveName;
    /** @var string */
    private $displaySlot;
    /** @var int */
    private $sortOrder;

    /**
     * Scoreboard constructor.
     * @param SWArena $arena
     * @param string $title
     * @param string $displaySlot
     * @param int $sortOrder
     */
    public function __construct(SWArena $arena, string $title, string $displaySlot, int $sortOrder = self::DESCENDING) {
        $this->arena = $arena;

        $this->displayName = $title;

        $this->objectiveName = uniqid('', true);

        $this->displaySlot = $displaySlot;

        $this->sortOrder = $sortOrder;
    }

    /**
     * @param SWPlayer|null $player
     */
    public function removePlayer(SWPlayer $player = null): void {
        $players = $this->arena->getEveryone();

        if ($player !== null) {
            $players = [$player];
        }

        $pk = new RemoveObjectivePacket();

        $pk->objectiveName = $this->objectiveName;

        foreach ($players as $p) {
            $p->getInstanceNonNull()->sendDataPacket($pk);
        }
    }

    /**
     * @param SWPlayer|null $player
     */
    public function addPlayer(SWPlayer $player = null): void {
        $players = $this->arena->getEveryone();

        if ($player !== null) $players = [$player];

        $pk = new SetDisplayObjectivePacket();

        $pk->displaySlot = $this->displaySlot;

        $pk->objectiveName = $this->objectiveName;

        $pk->displayName = $this->displayName;

        $pk->criteriaName = 'dummy';

        $pk->sortOrder = $this->sortOrder;

        foreach ($players as $p ) {
            $p->getInstanceNonNull()->sendDataPacket($pk);
        }
    }

    /**
     * @param int           $line
     * @param string        $message
     * @param SWPlayer|null $player
     */
    public function setLine(int $line, string $message = '', SWPlayer $player = null): void {
        $this->setLines([$line => $message], $player);
    }

    /**
     * @param array $lines
     * @param SWPlayer|null $player
     */
    public function setLines(array $lines, ?SWPlayer $player = null): void {
        $players = $this->arena->getEveryone();

        if ($player !== null) $players = [$player];

        foreach ($players as $p) {
            $instance = $p->getInstanceNonNull();

            $instance->sendDataPacket($this->getPackets($lines, SetScorePacket::TYPE_REMOVE));

            $instance->sendDataPacket($this->getPackets($lines, SetScorePacket::TYPE_CHANGE));
        }
    }

    /**
     * @param array $lines
     * @param int $type
     * @return DataPacket
     */
    public function getPackets(array $lines, int $type): DataPacket {
        $pk = new SetScorePacket();

        $pk->type = $type;

        foreach ($lines as $line => $message) {
            $entry = new ScorePacketEntry();

            $entry->objectiveName = $this->objectiveName;

            $entry->score = $line;

            $entry->scoreboardId = $line;

            if ($type === SetScorePacket::TYPE_CHANGE) {
                if ($message == "") {
                    $message = str_repeat(' ', $line - 1);
                }

                $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

                $entry->customName = TextFormat::colorize($message) . ' ';
            }

            $pk->entries[] = $entry;
        }

        return $pk;
    }
}