<?php

declare(strict_types=1);

namespace skywars\arena;

use pocketmine\level\Position;
use pocketmine\plugin\PluginException;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use skywars\factory\ArenaFactory;
use skywars\factory\SignFactory;
use skywars\SkyWars;

class SWSign extends Position{

    /** @var int|null */
    private $id;
    /** @var SWArena|null */
    private $arena = null;
    /** @var int */
    private $tickWaiting = 0;

    /**
     * SWSign constructor.
     *
     * @param int|null $id
     * @param array    $data
     */
    public function __construct(?int $id, array $data) {
        parent::__construct((int) $data['x'], (int) $data['y'], (int) $data['z'], Server::getInstance()->getDefaultLevel());

        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    public function getIdNonNull(): int {
        $id = $this->id;

        if ($id === null) {
            throw new PluginException('SWSign id is null');
        }

        return $id;
    }

    /**
     * @return bool
     */
    public function wasAssigned(): bool {
        return $this->arena != null;
    }

    /**
     * @return Tile|null
     */
    private function getTile(): ?Tile {
        return $this->getLevelNonNull()->getTile($this);
    }

    /**
     * @param SWArena|null $arena
     */
    public function assignArena(?SWArena $arena = null): void {
        $this->arena = $arena;

        $this->handleUpdate();
    }

    public function handleUpdate(): void {
        $arena = $this->arena;

        $tile = $this->getTile();

        if (!$tile instanceof Sign) {
            if ($arena !== null) {
                SignFactory::getInstance()->assignNewSign($arena);
            }

            return;
        }

        if ($arena !== null) {
            $tile->setText(TextFormat::BLACK . TextFormat::BOLD . 'SkyWars', $arena->getStatusColor(), $arena->getMap()->getMapName(), count($arena->getPlayers()) . '/' . $arena->getMap()->getMaxSlots());

            return;
        }

        if ($this->tickWaiting > 5) {
            ArenaFactory::getInstance()->registerNewArena($this);

            $this->tickWaiting = 0;

            return;
        }

        $tile->setText(TextFormat::DARK_PURPLE . '-------------', TextFormat::BLUE . 'SEARCHING', TextFormat::BLUE . 'FOR GAMES', TextFormat::DARK_PURPLE . '-------------');

        $this->tickWaiting++;
    }

    /**
     * @param array $data
     */
    public function serialize(array $data): void {
        $config = new Config(SkyWars::getInstance()->getDataFolder() . 'sign.json');

        $serialized = $config->getAll();

        /** @var array<string, mixed> $serialized */
        $serialized[] = $data;

        $config->setAll($serialized);
        $config->save();

        foreach ($serialized as $id => $data) {
            if ($data['x'] == $this->getFloorX() && $data['y'] == $this->getFloorY() && $data['z'] == $this->getFloorZ()) {
                $this->id = (int) $id;

                return;
            }
        }
    }
}