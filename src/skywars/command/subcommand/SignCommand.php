<?php

declare(strict_types=1);

namespace skywars\command\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use skywars\command\SubCommand;
use skywars\factory\SignFactory;

class SignCommand extends SubCommand {

    /**
     * @param CommandSender $sender
     * @param array         $args
     */
    public function run(CommandSender $sender, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        SignFactory::getInstance()->signRegister[] = $sender->getName();
    }
}