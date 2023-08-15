<?php

namespace laith98\MB\Utils;

/*  Copyright (2018 - 2020) (C) LaithYoutuber [HypixelPE DEv]
 *
 * Plugin By LaithYT , Gihhub:                                                                           
 *                                                                                                      
 *		88  		8855555555	88888889888	888888888888 88			88	 88888888888'8	888888888888'8  
 *		88			88		88		88			88		 88			88	 88		 	88	88			88  
 *		88			88		88		88			88	   	 88			88	 88			88	88			88  
 *		88			88		88		88			88		 88			88	 88			88	88			88  
 *		88			88		88		88			88		 88			88	 88			88	88			88  
 *		88			8855555588		88			88		 8855555555588   8888888855553	88555555555588	
 *		88			88		88		88			88		 88			88	 			88	88			88  
 *		88			88		88		88			88		 88			88	 			88	88			88  
 *		88			88		88		88			88		 88			88				88	88			88 
 *		85      	88		88		88			88		 88			88				88	88			88  
 *		8855555555	88		88	88888889888		88		 88			88   5555555555588	88888888888888  
 *		Dev 
 *		 
 *		Youtube: Laith Youtuber                                                                         
 *		Facebook: Laith A Al Haddad                                                                     
 *		Discord: Laith.97#8167                                                                          
 *
 */

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;

class Scoreboard extends PluginBase
{

    /** @var array $scoreboards */
    public static $scoreboards = array();

    /**
     * @param Player $player
     * @param string $objectiveName
     * @param string $displayName
     */
    public static function new(Player $player, string $objectiveName, string $displayName): void{
        if(isset(self::$scoreboards[$player->getName()])){
            self::remove($player);
        }
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = $objectiveName;
        $pk->displayName = $displayName;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->sendDataPacket($pk);
        self::$scoreboards[$player->getName()] = $objectiveName;
    }

    /**
     * @param Player $player
     */
    public static function remove(Player $player): void{
        $objectiveName = self::getObjectiveName($player);
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->sendDataPacket($pk);
        unset(self::$scoreboards[$player->getName()]);
    }

    /**
     * @param Player $player
     * @param int $score
     * @param string $message
     */
    public static function setLine(Player $player, int $score, string $message): void{
        if(!isset(self::$scoreboards[$player->getName()])){
            return;
        }
        if($score > 15 || $score < 1){
            error_log("Score must be between the value of 1-15. $score out of range");
            return;
        }
        $objectiveName = self::getObjectiveName($player);
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $objectiveName;
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = $message;
        $entry->score = $score;
        $entry->scoreboardId = $score;
        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;
        $player->sendDataPacket($pk);
    }

    /**
     * @param Player $player
     * @return string|null
     */
    public static function getObjectiveName(Player $player): ?string{
        return isset(self::$scoreboards[$player->getName()]) ? self::$scoreboards[$player->getName()] : null;
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
		if(isset(self::$scoreboards[($player->getName())])) unset(self::$scoreboards[$player->getName()]);
	}

}