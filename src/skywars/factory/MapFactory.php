<?php

namespace skywars\factory;

use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use skywars\arena\SWMap;
use skywars\InstancePluginReference;
use skywars\SkyWars;

class MapFactory {

    use InstancePluginReference;

    /** @var array<string, SWMap> */
    private $mapStorage = [];

    public function init(): void {
        foreach ((new Config(SkyWars::getInstance()->getDataFolder() . 'maps.json'))->getAll() as $mapName => $data) {
            $this->registerNewMap((string) $mapName, $data);
        }

        Server::getInstance()->getLogger()->info(TextFormat::AQUA . 'SkyWars: ' . count($this->mapStorage) . ' map(s) loaded.');
    }

    /**
     * @param string $mapName
     * @param array  $data
     * @param bool   $serialize
     */
    public function registerNewMap(string $mapName, array $data, bool $serialize = false): void {
        $map = new SWMap($mapName, $data);

        $this->mapStorage[strtolower($mapName)] = $map;

        if ($serialize) {
            $map->serialize();
        }
    }

    /**
     * @param string $mapName
     *
     * @return SWMap|null
     */
    public function getMapRegistered(string $mapName): ?SWMap {
        return $this->mapStorage[strtolower($mapName)] ?? null;
    }

    public function getRandomMap(): ?SWMap {
        /** @var SWMap|null $betterMap */
        $betterMap = null;

        foreach ($this->mapStorage as $map) {
            if ($betterMap == null) {
                $betterMap = $map;

                continue;
            }

            if (count(ArenaFactory::getInstance()->getArenas($map)) > count(ArenaFactory::getInstance()->getArenas($betterMap))) {
                continue;
            }

            $betterMap = $map;
        }

        return $betterMap;
    }
}