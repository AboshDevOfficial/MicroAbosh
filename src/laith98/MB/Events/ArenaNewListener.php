<?php

namespace laith98\MB\Events;

use laith98\MB\Game\Arena;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\Player;

class ArenaNewListener implements Listener
{

    /**
     * @var Arena
     */
    public $plugin;

    public function __construct(Arena $arena)
    {
        $this->plugin = $arena;
    }

    public function onExact(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();
        if($player instanceof Player){
            if($this->plugin->inArena($player)){
                $event->setCancelled(true);
            }
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($this->plugin->inArena($player) and Arena::PLAYER_PLAYING or $this->plugin->inArena($player) and Arena::STATE_RUNNING) {
            if($block->getId() == Block::GLASS){
                $event->setCancelled(true);
            }
        }
    }
}