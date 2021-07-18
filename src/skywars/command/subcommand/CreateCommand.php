<?php

declare(strict_types=1);

namespace skywars\command\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use skywars\asyncio\FileCopyAsyncTask;
use skywars\command\SubCommand;
use skywars\factory\MapFactory;
use skywars\SkyWars;

class CreateCommand extends SubCommand {

    /**
     * @param CommandSender $sender
     * @param array $args
     */
    public function run(CommandSender $sender, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        if (!isset($args[0], $args[1], $args[2])) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /skywars ' . $this->getName() . ' <lobby> <minSlots> <maxSlots>');

            return;
        }

        $level = $sender->getLevelNonNull();

        if ($level === Server::getInstance()->getDefaultLevel()) {
            $sender->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

            return;
        }

        $mapName = $level->getFolderName();

        if (MapFactory::getInstance()->getMapRegistered($mapName) != null) {
            $sender->sendMessage(TextFormat::RED . 'This map already exists.');

            return;
        }

        $level->save(true);

        $data = [
            'mapName' => $mapName,
            'lobbyMap' => $args[0],
            'minSlots' => (int) $args[1],
            'maxSlots' => (int) $args[2]
        ];

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Server::getInstance()->getDataPath() . '/worlds/' . $mapName, SkyWars::getInstance()->getDataFolder() . '/arenas/' . $mapName, function () use ($sender, $mapName, $data) {
            MapFactory::getInstance()->registerNewMap($mapName, $data, true);

            $sender->sendMessage(TextFormat::GREEN . 'Successfully created ' . $mapName);

            $sender->getInventory()->clearAll();
            $sender->getInventory()->setItem(0, (Item::get(Item::WOODEN_PICKAXE))->setCustomName(TextFormat::RESET . TextFormat::GREEN . 'Area spawn'));
        }));
    }
}