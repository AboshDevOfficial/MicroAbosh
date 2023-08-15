<?php

namespace laith98\MB\Events;

use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\event\player\{PlayerExhaustEvent,
    PlayerJoinEvent,
    PlayerRespawnEvent,
    PlayerQuitEvent,
    PlayerCommandPreprocessEvent,
    PlayerMoveEvent,
    PlayerInteractEvent,
    PlayerDropItemEvent,
    PlayerDeathEvent,
    PlayerItemHeldEvent};

use pocketmine\block\Block;
use pocketmine\event\block\{
	BlockBreakEvent,
	SignChangeEvent,
	BlockPlaceEvent
	};

use pocketmine\Server;

use pocketmine\entity\Living;
use pocketmine\entity\Entity;

use pocketmine\event\entity\{
	EntityDamageByEntityEvent,
	EntityDamageEvent,
	EntityLevelChangeEvent,
	EntityTeleportEvent,
	
	};

use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;

use pocketmine\network\mcpe\protocol\LevelEventPacket;

use pocketmine\level\Position;
use pocketmine\level\Location;

use pocketmine\Item\Item;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\inventory\Inventory;
use pocketmine\tile\Tile;
use pocketmine\nbt\NBT;
use pocketmine\utils\TextFormat;
use pocketmine\utils\{Config, TextFormat as TF};
use pocketmine\level\sound\{
	ClickSound, 
	EndermanTeleportSound, 
	Sound
	};

use onebone\economyapi\EconomyAPI;

use laith98\MB\Main;
use laith98\MB\Game\Arena;
use laith98\MB\Utils\Utils;

class EventListener implements Listener
{
	/** @var Main */
    private $plugin;
	
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
	
	public function getLang(string $n){
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("LobbyCore_System");// get Player Lang u can set plugin name
		if($api !== null){
			$m = $api->getPlayerLang($n);
			if($m == "EN"){
				return "EN";
			} elseif($m == "AR"){
				return "AR";	
			}
		}
	}
	
	public function OpenKitUI(Player $player){
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
			}
			$arena = $this->plugin->getPlayerArena($player);
			 switch($result){
				case 0:
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				
				if(!isset($arena->KIT_0[$player->getName()])){
					$arena->KIT_0[] = $player->getName();
					
				}
				break;
				
				case 1:
				
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				
				if(!isset($arena->KIT_1[$player->getName()])){
					$arena->KIT_1[] = $player->getName();
					if($this->getLang($player->getName()) == "EN"){
						$player->addTitle(TF::YELLOW . "You Selected", TF::AQUA . "ARCHER KIT");	
					} elseif($this->getLang($player->getName()) == "AR"){					
						$player->addTitle(TF::YELLOW . "ﺕﺮﺘﺧﺍ ﺪﻘﻟ", TF::AQUA . "ﺖﻛ ﺮﻴﺷﺭﺍ");			
					}
					$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				}
				
				break;
				
				case 2:
				
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				if(!isset($arena->KIT_2[$player->getName()])){
					$arena->KIT_2[] = $player->getName();
					if($this->getLang($player->getName()) == "EN"){
						$player->addTitle(TF::YELLOW . "You Selected", TF::AQUA . "SWORDSMAN KIT");
					} elseif($this->getLang($player->getName()) == "AR"){					
						$player->addTitle(TF::YELLOW . "ﺕﺮﺘﺧﺍ ﺪﻘﻟ", TF::AQUA . "ﻑﻮﻴﺴﻟﺍ ﺖﻛ");
					}
					$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				}
				$player->getLevel()->addSound(new ClickSound($player->asVector3()));
			
				break;
				
				case 3:
				
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				if(!isset($arena->KIT_3[$player->getName()])){
					$arena->KIT_3[] = $player->getName();
					if($this->getLang($player->getName()) == "EN"){
						$player->addTitle(TF::YELLOW . "You Selected", TF::AQUA . "GOLEM KIT");
					} elseif($this->getLang($player->getName()) == "AR"){					
						$player->addTitle(TF::YELLOW . "ﺕﺮﺘﺧﺍ ﺪﻘﻟ", TF::AQUA . "ﺖﻛ ﻢﻟﻮﺟ");
					}
					$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				}
				$player->getLevel()->addSound(new ClickSound($player->asVector3()));
			
				break;	
				
				case 4:
				
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				if(!isset($arena->KIT_4[$player->getName()])){
					$arena->KIT_4[] = $player->getName();
					if($this->getLang($player->getName()) == "EN"){
						$player->addTitle(TF::YELLOW . "You Selected", TF::AQUA . "WARPER KIT");
					} elseif($this->getLang($player->getName()) == "AR"){					
						$player->addTitle(TF::YELLOW . "ﺕﺮﺘﺧﺍ ﺪﻘﻟ", TF::AQUA . "ﺖﻛ ﺮﺑﺭﺍﻭ");
					}
					$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				}
				$player->getLevel()->addSound(new ClickSound($player->asVector3()));												
				break;	
				
				case 5:	
				
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				if(!isset($arena->KIT_5[$player->getName()])){
					$arena->KIT_5[] = $player->getName();
					if($this->getLang($player->getName()) == "EN"){
						$player->addTitle(TF::YELLOW . "You Selected", TF::AQUA . "MINER KIT");
					} elseif($this->getLang($player->getName()) == "AR"){					
						$player->addTitle(TF::YELLOW . "ﺕﺮﺘﺧﺍ ﺪﻘﻟ", TF::AQUA . "ﺖﻛ ﺮﻨﻳﺎﻣ");
					}
					$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				}
				$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				
				break;	
				
				case 6:	
				
				if(isset($arena->KIT_0[$player->getName()])){
					unset($arena->KIT_0[$player->getName()]);
				}
				
				if(isset($arena->KIT_1[$player->getName()])){
					unset($arena->KIT_1[$player->getName()]);
				}
				
				if(isset($arena->KIT_2[$player->getName()])){
					unset($arena->KIT_2[$player->getName()]);
				}
				
				if(isset($arena->KIT_3[$player->getName()])){
					unset($arena->KIT_3[$player->getName()]);
				}
				
				if(isset($arena->KIT_4[$player->getName()])){
					unset($arena->KIT_4[$player->getName()]);
				}
				
				if(isset($arena->KIT_5[$player->getName()])){
					unset($arena->KIT_5[$player->getName()]);
				}
				
				if(isset($arena->KIT_6[$player->getName()])){
					unset($arena->KIT_6[$player->getName()]);
				}
				
				if(!isset($arena->KIT_6[$player->getName()])){
					$arena->KIT_6[] = $player->getName();
					if($this->getLang($player->getName()) == "EN"){
						$player->addTitle(TF::YELLOW . "You Selected", TF::AQUA . "SPIDER KIT");
					} elseif($this->getLang($player->getName()) == "AR"){					
						$player->addTitle(TF::YELLOW . "ﺕﺮﺘﺧﺍ ﺪﻘﻟ", TF::AQUA . "ﺖﻛ ﺭﺪﻳﺎﺒﺳ");
					}
					$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				}
				$player->getLevel()->addSound(new ClickSound($player->asVector3()));
				
				break;
				
				case 7:
				// exit
				break;
				
				}
		});
		$form->setTitle(TF::GRAY . "Kit Selection");
		$form->setContent(TF::GRAY . "Select a kit to use:");
		$form->addButton(TF::GREEN . "Default\n§ediamond items",1,"");
		$form->addButton(TF::GREEN . "Archer\n§ebow, 16 arrow",1,"https://cdn.discordapp.com/attachments/611797787452899334/722847727221735514/minecraft-yaymage-330957.png");
		$form->addButton(TF::GREEN . "Swordsman\n§eStone sword, Chain chestplate",1,"https://cdn.discordapp.com/attachments/611797787452899334/722857091789881364/minecraft-yay-image-.png");
		$form->addButton(TF::GREEN . "Golem\n§eFull Chain armor",1,"https://cdn.discordapp.com/attachments/611797787452899334/722857090116223097/IMG_20200521_232956_650.png");
		$form->addButton(TF::GREEN . "Warper\n§eDiamond boots, 8 ender pearl",1,"https://cdn.discordapp.com/attachments/611797787452899334/722866045446258758/oyssdijnb2.png");
		$form->addButton(TF::GREEN . "Miner\n§eChain helmet, Chain boots",1,"https://cdn.discordapp.com/attachments/611797787452899334/722866263726227526/bhkkjhgghhjk2.png");
	    $form->addButton(TF::GREEN . "Spider\n§eDiamond boots, 24 cobweb",1,"https://cdn.discordapp.com/attachments/611797787452899334/722866427232649316/klmmmml2.png");
	    $form->addButton(TF::BLUE . "Exit",1,"https://cdn.discordapp.com/attachments/611797787452899334/722876039147159593/530f8f35d4f32068.png");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function onSignChange(SignChangeEvent $event) : void
    {
        $player = $event->getPlayer();
        if (!$player->isOp() || $event->getLine(0) !== 'mb') {
            return;
        }

        $arena = $event->getLine(1);
        if (!isset($this->plugin->arenas[$arena])) {
            $player->sendMessage(TextFormat::RED . "This arena doesn't exist, try " . TextFormat::GOLD . "/mb create");
            return;
        }

        if (in_array($arena, $this->plugin->signs)) {
            $player->sendMessage(TextFormat::RED . "A sign for this arena already exist, try " . TextFormat::GOLD . "/mb signdelete");
            return;
        }

        $block = $event->getBlock();
        $level = $block->getLevel();
        $level_name = $level->getFolderName();

        foreach ($this->plugin->arenas as $name => $arena_instance) {
            if ($arena_instance->getWorld() === $level_name) {
                $player->sendMessage(TextFormat::RED . "You can't place the join sign inside arenas.");
                return;
            }
        }

        if (!$this->plugin->arenas[$arena]->checkSpawns()) {
            $player->sendMessage(TextFormat::RED . "You haven't configured all the spawn points for this arena, use " . TextFormat::YELLOW . "/mb setspawn");
            return;
        }

        $this->plugin->setSign($arena, $block);
        $this->plugin->refreshSigns($arena);

        $event->setLine(0, $this->plugin->configs["1st_line"]);
        $event->setLine(1, str_replace("{SWNAME}", $this->plugin->arenas[$arena]->getName(), $this->plugin->configs["2nd_line"]));
        $player->sendMessage(TextFormat::GREEN . "Successfully created join sign for '" . TextFormat::YELLOW . $arena . TextFormat::GREEN . "'!");
    }
	
	public function onInteract(PlayerInteractEvent $event) : void
    {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        $itemid = $item->getID();
		$inv = $player->getArmorInventory();
		if (($block->getId() === Block::SIGN_POST || $block->getId() === Block::WALL_SIGN) && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $arena = $this->plugin->getArenaFromSign($block);
            if ($arena !== null) {
                $player = $event->getPlayer();
                if ($this->plugin->getPlayerArena($player) === null) {
                    $this->plugin->arenas[$arena]->join($player);
                }
            }
        }
		$arena = $this->plugin->getPlayerArena($player);
		// open chests
		if($arena !== null){
			if($block->getId() == Item::CHEST && $arena->GAME_STATE === Arena::STATE_COUNTDOWN){
				$event->setCancelled();
			}
		}
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_AIR)return;
		
		// quit bed
		if($item->getName() === "§l§cReturn to Lobby§r§7 (Right Click)"){
			if($arena == null)return;
			$event->setCancelled(true);
			Server::getInstance()->dispatchCommand($player, "mb quit");
			
        }
		if($item->getCustomName() == "§aKit Selector§7 (Right Click)"){
			if($arena == null)return;
			//$this->OpenKitUI($player);
			$event->setCancelled(true);
			Server::getInstance()->dispatchCommand($player, "mb openkit");
		}
		
		if($item->getCustomName() == "§l§bPlay Again§r§7 (Right Click)"){
			if($arena !== null)return;
			$event->setCancelled(true);
			Server::getInstance()->dispatchCommand($player, "mb join");
		}
		
		$item = $event->getItem();
		
		if($item->getId() == Item::WOOL){
           // $teamColor = Utils::woolIntoColor($item->getDamage());

            $playerGame = $this->plugin->getPlayerArena($player);
            //if($playerGame == null || $playerGame->getState() !== Game::STATE_LOBBY)return;
			
			if($playerGame == null){
				return;
			}
			
			if($playerGame->getST() !== Arena::STATE_COUNTDOWN){
				return;
			}
			
            if(!$player->hasPermission('lobby.team')){
                $player->sendMessage(TextFormat::YELLOW . "§r§cYou don't have permission to use this");
                return;
            }

            $teamColor = Utils::woolIntoColor($item->getDamage());
			$playerTeam = $this->plugin->getPlayerTeam($player);
            if($playerTeam !== null){
                $player->sendMessage(TextFormat::YELLOW . "§r§cYou are already in a team!");
                return;
            }
            foreach($playerGame->teams as $team){
                if($team->getColor() == $teamColor){

                    if(count($team->getPlayers()) >= $playerGame->playersPerTeam){
                        $player->sendMessage(TextFormat::RED . "§r§c" . $team->getName() . " team is full");
                        return;
                    }
                    $team->add($player);
                    $player->sendMessage(TextFormat::GRAY . "§r§eYou've joined " . $team->getName() . " team");
                }
            }
        }
	}
	
	public function onLevelChange(EntityLevelChangeEvent $event) : void
    {
        $player = $event->getEntity();
		$arena = $this->plugin->getPlayerArena($player);
        if ($player instanceof Player && $arena !== null) {
            //$event->setCancelled();
			Server::getInstance()->dispatchCommand($player, "mb quit");
        }
    }
	
	public function onDropItem(PlayerDropItemEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null) {
            $type = $arena->inArena($player);
            if ($type === Arena::PLAYER_SPECTATING) {
                $event->setCancelled();
            }
			
			if($arena->GAME_STATE === Arena::STATE_COUNTDOWN){
				$event->setCancelled();
			}
        }
    }
	
	public function onJoin(PlayerJoinEvent $ev){
		$player = $ev->getPlayer();
		if(!is_file($this->plugin->getDataFolder() . "Players/" . $player->getName() . ".yml")){// create player file
			$t = new Config($this->plugin->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML, [
			"Black_Firework" => "false",
			"Aqua_Firework" => "false",
			"Yellow_Firework" => "false",
			"Red_Firework" => "false",
			"Blue_Firework" => "false",
			"Black_Firework" => "false",
			"Anvil_Win" => "false",
			"Win_EF" => 6
			]);
			$t->save();
			var_dump("mb | Done Make file player");
		}
	}
	
	public function onMove(PlayerMoveEvent $ev) : void
    {
        $from = $ev->getFrom();
        $to = $ev->getTo();
        $player = $ev->getPlayer();
		
        if (floor($from->x) !== floor($to->x) || floor($from->z) !== floor($to->z) || floor($from->y) !== floor($from->y)) {//moved a block
            $arena = $this->plugin->getPlayerArena($player);
            if ($arena !== null) {
                if ($arena->GAME_STATE === Arena::STATE_COUNTDOWN) {
                    //$ev->setCancelled();
                } elseif ($arena->void >= $ev->getPlayer()->getFloorY() && $ev->getPlayer()->isAlive()) {
                    $player->attack(new EntityDamageEvent($ev->getPlayer(), EntityDamageEvent::CAUSE_VOID, 10));
                }
                return;
            }
            if ($this->plugin->configs["sign.knockBack"]) {
                foreach ($this->plugin->getNearbySigns($to, $this->plugin->configs["knockBack.radius.from.sign"]) as $pos) {
                    $player->knockBack($player, 0, $from->x - $pos->x, $from->z - $pos->z, $this->plugin->configs["knockBack.intensity"] / 5);
                    break;
                }
            }
        }
    }
	
	public function onDamage(EntityDamageEvent $event) : void
    {
		$entity = $event->getEntity();
		if($event instanceof EntityDamageByEntityEvent && $entity instanceof Player){
			$d = $event->getDamager();
			if($d instanceof Player){
				$this->plugin->setLast($entity, $d);// set Last Player Damage 
			}
		}
		if ($entity instanceof Player) {
			$arena = $this->plugin->getPlayerArena($entity);
			if ($arena !== null) {
				if ($arena->inArena($entity) !== Arena::PLAYER_PLAYING || $arena->GAME_STATE === Arena::STATE_RESTART ||$arena->GAME_STATE === Arena::STATE_COUNTDOWN ||$arena->GAME_STATE === Arena::STATE_NOPVP || in_array($event->getCause(), $this->plugin->configs["damage.cancelled.causes"])) {
                    $event->setCancelled();
                    return;
                }
				if ($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player) {
					if ($arena->inArena($damager) !== Arena::PLAYER_PLAYING) {
						//$damager->getLevel()->broadcastLevelSoundEvent(new Vector3($damager->getX(), $damager->getY(), $damager->getZ()), LevelSoundEventPacket::SOUND_NOTE, [$damager]);
                        //$event->setCancelled();
					}
				}
				$spectate = (bool)$this->plugin->configs['death.spectator'];
				if ($entity->getHealth() <= $event->getFinalDamage()) {
					foreach ($entity->getDrops() as $item) {
						$entity->getLevel()->dropItem($entity, $item);
					}
					$status = TF::YELLOW . "There are" . TF::RED . " " . ($arena->getSlot(true) - 1) . TF::YELLOW . " players remaining!";
					$arena->sendPopup($status);
					$this->sendDeathMessage($entity);
					$arena->closeSp($entity);
					$event->setCancelled(true);
				}
			}
		}
		
		if($event instanceof EntityDamageByEntityEvent){
		    if($entity instanceof Player)
			$arena = $this->plugin->getPlayerArena($entity);
			$damager = $event->getDamager();
			
			if(!$damager instanceof Player)return;
			
			if(isset($arena->players[$damager->getRawUniqueId()])){
				$damagerTeam = $this->plugin->getPlayerTeam($damager);
				$playerTeam = $this->plugin->getPlayerTeam($entity);
				if($damagerTeam->getName() == $playerTeam->getName()){
					$event->setCancelled();
				}
			}
		}
	}
	
	public function onRespawn(PlayerRespawnEvent $event) : void
    {
        if ($this->plugin->configs["always.spawn.in.defaultLevel"]) {
            $event->setRespawnPosition($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
        }

        if ($this->plugin->configs["clear.inventory.on.respawn&join"]) {
            $event->getPlayer()->getInventory()->clearAll();
        }

        if ($this->plugin->configs["clear.effects.on.respawn&join"]) {
            $event->getPlayer()->removeAllEffects();
        }
		
		if(($api = $this->plugin->getServer()->getPluginManager()->getPlugin("LobbyCore_System")) instanceof  \pocketmine\plugin\Plugin){
			$api->getItems($event->getPlayer());
		}
    }

	public function onBreak(BlockBreakEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null && $arena->inArena($player) !== Arena::PLAYER_PLAYING) {
            $event->setCancelled();
        }

		if ($arena !== null && $player->getGamemode() == 3) {
            $event->setCancelled(true);
        }

        $block = $event->getBlock();
        $sign = $this->plugin->getArenaFromSign($block);
        if ($sign !== null) {
            if (!$player->isOp()) {
                $event->setCancelled();
                return;
            }

            $this->plugin->deleteSign($block);
            $player->sendMessage(TextFormat::GREEN . "Removed join sign for arena '" . TextFormat::YELLOW . $arena . TextFormat::GREEN . "'!");
        }
		
		if(isset($this->plugin->wallSetup[$player->getId()])){
			$arena = $player->getLevel()->getFolderName();
			$t = new Config($this->plugin->getDataFolder() . "arenas/" . $arena . "/wallpos.yml", Config::YAML);
			for($i = 1; $i <= 1000; $i++){
				if(!$t->get($i . "_WALL")){
					$pos = $block->asVector3();
					$t->set($i . "_WALL", ["PX" => $pos->x, "PY" => $pos->y, "PZ" => $pos->z]);
					$t->save();
					break;
				}
			}
		}
    }
	
	public function onPlace(BlockPlaceEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null && $arena->inArena($player) !== Arena::PLAYER_PLAYING) {
            $event->setCancelled();
        }
		if ($arena !== null && $player->getGamemode() == 3) {
            $event->setCancelled(true);
        }
		if ($arena !== null && $arena->inArena($player) == Arena::PLAYER_PLAYING) {
			if($event->getPlayer()->getY() >= 120){
				$player->sendMessage(TF::RED . "Cannot Build Here");
				$event->setCancelled();
			}
		}
    }
	
	public function onCommand(PlayerCommandPreprocessEvent $event) : void
    {
        $command = $event->getMessage();
        if ($command[0] === "/") {
            $player = $event->getPlayer();
            if ($this->plugin->getPlayerArena($player) !== null) {
                if (in_array(strtolower(explode(" ", $command, 2)[0]), $this->plugin->configs["banned.commands.while.in.game"])) {
                    $player->sendMessage($this->plugin->lang["banned.command.msg"]);
                    $event->setCancelled();
                }
            }
        }
    }
	
	public function sendDeathMessage(Player $player) : void
	{
		$arena = $this->plugin->getPlayerArena($player);
		$status = "[" . ($arena->getSlot(true) - 1) . "/" . $arena->getSlot() . "]";
		$last_cause_ev = $player->getLastDamageCause();
		if($last_cause_ev == null)return;
		switch ($last_cause_ev->getCause()) {
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				$damager = $last_cause_ev->getDamager();
				$message = TF::GRAY . $player->getDisplayName() . TF::YELLOW . " was killed by " . TF::GRAY . $damager->getDisplayName();
				$last = $this->plugin->getLast($player);
				if($last !== null){
					$d = $this->plugin->getServer()->getPlayer($last);
					$arena->addKill($d, 1);
					$arena->addSoul($d, 1);
					$arena->addXP($d, 1);
					EconomyAPI::getInstance()->addMoney($damager, 83);
					$damager->sendPopup("§6+83 coins §b+1 soul §d+1 XP");
				}
			break;
			
			case EntityDamageEvent::CAUSE_PROJECTILE:
				$damager = $last_cause_ev->getDamager();
				$message = strtr($this->plugin->lang["death.arrow"], [
                    '{COUNT}' => $status,
                    '{KILLER}' => $damager instanceof Player ? $damager->getDisplayName() : ($damager instanceof Living ? $damager->getRawUniqueId() : $damager->getNameTag()),
                    '{PLAYER}' => $player->getDisplayName()
                ]);
				$last = $this->plugin->getLast($player);
				if($last !== null){
					$d = $this->plugin->getServer()->getPlayer($last);
					$arena->addKill($d, 1);
					$arena->addSoul($d, 1);
					$arena->addXP($d, 1);
					EconomyAPI::getInstance()->addMoney($damager, 83);
					$damager->sendPopup("§6+83 coins §b+1 soul §d+1 XP");
				}
			break;
			
			case EntityDamageEvent::CAUSE_CUSTOM:
				$message = TF::GRAY . $player->getDisplayName() . TF::YELLOW . " was killed by water";
			break;
			
			case EntityDamageEvent::CAUSE_VOID:
				$last = $this->plugin->getLast($player);
				$message = TF::GRAY . $player->getDisplayName() . TF::YELLOW . " was thrown into the void";
				if($last !== null){
					$pp = $this->plugin->getServer()->getPlayer($last);
					$pk = new LevelEventPacket();
					$pk->evid = LevelEventPacket::EVENT_SOUND_ORB;
					$pk->data = 0;
					$pk->position = $pp->asVector3();
					$pp->dataPacket($pk);
					$arena->addKill($pp, 1);
					$arena->addSoul($pp, 1);
					$arena->addXP($pp, 1);
					EconomyAPI::getInstance()->addMoney($pp, 83);
					$pp->sendPopup("§6+83 coins §b+1 soul §d+1 XP");
					$message = TF::GRAY . $player->getDisplayName() . TF::YELLOW . " was thrown into the void by " . TF::GRAY . $last;
				}
			break;
			
			case EntityDamageEvent::CAUSE_LAVA:
				$last = $this->plugin->getLast($player);
				$message = TF::RED . $player->getDisplayName() . TF::YELLOW . " killed by" . TF::RED . " Lava " . $status;
				if($last !== null){
					$pp = $this->plugin->getServer()->getPlayer($last);
					$pk = new LevelEventPacket();
					$pk->evid = LevelEventPacket::EVENT_SOUND_ORB;
					$pk->data = 0;
					$pk->position = $pp->asVector3();
					$pp->dataPacket($pk);
					$arena->addKill($pp, 1);
					$arena->addSoul($pp, 1);
					$arena->addXP($pp, 1);
					EconomyAPI::getInstance()->addMoney($pp, 83);
					$pp->sendPopup("§6+83 coins §b+1 soul §d+1 XP");
					$message = TF::RED . $player->getDisplayName() . TF::YELLOW . " killed by " . TF::GREEN . $last . TF::RED . " Lava " . $status;
				}
			break;
			default:
				$message = strtr($this->plugin->lang["game.left"], [
                    '{COUNT}' => $status,
                    '{PLAYER}' => $player->getDisplayName()
                ]);
			break;
		}
		$this->plugin->getServer()->broadcastMessage($message, $player->getLevel()->getPlayers());
	}
	
	public function onPickUp(InventoryPickupItemEvent $event) : void
    {
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player && ($arena = $this->plugin->getPlayerArena($player)) !== null && $arena->inArena($player) === Arena::PLAYER_SPECTATING) {
            $event->setCancelled();
        }
    }
	
	public function onQuit(PlayerQuitEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null) {
            $arena->closePlayer($player);
        }
    }
}