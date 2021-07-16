<?php

declare(strict_types=1);

namespace skywars\arena;

use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use skywars\factory\SignFactory;
use skywars\SkyWars;

class SWSign extends Position{

    /** @var int */
    private $id;
    /** @var SWArena|null */
    private $arena = null;

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
     * @return int
     */
    public function getId(): int {
        return $this->id;
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
    public function assignArena(SWArena $arena = null): void {
        $this->arena = $arena;

        $this->handleUpdate();
    }

    public function handleUpdate(): void {
        $arena = $this->arena;

        $tile = $this->getTile();

        if (!$tile instanceof Sign) {
            if ($arena === null) {
                SignFactory::getInstance()->assignNewSign($arena);
            }

            return;
        }

        if ($arena === null) {
            $tile->setText(TextFormat::DARK_PURPLE . '-------------', TextFormat::BLUE . 'SEARCHING', TextFormat::BLUE . 'FOR GAMES', TextFormat::DARK_PURPLE . '-------------');
        } else {
            $tile->setText(TextFormat::BLACK . TextFormat::BOLD . 'SkyWars', $arena->getStatusColor(), $arena->getMap()->getMapName(), count($arena->getPlayers()) . '/' . $arena->getMap()->getMaxSlots());
        }
    }

    /**
     * @param array $data
     */
    public function serialize(array $data): void {
        $config = new Config(SkyWars::getInstance()->getDataFolder() . 'sign.json');

        $serialized = $config->getAll();

        $serialized[] = $data;

        $config->setAll($serialized);
        $config->save();

        foreach ($serialized as $id => $data) {
            if ($data['x'] == $this->getFloorX() && $data['y'] == $this->getFloorY() && $data['z'] == $this->getFloorZ()) {
                $this->id = $id;

                return;
            }
        }
    }
}