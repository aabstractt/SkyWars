<?php

declare(strict_types=1);

namespace skywars\factory;

use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use skywars\arena\SWArena;
use skywars\arena\SWSign;
use skywars\InstancePluginReference;
use skywars\SkyWars;

class SignFactory {

    use InstancePluginReference;

    /** @var array<int, SWSign> */
    private $signStorage = [];
    /** @var array */
    public $signRegister = [];

    /** @noinspection PhpUnusedParameterInspection */
    public function init(): void {
        foreach ((new Config(SkyWars::getInstance()->getDataFolder() . 'sign.json'))->getAll() as $id => $data) {
            $this->registerNewSign($data, (int)$id);
        }

        SkyWars::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            foreach ($this->signStorage as $sign) {
                $sign->handleUpdate();
            }
        }), 20);
    }

    /**
     * @param array    $data
     * @param int|null $id
     */
    public function registerNewSign(array $data, int $id = null): void {
        $sign = new SWSign($id, $data);

        if ($id === null) {
            $sign->serialize($data);
        }

        $sign->assignArena(ArenaFactory::getInstance()->registerNewArena($sign));

        $this->signStorage[$sign->getIdNonNull()] = $sign;
    }

    /**
     * @param int $id
     *
     * @return SWSign|null
     */
    public function getSignRegistered(int $id): ?SWSign {
        return $this->signStorage[$id] ?? null;
    }

    /**
     * @param SWArena     $arena
     * @param SWSign|null $sign
     *
     * @return bool
     */
    public function assignNewSign(SWArena $arena, SWSign $sign = null): bool {
        if ($sign === null) {
            $sign = $this->getRandomSign();
        }

        if ($sign == null) {
            return false;
        }

        $sign->assignArena($arena);

        $arena->signId = $sign->getIdNonNull();

        return true;
    }

    /**
     * @return SWSign|null
     */
    public function getRandomSign(): ?SWSign {
        $signStorage = $this->signStorage;

        shuffle($signStorage);

        foreach ($signStorage as $sign) {
            if ($sign->getId() === null || $sign->wasAssigned()) {
                continue;
            }

            return $sign;
        }

        return null;
    }
}