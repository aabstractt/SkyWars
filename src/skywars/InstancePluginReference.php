<?php

declare(strict_types=1);

namespace skywars;

trait InstancePluginReference {

    /** @var self */
    private static $instance;

    /**
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}