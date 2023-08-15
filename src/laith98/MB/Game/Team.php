<?php

declare(strict_types=1);

namespace laith98\MB\Game;

use pocketmine\Player;

class Team
{

    /** @var Player[] $players */
    protected $players = [];

    /** @var string $color */
    protected $color;

    /** @var string $name */
    protected $name;

    /** @var int $dead */
    public $dead = 0;
	
	/** @var int $maxplayers */
    public $maxplayers = 4;


    /**
     * Team constructor.
     * @param string $name
     * @param string $color
     */
    public function __construct(string $name, string $color)
    {
        $this->name = $name;
        $this->color = $color;
    }

    /**
     * @param Player $player
     */
    public function add(Player $player) : void{
		if(count($this->players) < $this->maxplayers){
			$this->players[$player->getRawUniqueId()] = $player;
		}
    }

    public function remove(Player $player) : void{
        unset($this->players[$player->getRawUniqueId()]);
    }

    /**
     * @return string
     */
    public function getColor() : string{
        return $this->color;
    }

    /**
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

    /**
     * @return array
     */
    public function getPlayers() : array{
        return $this->players;
    }

    public function reset() : void{
        $this->players = array();
    }

}