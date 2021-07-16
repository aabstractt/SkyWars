<?php

declare(strict_types=1);

namespace skywars\command;

use skywars\command\subcommand\CreateCommand;
use skywars\command\subcommand\JoinCommand;
use skywars\command\subcommand\SignCommand;
use skywars\command\subcommand\SpawnCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\utils\TextFormat;

class SWCommand extends Command {

    /** @var array<string, SubCommand> */
    private $commands = [];

    /**
     * SWCommand constructor.
     */
    public function __construct() {
        parent::__construct('skywars', 'SkyWars Command', '/sw help', ['sw']);

        $this->addCommand(
            new CreateCommand('create', 'create'),
            new SpawnCommand('spawn', 'spawn'),
            new SignCommand('sign', 'sign'),
            new JoinCommand('join')
        );

        $this->setPermission('skywars.command');
    }

    /**
     * @param SubCommand ...$commands
     */
    protected function addCommand(SubCommand ...$commands): void {
        foreach ($commands as $command) {
            $this->commands[strtolower($command->getName())] = $command;
        }
    }

    /**
     * @param string $name
     * @return SubCommand|null
     */
    protected function getCommand(string $name): ?SubCommand {
        return $this->commands[strtolower($name)] ?? null;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) == 0) {
            throw new InvalidCommandSyntaxException();
        }

        $name = array_shift($args);

        if ($name == null) {
            throw new InvalidCommandSyntaxException();
        }

        if ($name == 'help') {
            $sender->sendMessage(TextFormat::BLUE . 'SkyWars Commands.');

            foreach ($this->commands as $command) {
                $sender->sendMessage(TextFormat::RED . '/' . $commandLabel . ' ' . $command->getName());
            }

            return;
        }

        $command = $this->getCommand($name);

        if ($command == null) {
            throw new InvalidCommandSyntaxException();
        }

        if (($mainPermission = $this->getPermission()) != null &&
            ($permission = $command->getPermission()) != null &&
            !$sender->hasPermission($mainPermission . '.' . $permission)) {
            $sender->sendMessage(TextFormat::RED . 'You don\'t have permissions to use this command');

            return;
        }

        $command->run($sender, $args);
    }
}