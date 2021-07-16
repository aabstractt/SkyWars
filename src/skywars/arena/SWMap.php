<?php

declare(strict_types=1);

namespace skywars\arena;

use pocketmine\level\Level;
use pocketmine\plugin\PluginException;
use skywars\SkyWars;
use pocketmine\level\Location;
use pocketmine\utils\Config;

class SWMap {

    /** @var int */
    private $mapName;
    /** @var array */
    private $data;

    /**
     * SWMap constructor.
     *
     * @param string $mapName
     * @param array  $data
     */
    public function __construct(string $mapName, array $data) {
        $this->mapName = $mapName;

        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getMapName(): string {
        return $this->mapName;
    }

    /**
     * @return int
     */
    public function getMinSlots(): int {
        return $this->data['minSlots'];
    }

    /**
     * @return int
     */
    public function getMaxSlots(): int {
        return $this->data['maxSlots'];
    }

    /**
     * @param int      $slot
     * @param Location $loc
     */
    public function addSpawnLocation(int $slot, Location $loc): void {
        $this->data['spawns'][$slot] = ['x' => $loc->getFloorX(), 'y' => $loc->getFloorY(), 'z' => $loc->getFloorZ(), 'yaw' => $loc->yaw, 'pitch' => $loc->pitch];
    }

    /**
     * @param int   $slot
     * @param Level $level
     *
     * @return Location
     */
    public function getSpawnLocation(int $slot, Level $level): Location {
        $data = $this->data['spawns'][$slot] ?? [];

        if (empty($data)) {
            throw new PluginException('Spawn not found');
        }

        return new Location($data['x'], $data['y'], $data['z'], $data['yaw'], $data['pitch'], $level);
    }

    /**
     * @return array
     */
    public function getSlotsRegistered(): array {
        return array_keys($this->data['spawns'] ?? []);
    }

    /**
     * @return array
     */
    public function dataSerialized(): array {
        return $this->data;
    }

    public function serialize(): void {
        $config = new Config(SkyWars::getInstance()->getDataFolder() . 'maps.json');

        $config->set($this->getMapName(), $this->data);
        $config->save();
    }
}