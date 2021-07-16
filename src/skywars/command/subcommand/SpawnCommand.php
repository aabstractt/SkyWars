<?php

declare(strict_types=1);

namespace skywars\command\subcommand;

use skywars\command\SubCommand;
use skywars\factory\MapFactory;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SpawnCommand extends SubCommand {

    /**
     * @param CommandSender $sender
     * @param array $args
     */
    public function run(CommandSender $sender, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        if (empty($args[0])) {
            $sender->sendMessage(TextFormat::RED . '/skywars ' . $this->getName() . ' <team>');

            return;
        }

        if ($sender->getLevelNonNull() === Server::getInstance()->getDefaultLevel()) {
            $sender->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

            return;
        }

        $map = MapFactory::getInstance()->getMapRegistered($sender->getLevelNonNull()->getFolderName());

        if ($map == null) {
            $sender->sendMessage(TextFormat::RED . 'This map doesn\'t exist.');

            return;
        }

        if (!is_numeric(($slot = (int)$args[0])) || ($slot > $map->getMaxSlots() || $slot <= 0)) {
            $sender->sendMessage(TextFormat::RED . 'You must specify a valid slot.');

            return;
        }

        $map->addSpawnLocation($slot, ($loc = $sender->getLocation()));

        $sender->sendMessage(TextFormat::BLUE . 'Team spawn ' . $slot . ' set to §6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ() . ' §6Yaw:§b ' . $loc->getYaw() . ' §6Pitch:§b ' . $loc->getPitch());

        $map->serialize();
    }
}