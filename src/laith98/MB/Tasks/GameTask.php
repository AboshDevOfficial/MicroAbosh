<?php

namespace laith98\MB\Tasks;

use pocketmine\scheduler\Task;

use laith98\MB\Main;

class GameTask extends Task
{
	/** @var bool */
    public $tick = false;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        //$this->tick = (bool) $plugin->configs["sign.tick"];
    }

    public function onRun(int $tick) : void
    {
        $owner = $this->plugin;

        foreach ($owner->arenas as $arena) {
            $arena->tick();
        }

        if ($this->tick && ($tick % 5 === 0)) {
            $owner->refreshSigns();
        }
    }
}