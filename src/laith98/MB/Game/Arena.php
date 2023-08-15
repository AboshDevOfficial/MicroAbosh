<?php

namespace laith98\MB\Game;

use laith98\MB\Events\ArenaNewListener;
use laith98\MB\Utils\Scoreboard;
use pocketmine\entity\object\ItemEntity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

use pocketmine\block\Block;
use pocketmine\item\Item;

use pocketmine\level\Position;
use pocketmine\level\sound\{
	ClickSound, 
	EndermanTeleportSound, 
	Sound
	};

use pocketmine\Player;

use pocketmine\tile\Chest;

use pocketmine\utils\{Config, TextFormat};
use pocketmine\utils\TextFormat as TF;

use pocketmine\item\enchantment\{
	Enchantment, 
	EnchantmentInstance
	};

use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;

use pocketmine\item\ItemFactory;
use pocketmine\Server;

use pocketmine\entity\Entity;
use pocketmine\level\particle\FloatingTextParticle;

use pocketmine\level\sound\{
	BatSound,
	DoorSound,
	FizzSound,
	LaunchSound
	};

use pocketmine\item\Bow;
use pocketmine\item\Sword;
use pocketmine\item\Armor;

use pocketmine\inventory\transaction\action\SlotChangeAction;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;
use muqsit\invmenu\inventories\BaseFakeInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use onebone\economyapi\EconomyAPI;

use laith98\MB\Main;
use laith98\MB\Utils\Utils;

class Arena
{
	//Player states
    const PLAYER_NOT_FOUND = 0;
    const PLAYER_PLAYING = 1;
    const PLAYER_SPECTATING = 2;
	
	//Game states
    const STATE_COUNTDOWN = 0;
    const STATE_RUNNING = 1;
    const STATE_NOPVP = 2;
	const STATE_RESTART = 3;

	// vote chest
	const NORMAL = 01;
	const OVERPOWER = 02;
	const DRAW = 03;

	// game mode	
	const NORMAL_MODE = 01;
	const INSANE_MODE = 02;
	
	/** @var Main             */
    private $plugin;
	
	/** @var PlayerSnapshot[] */
    private $playerSnapshots = [];//store player's inventory, health etc pre-match so they don't lose it once the match ends
	
	/** @var int              */	
    public $GAME_STATE = Arena::STATE_COUNTDOWN;
	
	/** @var string           */
    private $SWname;
	
	/** @var int              */
    private $slot;

    /** @var string           */
    private $world;

    /** @var int              */
    private $countdown = 60;//Seconds to wait before the game starts

    /** @var int              */
    private $maxtime = 300;//Max seconds after the countdown, if go over this, the game will finish
	
	/** @var int              */
    private $gametime = 420;

    /** @var int              */
    public $void = 0;//This is used to check "fake void" to avoid fall (stunck in air) bug

    /** @var array            */
    private $spawns = [];//Players spawns

    /** @var int              */
    private $time = 0;//Seconds from the last reload | GAME_STATE
	
	/** @var int              */
	private $chestrefill = 0;
	
	/** @var string[]         */
    public $players = [];//[rawUUID] => int(player state)
	
	/** @var array[]          */
    public $playerSpawns = [];
	
	/** @var array            */
    public $spectators = [];
	
	/** @var array[]          */
	public $inNormal = [];
	
	/** @var array[]          */
	public $inNormalMode = [];
	
	/** @var array[]          */
	public $inInsaneMode = [];
	
	/** @var array[]          */
	public $playing = [];
	
	/** @var array[]          */
	public $ing = [];
	
	/** @var array[]          */
	public $kills = [];
	
	/** @var array[]          */
	public $buttons = [];
	
	/** @var array[]          */
	public $pstate = [];
	
	/** @var array $ranks     */
	public $ranks = array("Gaust", "VIP", "VIPp", "MVP", "MVPp", "MOD", "HELPER", "Admin", "CoOwner", "Owner");

	// KITS
	public $KIT_0 = [];
	public $KIT_1 = [];
	public $KIT_2 = [];
	public $KIT_3 = [];
	public $KIT_4 = [];
	public $KIT_5 = [];
	public $KIT_6 = [];
	
	/** @var array[] */
	public $inOverPower = [];
	
	/** @var array[] */
	public $spp = [];
	
	/** @var int */
	public $endtime = 10;
	
	public $WallTime = 20;
	
	public $playersPerTeam = 4;
	
	/** @var bool */
	public $tpToCage = false;
	
	public $damageWater = false;
	
	public $floatingChestRef;
	
	public $mode = 01;
	
	/** @var const Teams           */
	const TEAMS = [
        'blue' => "§1",
        'red' => "§c",
        'yellow' => "§e",
        "green" => "§a",
        "aqua" => "§b",
        "gold" => "§6",
        "white" => "§f"
    ];
	
    public $teams = array();
    public $kepd3;
    public $kedip1;
    public $teamsCount = array();

    public function __construct(Main $plugin, string $SWname = "sw", int $slot = 0, string $world = "world", int $countdown = 60, int $maxtime = 300, int $void = 0)
    {
		$this->gametime = 420;
        $this->plugin = $plugin;
        $this->SWname = $SWname;
        $this->slot = ($slot + 0);
        $this->world = $world;
        $this->countdown = ($countdown + 0);
        $this->maxtime = ($maxtime + 0);
        $this->void = $void;
        $this->kepd3 = 0;
        $this->plugin->getServer()->getPluginManager()->registerEvents(new ArenaNewListener($this), $this->plugin);
		$this->teams["Blue"] = new Team("Blue", self::TEAMS[strtolower("Blue")]);
		$this->teams["Red"] = new Team("Red", self::TEAMS[strtolower("Red")]);
		$this->teams["Yellow"] = new Team("Yellow", self::TEAMS[strtolower("Yellow")]);
		$this->teams["Green"] = new Team("Green", self::TEAMS[strtolower("Green")]);
        $this->teamsCount["GreenCount"] = $this->teams["Green"]->getPlayers();
        $this->teamsCount["YellowCount"] = $this->teams["Yellow"]->getPlayers();
        $this->teamsCount["RedCount"] = $this->teams["Red"]->getPlayers();
        $this->teamsCount["BlueCount"] = $this->teams["Blue"]->getPlayers();
        if (!$this->reload($error)) {
            $logger = $this->plugin->getLogger();
            $logger->error("An error occured while reloading the arena: " . TextFormat::YELLOW . $this->SWname);
            $logger->error($error);
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
        }
	}
	
	/**
     * @return bool
     */
    private function reload(&$error = null) : bool
    {
        //Map reset
        if (!is_file($file = $this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/" . $this->world . ".tar") && !is_file($file = $this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/" . $this->world . ".tar.gz")) {
            $error = "Cannot find world backup file $file";
            return false;
        }

        $server = $this->plugin->getServer();
        if ($server->isLevelLoaded($this->world)) {
            $server->unloadLevel($server->getLevelByName($this->world));
        }

        if ($this->plugin->configs["world.reset.from.tar"]) {
            $tar = new \PharData($file);
            $tar->extractTo($server->getDataPath() . "worlds/" . $this->world, null, true);
        }
		
        $server->loadLevel($this->world);
        $server->getLevelByName($this->world)->setAutoSave(false);

        $config = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML, [//TODO: put descriptions
            "name" => $this->SWname,
            "slot" => $this->slot,
            "world" => $this->world,
            "countdown" => $this->countdown,
            "maxGameTime" => $this->maxtime,
            "void_Y" => $this->void,
			"mode" => $this->mode,
            "spawns" => []
        ]);
		
		foreach($this->teams as $team){
			$team->reset();
		}

        $this->SWname = $config->get("name");
        $this->slot = (int) $config->get("slot");
        $this->world = $config->get("world");
        $this->countdown = (int) $config->get("countdown");
        $this->maxtime = (int) $config->get("maxGameTime");
        $this->spawns = $config->get("spawns");
        $this->void = (int) $config->get("void_Y");
        $this->mode = $config->get("mode");
        $this->players = [];
		$this->inNormalMode = [];
		$this->inInsaneMode = [];
		$this->ing = [];
		$this->ing["0"] = "null";
		$this->ing["1"] = "null";
		$this->ing["2"] = "null";
		$this->ing["3"] = "null";
		$this->ing["4"] = "null";
		$this->ing["5"] = "null";
		$this->ing["6"] = "null";
		$this->ing["7"] = "null";
		$this->ing["8"] = "null";
		$this->ing["9"] = "null";
		$this->ing["10"] = "null";
		$this->ing["11"] = "null";
		$this->tpToCage = false;
		$this->damageWater = false;
		
		$this->buttons = [];
		$this->spectators = [];
		$this->endtime = 10;
		$this->WallTime = 20;
        $this->time = 0;
        $this->GAME_STATE = Arena::STATE_COUNTDOWN;

        //Reset Sign
        $this->plugin->refreshSigns($this->SWname, 0, $this->slot);
        return true;
    }
	
	/**
     * @return array
     */
    public function getAliveTeams() : array{
        $teams = [];
        foreach($this->teams as $team){
            if(count($team->getPlayers()) > 0){
				$teams[] = $team->getName();
			}
		}
        return $teams;
    }
	
	final public function getName() : string
    {
        return $this->SWname;
    }
	
	public function getLang(string $n){
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("LobbyCore_System");
		if($api !== null){
			$m = $api->getPlayerLang($n);
			if($m == "EN"){
				return "EN";
			} elseif($m == "AR"){
				return "AR";	
			}
		}
		return "EN";
	}
	
	public function getMode(){
		return Arena::INSANE_MODE;
	}
	
	public function getM(){
		$r = rand(0,1);
		switch($r){
			case 0:
			$t = Arena::NORMAL_MODE;
			break;

			case 1:
			$t = Arena::INSANE_MODE;
			break;
		}
		return $t;
	}
	
	public function getModee(){
		if($this->getMode() == Arena::INSANE_MODE){
			if(($this->countdown - $this->time) < 13){
				return "§cInsane";
			}
		} elseif($this->getMode() == Arena::NORMAL_MODE){
			if(($this->countdown - $this->time) < 16){
				return "§aNormal";
			}
		}
		return TF::GREEN . "Solo";
	}
	
	public function OpenVoteModeUI(Player $player){
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
			}

			 switch($result){
				case 0:
					if(in_array($player->getName(), $this->inNormalMode) or in_array($player->getName(), $this->inInsaneMode)){
						$player->sendMessage(TF::RED . "you already voted");
					}
					if(!in_array($player->getName(), $this->inNormalMode) && !in_array($player->getName(), $this->inInsaneMode)){
						$this->inInsaneMode[] = $player->getName();
						$this->sendMessage(TF::AQUA . $player->getDisplayName() . TF::GOLD . " has voted for §cInsane §6- §b" . count($this->inInsaneMode) . TF::GOLD . " vote");
					}
				break;
				
				case 1:
					if(in_array($player->getName(), $this->inNormalMode) or in_array($player->getName(), $this->inInsaneMode)){
						$player->sendMessage(TF::RED . "you already voted");
					}
					if(!in_array($player->getName(), $this->inNormalMode) && !in_array($player->getName(), $this->inInsaneMode)){
						$this->inNormalMode[] = $player->getName();
						$this->sendMessage(TF::AQUA . $player->getDisplayName() . TF::GOLD . " has voted for §aNormal §6- §b" . count($this->inNormalMode) . TF::GOLD . " vote");					
					}
				break;
				
				case 2:
				
				break;
				}
		});
		$cn = count($this->inNormalMode);
		$op = count($this->inInsaneMode);
		$form->setTitle("Voting Mode");
		$form->addButton(TF::RED . "Insane Mode §7[{$op}]",1,"https://cdn.discordapp.com/attachments/611797787452899334/739974568101412904/Red_Wool.png");
		$form->addButton(TF::GREEN . "Normal Mode §7[{$cn}]",1,"https://cdn.discordapp.com/attachments/611797787452899334/739974394625130506/150px-Lime_Wool.png");
		$form->addButton("§l§cExit", 0, "textures/blocks/barrier");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function getKit(Player $player){
		$n = $player->getName();
		if(isset($this->KIT_0[$n])){
			return "DEF";
		}
		if(isset($this->KIT_0[$n])){
			return "";
		}
		if(isset($this->KIT_0[$n])){
			return "";
		}
		if(isset($this->KIT_0[$n])){
			return "";
		}
		if(isset($this->KIT_0[$n])){
			return "";
		}
		if(isset($this->KIT_0[$n])){
			return "";
		}
		return $this->KIT_0[$n];
	}
	
	public function getPlayerKit(Player $player){
		$kit = $this->getKit($player);
		if($kit == "DEF" or $kit == "SW"){
			return "Default";
		} else {
			return $kit;
		}
	}
	
	public function getST(){
		return $this->GAME_STATE;
	}
	
	public function getState() : string
    {
        if ($this->GAME_STATE !== Arena::STATE_COUNTDOWN || count(array_keys($this->players, Arena::PLAYER_PLAYING, true)) >= $this->slot) {
            return TextFormat::RED . TextFormat::BOLD . "Running";
        }
        return TextFormat::WHITE . "Tap to join";
    }
	
	public function getSlot(bool $players = false) : int
    {
        return $players ? count($this->players) : $this->slot;
    }

    public function getWorld() : string
    {
        return $this->world;
    }
	
	/**
     * @param Player $player
     * @return int
     */
    public function inArena(Player $player) : int
    {
		$playerR = $player->getRawUniqueId();
		$playerName = $player->getName();
		if(isset($this->players[$player->getRawUniqueId()])){
			return $this->players[$player->getRawUniqueId()];
		}
		
		if(isset($this->spectators[$player->getRawUniqueId()])){
			return Arena::PLAYER_SPECTATING;
		}
		
        return Arena::PLAYER_NOT_FOUND;
    }
	
	public function getPlayerState(Player $player){
		if(isset($this->pstate[$player->getRawUniqueId()])){
			return $this->pstate[$player->getRawUniqueId()];
		}
		return null;
	}
	
	public function setPState(Player $player, int $s){
		if($s == 03){
			if(isset($this->pstate[$player->getRawUniqueId()])){
				unset($this->pstate[$player->getRawUniqueId()]);
				return true;
			}
		}
		$this->pstate[$player->getRawUniqueId()] = $s;
	}

    public function setPlayerState(Player $player, ?int $state) : void
    {
        if ($state === null || $state === Arena::PLAYER_NOT_FOUND) {
            unset($this->players[$player->getRawUniqueId()]);
            return;
        }
        $this->players[$player->getRawUniqueId()] = $state;
    }
	
	/**
     * @param Player $player
     * @param int $slot
     * @return bool
     */
    public function setSpawn(Player $player, int $slot = 1) : bool
    {
        if ($slot > $this->slot) {
            $player->sendMessage(TextFormat::RED . "This arena have only got " . TextFormat::WHITE . $this->slot . TextFormat::RED . " slots");
            return false;
        }

        $config = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML);

        if (empty($config->get("spawns", []))) {
            $config->set("spawns", array_fill(1, $this->slot, [
                "x" => "n.a",
                "y" => "n.a",
                "z" => "n.a",
                "yaw" => "n.a",
                "pitch" => "n.a"
            ]));
        }
        $s = $config->get("spawns");
        $s[$slot] = [
            "x" => floor($player->x),
            "y" => floor($player->y),
            "z" => floor($player->z),
            "yaw" => $player->yaw,
            "pitch" => $player->pitch
        ];

        $config->set("spawns", $s);
		$config->save();
        $this->spawns = $s;

        if (!$config->save() || count($this->spawns) !== $this->slot) {
            $player->sendMessage(TextFormat::RED . "An error occured setting the spawn, please contact the developer.");
            return false;
        }
        return true;
    }
	
	/**
     * @return bool
     */
    public function checkSpawns() : bool
    {
        /*if (empty($this->spawns)) {
            return false;
        }

        foreach ($this->spawns as $key => $val) {
            if (!is_array($val) || count($val) !== 5 || $this->slot !== count($this->spawns) || in_array("n.a", $val, true)) {
                return false;
            }
        }*/
		
		$t = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML);
		$yes = 0;
		if($t->get("Blue_SPAWN")){
			$yes = ($yes + 1);
		}
		if($t->get("Red_SPAWN")){
			$yes = ($yes + 1);
		}
		if($t->get("Yellow_SPAWN")){
			$yes = ($yes + 1);
		}
		if($t->get("Green_SPAWN")){
			$yes = ($yes + 1);
		}
		if($yes = 4){
			return true;
		}
		return false;
    }
	
	public function Enchant(int $enchantment, int $level): EnchantmentInstance{
        return new EnchantmentInstance(Enchantment::getEnchantment($enchantment), $level);
    }

	public function getPlayingPlayers(){
		return count($this->playing);
	}
	
	public function refillChests() : void {
        $contents = $this->plugin->getChestContents();
        foreach($this->plugin->getServer()->getLevelByName($this->world)->getTiles() as $tile){
            if($tile instanceof Chest){
                $inventory = $tile->getInventory();
                $inventory->clearAll(false);
                if(empty($contents)){
                    $contents = $this->plugin->getChestContents();
                }
                
				foreach(array_shift($contents) as $key => $val){
					if($this->getMode() == Arena::NORMAL_MODE){
						//$inventory->setItem($key, Item::get($val[0], 0, $val[1]), false);
					}
					
                    $item = Item::get($val[0], 0, $val[1]);

					if($this->getMode() == Arena::INSANE_MODE){
						$it = $this->enchantItem($item);
						//$inventory->setItem($key, $it, false);
					}

					if(mt_rand(1, 100) <= 2){
                        $potion = Item::get(438, 16, 1);
                        //$tile->getInventory()->setItem(mt_rand(1, 25), $potion);
                    }

					// new position
					if(mt_rand(1, 100) <= 2){
                        $potion = Item::get(441	, 17, 1);
                        //$tile->getInventory()->setItem(mt_rand(1, 25), $potion);
                    }
					
					// new position
					if(mt_rand(1, 100) <= 2){
                        $potion = Item::get(438, 7, 1);
                    }
					
					// new position
					if(mt_rand(1, 100) <= 2){
                        $potion = Item::get(438, 25, 1);
                    }

					// new position
					if(mt_rand(1, 100) <= 2){
                        $potion = Item::get(438, 29, 1);
                    }
					
					if(mt_rand(1, 100) <= 2){
                        $potion = Item::get(373, 13, 1);
                    }

					if(mt_rand(1, 100) <= 1){
                        $potion = Item::get(373, 28, 1);
                        //$tile->getInventory()->setItem(mt_rand(1, 25), $potion);
                    }

                    if(rand(1, 100) <= 10){
                        if($item instanceof Sword){
							//$this->enchantItem($item);
                            $item->addEnchantment($this->Enchant(9, 2));
                            $item->addEnchantment($this->Enchant(12, 2));
                        } elseif($item instanceof Armor){
							$this->enchantItem($item);
                            $item->addEnchantment($this->Enchant(0, 2));
                        } elseif($item instanceof Bow){
							$this->enchantItem($item);
                            $item->addEnchantment($this->Enchant(19, 2));
                        }
                    }

                }
                $inventory->sendContents($inventory->getViewers());
            }
        }
    }
	
	public function getTimeCountdown(){
		if($this->getST() == Arena::STATE_COUNTDOWN){
			return TF::GREEN . "Started in " . ($this->countdown - $this->time);
		}
		return TextFormat::BOLD . "" . TF::RESET . TF::BOLD . TF::YELLOW . "§e§l Playing §fMICROBATTLE §e§lON §b " . TextFormat::BOLD . TextFormat::WHITE . "";
	}
	
	public function enchantItem($item) : Item{
        $armorEnchantments = [
            Enchantment::PROTECTION => 0,
            Enchantment::FIRE_PROTECTION => 1,
            Enchantment::THORNS => 5
        ];

        $swordEnchantments = [
            Enchantment::FIRE_ASPECT => 13,
            Enchantment::SHARPNESS => 9
        ];
		
		$bowEnchantments = [
           Enchantment::UNBREAKING => 17,
		   Enchantment::POWER => 19,
		   Enchantment::PUNCH => 20,
		   Enchantment::FLAME => 21,
		   Enchantment::THORNS => 5
        ];
		
        if($item instanceof Armor){
            $enchantment = array_rand($armorEnchantments);
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($enchantment), $this->getRandomLevel()));
        }

		if($item instanceof Bow){
            $enchantment = array_rand($bowEnchantments);
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($enchantment), $this->getRandomLevel()));
        }

        if($item instanceof Sword){
            $enchantment = array_rand($swordEnchantments);
            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($enchantment), $this->getRandomLevel()));
        }
        return $item;
    }
	
	public function tick() : void
    {
		$config = $this->plugin->configs;
		switch ($this->GAME_STATE) {
			case Arena::STATE_COUNTDOWN:
				$player_cnt = count($this->players);
				foreach($this->getPlayers() as $player){
					if ($player_cnt < $config["needed.players.to.run.countdown"]) {
						$day = date("d/m/y");
						if($this->time !== 0){
							$this->time = 0;
						}
						return;
					}
					$p = $player;
					$day = date("d/m/y");
					$ty = ($this->countdown - $this->time);
                    $dada = ["§e§l< §6§lM §e§l>", "§e§l< §6§lMI §e§l>", "§e§l< §6§lMIC §e§l>", "§e§l< §6§lMICR §e§l>", "§e§l< §6§lMICRO §e§l>", "§e§l< §6§lMICRO§f§lB§e§l >", "§e§l< §6§lMICRO§f§lBA§e§l >", "§e§l< §6§lMICRO§f§lBAT§e§l >", "§e§l< §6§lMICRO§f§lBATT §e§l>", "§e§l< §6§lMICRO§f§lBATTL §e§l>", "§e§l< §6§lMICRO§f§lBATTLE §e§l>", "§e§l< §f§lMICROBATTLE §e§l>", "§e§l< §6§lMICROBATTLE §e§l>"];
                    $date = date("d/m/Y");
                    Scoreboard::new($player, "GameJoin", $dada[$this->kepd3]);
                    Scoreboard::setLine($player, 1, "§7".$date);
                    Scoreboard::setLine($player, 2, "§l§b   ");
                    Scoreboard::setLine($player, 3, "Map §a".$this->world);
                    Scoreboard::setLine($player, 4, "§l§b  ");
                    Scoreboard::setLine($player, 5, "".$this->countdown - $this->time);
                    Scoreboard::setLine($player, 6, "§l§b ");
                    Scoreboard::setLine($player, 7, "§ePlay.EXAMPLE.NET");
					if(($this->countdown - $this->time) == 15){
						if($this->getMode() == Arena::NORMAL_MODE){
							$tmp = $this->playerSpawns[$p->getRawUniqueId()];
							$server = $this->plugin->getServer();
							$level = $server->getLevelByName($this->world);
							$pos = new Position($tmp["x"] + 0.5, $tmp["y"], $tmp["z"] + 0.5, $level);
							$this->build($pos);
							//$p->addTitle(TF::YELLOW . "SkyWars", TF::GREEN . "Normal Mode");
							//$p->teleport($pos, $tmp["yaw"], $tmp["pitch"]);
							//$p->setImmobile(true);
							//$this->tpToCage = true;
							//$p->getInventory()->setItem(0, Item::get(Item::BOW, 0, 1)->setCustomName("§aKit Selector§7 (Right Click)"));
							$p->getInventory()->setItem(8, Item::get(355, 14, 1)->setCustomName("§l§cReturn to Lobby§r§7 (Right Click)"));
						}
					}
                    if (($this->countdown - $this->time) == 14){
                        $p->getLevel()->addSound(new ClickSound($p->asVector3()));
                        if($this->getLang($p->getName()) == "EN"){

                        } elseif($this->getLang($p->getName()) == "AR"){

                        }
                    }
                    if(($this->countdown - $this->time) < 13){
						if(isset($this->KIT_0[$player->getName()])){
								$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Default");
						}
							
						if(isset($this->KIT_1[$player->getName()])){
							$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Archer");
						}
							
						if(isset($this->KIT_2[$player->getName()])){
							$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Swordsman");
						}
							
						if(isset($this->KIT_3[$player->getName()])){
							$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Golem");
						}
						
						if(isset($this->KIT_4[$player->getName()])){
							$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Warper");
						}
							
						if(isset($this->KIT_5[$player->getName()])){
							$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Miner");
						}
						
						if(isset($this->KIT_6[$player->getName()])){
							$this->sendPopup(TF::YELLOW . "Selected Kit: " . TF::GREEN . "Spider");
						}

					}
					
					if(($this->countdown - $this->time) == 12){
						if($this->getMode() == Arena::INSANE_MODE){
							$p->sendMessage("§eThe game stars in §a12 §eseconds!");
							//$tmp = $this->playerSpawns[$p->getRawUniqueId()];
							//$server = $this->plugin->getServer();
							//$level = $server->getLevelByName($this->world);
							//$pos = new Position($tmp["x"] + 0.5, $tmp["y"], $tmp["z"] + 0.5, $level);
							//$this->build($pos);
							//$p->addTitle(TF::YELLOW . "SkyWars", TF::GREEN . "Insane Mode");
							//$p->teleport($pos, $tmp["yaw"], $tmp["pitch"]);
							//$this->tpToCage = true;
							//$p->setImmobile(true);
							//$p->getInventory()->setItem(0, Item::get(Item::BOW, 0, 1)->setCustomName("§aKit Selector§7 (Right Click)"));

							$p->getInventory()->setItem(8, Item::get(355, 14, 1)->setCustomName("§l§cReturn to Lobby§r§7 (Right Click)"));
						}
					}
					if (($this->countdown - $this->time) == 10){
						$p->getLevel()->addSound(new ClickSound($p->asVector3()));
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage("§eThe game stars in §610 §eseconds!");
							$p->addTitle("§c10", "§ePrepare to fight!", 5, 20, 5);
						} elseif($this->getLang($p->getName()) == "AR"){
							$p->sendMessage("ﻲﻧﺍﻮﺛ ﺮﺸﻋ ﻲﻓ ﻢﻴﻘﻟﺍ ﺃﺪﺒﻴﺳ");

							$p->addTitle("§9§l10", "§eﺪﻌﺘﺴﻣ ﺖﻧﺍ ﻞﻫ", 5, 20, 5);
						}
					}
                    if (($this->countdown - $this->time) == 5){
						$p->getLevel()->addSound(new ClickSound($p->asVector3()));
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage("§eThe game stars in §c5 §eseconds!");
							$p->addTitle("§c5", "§ePrepare to fight!", 5, 20, 5);
						} elseif($this->getLang($p->getName()) == "AR"){
							$p->sendMessage("ﻲﻧﺍﻮﺛ ﺲﻤﺧ ﻲﻓ ﻢﻴﻘﻟﺍ ﺃﺪﺒﻴﺳ");
							$p->addTitle("§c5", "§eﺪﻌﺘﺴﻣ ﺖﻧﺍ ﻞﻫ", 5, 20, 5);
						}
					}
					
					if (($this->countdown - $this->time) == 4){
						$p->getLevel()->addSound(new ClickSound($p->asVector3()));
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage("§eThe game stars in §c4 §eseconds!");
							$p->addTitle("§c4", "§ePrepare to fight!", 5, 20, 5);
						} elseif($this->getLang($p->getName()) == "AR"){
							//$p->sendMessage("ﻲﻧﺍﻮﺛ ﺲﻤﺧ ﻲﻓ ﻢﻴﻘﻟﺍ ﺃﺪﺒﻴﺳ");
							$p->addTitle("§c4", "§eﺪﻌﺘﺴﻣ ﺖﻧﺍ ﻞﻫ", 5, 20, 5);
						}
					}
					if (($this->countdown - $this->time) == 3){
						$p->getLevel()->addSound(new ClickSound($p->asVector3()));
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage("§eThe game stars in §c3 §eseconds!");
							$p->addTitle("§c3", "§ePrepare to fight!", 5, 20, 5);
						} elseif($this->getLang($p->getName()) == "AR"){
							$p->sendMessage("ﻲﻧﺍﻮﺛ ﺙﻼﺛ ﻲﻓ ﻢﻴﻘﻟﺍ ﺃﺪﺒﻴﺳ");
							$p->addTitle("§c3", "§eﺪﻌﺘﺴﻣ ﺖﻧﺍ ﻞﻫ", 5, 20, 5);

						}
					}
					
					if (($this->countdown - $this->time) == 2){
						$p->getLevel()->addSound(new ClickSound($p->asVector3()));
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage("§eThe game stars in §c2 §eseconds!");
							$p->addTitle("§c2", "§ePrepare to fight!", 5, 20, 5);

						} elseif($this->getLang($p->getName()) == "AR"){
							$p->sendMessage("ﻦﻴﺘﻴﻧﺎﺛ ﻲﻓ ﻢﻴﻘﻟﺍ ﺃﺪﺒﻴﺳ");
							$p->addTitle("§c2", "§eﺪﻌﺘﺴﻣ ﺖﻧﺍ ﻞﻫ", 5, 20, 5);

						}
					}
					
					if (($this->countdown - $this->time) == 1){
						$p->getLevel()->addSound(new ClickSound($p->asVector3()));
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage("§eThe game stars in §c1 §eseconds!");
							$p->addTitle("§c1", "§ePrepare to fight!", 5, 20, 5);

						} elseif($this->getLang($p->getName()) == "AR"){
							$p->sendMessage("ﺔﻴﻧﺎﺛ ﻲﻓ ﻢﻴﻘﻟﺍ ﺃﺪﺒﻴﺳ");
							$p->addTitle("§c1", "§eﺪﻌﺘﺴﻣ ﺖﻧﺍ ﻞﻫ", 5, 20, 5);

						}
					}
					
					if (($config["start.when.full"] && $this->slot <= $player_cnt) || $this->time >= $this->countdown) {
						$this->start();
						return;
					}
					
					if ($this->time % 30 === 0) {
						//$this->sendMessage(str_replace("{N}", date("i:s", ($this->countdown - $this->time)), $this->plugin->lang["chat.countdown"]));
					}
				}
			break;
			
			case Arena::STATE_RUNNING:
				$player_cnt = count(array_keys($this->players, Arena::PLAYER_PLAYING, true));
				if ($player_cnt < 2) {
					$this->GAME_STATE = Arena::STATE_RESTART;
                    return;
                }
				++$this->chestrefill;
				if($this->WallTime !== 0){
					--$this->WallTime;
				}
				$this->gametime--;
				foreach($this->getPlayers() as $p){
					$player = $p;
					if($this->time >= $this->maxtime){
						if($this->getLang($p->getName()) == "EN"){
							$p->sendMessage(TF::YELLOW . "<<-MicroBattle->> GameTime Ended");
						} elseif($this->getLang($p->getName()) == "AR"){
							$p->sendMessage(TF::YELLOW . "<<-MicroBattle->> ﻢﻴﻘﻟﺍ ﺖﻗﻭ ﻰﻬﺘﻧﺍ");
						}
						$this->stop(true);
						return;
					}
					$m = floor($this->gametime / 60);
					$s = $this->gametime % 60;
					$time = ($m < 10 ? "0" : "") . $m . ":" . ($s < 10 ? "0" : "") . $s;
					//$alive = count($this->players);
					$tm = date('i:s', ($this->maxtime - $this->time));
					$r = 0;
					$kills = 0;
					$day = date("d/m/y");
					$ttt = date('i:s', $this->chestrefill);
					$alive = $this->getPlayingPlayers();
					$kills = $this->getKills($p);
					$mode = $this->getMode();
					if($mode == Arena::NORMAL_MODE){
						$m = "§aNormal";
					} elseif($mode == Arena::INSANE_MODE){
						$m = "§cInsane";
					}
					
					$team = $this->plugin->getPlayerTeam($player);
					$this->sendPopup("Team: " . $team->getColor() . $team->getName() . TF::RESET . TF::WHITE . " Time: " . TF::GREEN . $tm);
					if($this->WallTime == 0){
						$t = new Config($this->plugin->getDataFolder() . "arenas/" . $this->getName() . "/wallpos.yml", Config::YAML);
						for($i = 1; $i <= 1000; $i++){
							if($t->get($i . "_WALL") !== null){
								$pos = $t->get($i . "_WALL");
								$x = $pos["PX"];
								$y = $pos["PY"];
								$z = $pos["PZ"];
								$level = $this->plugin->getServer()->getLevelByName($this->world);
								$level->setBlock(new Vector3($x, $y, $z), Block::get(Block::AIR), true, true);
							}
						}
					}
					if($this->chestrefill == 180){
						//$this->refillChests();
						$this->chestrefill = 0;
						//++$this->chestrefill;
						if($this->getLang($player->getName()) == "EN"){
							//$player->addTitle("§e", "§eAll chests have been refilled!", 5, 20, 5);
						} elseif($this->getLang($player->getName()) == "AR") {
							//$player->addTitle("§e", "§eAll chests have been refilled!", 5, 20, 5);
							//$player->sendMessage(TF::YELLOW . "ﻖﻳﺩﺎﻨﺼﻟ ﺚﻳﺪﺤﺗ ﻢﺗ");
						}
					}
				}
			break;
			
			case Arena::STATE_NOPVP:
				if ($this->time <= $config["no.pvp.countdown"]) {
					foreach ($this->getPlayers() as $player){
						$p = $player;
						$kills = 0;
						$day = date("d/m/y");
						$ttt = date('i:s', $this->chestrefill);
						$alive = $this->getPlayingPlayers();
						$kills = $this->getKills($p);
						$mode = $this->getMode();
						if($mode == Arena::NORMAL_MODE){
							$m = "§aNormal";
						} elseif($mode == Arena::INSANE_MODE){
							$m = "§cInsane";
						}
						
					}
				} else {
                    $this->GAME_STATE = Arena::STATE_RUNNING;
                }
			break;
			
			case Arena::STATE_RESTART:
				foreach ($this->plugin->getServer()->getLevelByName($this->world)->getPlayers() as $player) {
					$p = $player;
					if ($player instanceof Player){
						$s = $this->getPlayerState($player);
						if($s === Arena::PLAYER_SPECTATING){
							$this->closePlayer($player);
						}
					}
					--$this->endtime;
					foreach ($this->getPlayers() as $player){
						/** CLOSE SPECTATORS */
						$st = $this->getPlayerState($player);
						if($st == Arena::PLAYER_SPECTATING){
							$this->closePlayer($player, false, false);
						}
						$w = $this->getWinE($player);
						if($w == 6){
							$fw = ItemFactory::get(Item::FIREWORKS);
							$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_GREEN, "", false, false);
							$fw->setFlightDuration(2);

							$level = Server::getInstance()->getLevelByName($this->world);
							$vector3 = $player->asVector3()->add(0.5, 1, 0.5);
							
							$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
							$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);
							if ($entity instanceof Entity) {
								$entity->spawnToAll();
							}
						} elseif($w == 2){
							$fw = ItemFactory::get(Item::FIREWORKS);
							$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_RED, "", false, false);
							$fw->setFlightDuration(2);
							$level = Server::getInstance()->getLevelByName($this->world);
							$vector3 = $player->asVector3()->add(0.5, 1, 0.5);
							$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
							$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);
							if ($entity instanceof Entity) {
								$entity->spawnToAll();
							}
						} elseif($w == 3){
							$fw = ItemFactory::get(Item::FIREWORKS);
							$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_YELLOW, "", false, false);
							$fw->setFlightDuration(2);

							$level = Server::getInstance()->getLevelByName($this->world);
							$vector3 = $player->asVector3()->add(0.5, 1, 0.5);

							$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
							$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);
							if ($entity instanceof Entity) {
								$entity->spawnToAll();
							}
						} elseif($w == 4){
							$fw = ItemFactory::get(Item::FIREWORKS);
							$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_DARK_AQUA, "", false, false);
							$fw->setFlightDuration(2);

							$level = Server::getInstance()->getLevelByName($this->world);
							$vector3 = $player->asVector3()->add(0.5, 1, 0.5);

							$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
							$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);

							if ($entity instanceof Entity) {
								$entity->spawnToAll();
							}
						} elseif($w == 5){
							$fw = ItemFactory::get(Item::FIREWORKS);
							$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_BLACK, "", false, false);
							$fw->setFlightDuration(2);

							$level = Server::getInstance()->getLevelByName($this->world);
							$vector3 = $player->asVector3()->add(0.5, 1, 0.5);
							
							$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
							$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);
							if ($entity instanceof Entity) {
								$entity->spawnToAll();
							}
						} elseif($w == 1){
							$fw = ItemFactory::get(Item::FIREWORKS);
							$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_BLUE, "", false, false);
							$fw->setFlightDuration(2);

							$level = Server::getInstance()->getLevelByName($this->world);
							$vector3 = $player->asVector3()->add(0.5, 1, 0.5);

							$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
							$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);
							if ($entity instanceof Entity) {
								$entity->spawnToAll();
							}
						}
						$w = new Config($this->plugin->getDataFolder() . "Players/{$player->getName()}.yml", Config::YAML);
						if($w->get("Anvil_Win") == "true"){
							$px = $player->getX();
							$py = $player->getY();
							$pz = $player->getZ();
							$player->getLevel()->setBlock(new Vector3($px+$this->getRandomLevel(), $py+8, $pz+$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px+$this->getRandomLevel(), $py+8, $pz+$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px+$this->getRandomLevel(), $py+mt_rand(7, 8), $pz+$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px+$this->getRandomLevel(), $py+8, $pz+$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px+$this->getRandomLevel(), $py+8, $pz+$this->getRandomLevel()), Block::get(Block::ANVIL));

							$player->getLevel()->setBlock(new Vector3($px-$this->getRandomLevel(), $py+8, $pz-$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px-$this->getRandomLevel(), $py+8, $pz-$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px-$this->getRandomLevel(), $py+8, $pz-$this->getRandomLevel()), Block::get(Block::ANVIL));
							$player->getLevel()->setBlock(new Vector3($px-$this->getRandomLevel(), $py+8, $pz-$this->getRandomLevel()), Block::get(Block::ANVIL));
						}
						if($this->endtime == 9){
							if($this->getLang($player->getName()) == "EN"){
								$player->addTitle("§6§lVICTORY!", "§7You where the last man standing!", 10, 30, 10);
							} elseif($this->getLang($player->getName()) == "AR"){
								$player->addTitle("§6§lﺕﺰﻓ ﺪﻘﻟ!", "§7ﻊﺋﺍﺭ ﺀﺍﺩﺍ!", 10, 30, 10);
							}
						}
						$player->sendPopup(TF::YELLOW . "Restart in " . $this->endtime);
						if($this->endtime == 0){
							$this->stop();
						}
					}
				}
			break;
		}
		++$this->time;
	}
	
	public function getRandomLevel(){
		$a = rand(1, 5);
		switch($a){
			case 1:
			$i = 1;
			break;
			
			case 2:
			$i = 2;
			break;
			
			case 3:
			$i = 3;
			
			case 4:
			$i = 4;
			
			case 5:
			$i = 5;
			break;
		}
		return $i;
	}
	
	public function addKill(Player $player, int $k = 1){
		if($player instanceof Player && $k > 0){
			if(!isset($this->kills[$player->getRawUniqueId()])){
				$this->kills[$player->getRawUniqueId()] = 0;
			}
			$kills = $this->kills[$player->getRawUniqueId()];
			$this->kills[$player->getRawUniqueId()] = ($kills + $k);
			$t = new Config($this->plugin->getDataFolder() . "Players/{$player->getName()}.yml", Config::YAML);
			if(!$t->get("Kills")){
				$t->set("Kills", 0);
			}
			$t->set("Kills", ($t->get("Kills") + $k));
			$t->save();
		}
	}
	
	public function getKills(Player $player){
		if($player instanceof Player){
			if(!isset($this->kills[$player->getRawUniqueId()])){
				$this->kills[$player->getRawUniqueId()] = 0;
			}
			return $this->kills[$player->getRawUniqueId()];
		}
	}
	
	public function addSoul(Player $player, int $c = 1){
		if($player instanceof Player){
			$t = new Config($this->plugin->getDataFolder() . "Players/{$player->getName()}.yml", Config::YAML);
			if(!$t->get("Souls")){
				$t->set("Souls", 0);
				$t->save();
			}
			$d = $t->get("Souls");
			$t->set("Souls", ($d + $c));
			$t->save();
		}
	}
	
	public function addXP(Player $player, int $c = 1){
		if($player instanceof Player){
			$t = new Config($this->plugin->getDataFolder() . "Players/{$player->getName()}.yml", Config::YAML);
			if(!$t->get("XP")){
				$t->set("XP", 0);
				$t->save();
			}
			$d = $t->get("XP");
			$t->set("XP", ($d + $c));
			$t->save();
		}
	}
	
	// cage 
	public function build(Position $locate): array{
        $loc = clone $locate;
        $list = [];
        $level = $loc->getLevel();
        $part = Block::get(Block::GLASS);
        $loc->y = $loc->y - 1;
        $list[] = $loc->asVector3();
        $level->setBlock($loc->asVector3(), $part, true, true);
        for($i = 0; $i <= 4; $i++){
            $array = [
                $loc->add(1.0, 0.0, 0.0),
                $loc->add(-1.0, 0.0, 0.0),
                $loc->add(0.0, 0.0, 1.0),
                $loc->add(0.0, 0.0, -1.0),
                $loc->add(-1.0, 0.0, -1.0),
                $loc->add(1.0, 0.0, 1.0),
                $loc->add(-1.0, 0.0, 1.0),
                $loc->add(1.0, 0.0, -1.0),
            ];

            for($j = 0; $j < count($array); ++$j){
                $loc2 = $array[$j];
                $list[] = $loc2;
                $level->setBlock($loc2, $part, true, true);
            }
			
            $loc->y = $loc->y + 1;
        }

        $loc->y = $loc->y - 1;
        $list[] = $loc->asVector3();
        $level->setBlock($loc->asVector3(), $part, true, true);
        return $list;
    }
	
	public function clearObstacle(Position $loc){
        $loc->y = $loc->y - 1;
        for($y = 0; $y < 6; ++$y){
            for($z = -2; $z < 2; $z++){
                for($x = -2; $x < 2; $x++){
                    $loc->level->setBlock($loc->add($x, $y, $z), Block::get(0));
                }
            }
        }
    }

    public function clearObstacle2(Position $loc){
        $this->plugin->getServer()->getLevelByName($this->world)->getBlockAt($loc->getX(), $loc->getY(), $loc->getZ())->getLevel()->setBlock($loc->add($loc->getX(), $loc->getY(), $loc->getZ()), Block::get(Block::AIR));
    }
	
	public function clearObstacle1(Position $loc){
        $loc->y = $loc->y - 1;
        for($y = 0; $y < 6; ++$y){
            for($z = -2; $z < 2; $z++){
                for($x = -2; $x < 2; $x++){
					$pp = $loc->add($x, $y, $z);
					if($loc->level->getBlockIdAt($pp->x, $pp->y, $pp->z) === Block::GLASS){
						$loc->level->setBlock($loc->add($x, $y, $z), Block::get(0));
					}
                }
            }
        }
    }
	
	public function join(Player $player, bool $sendErrorMessage = true) : bool
    {
		$t = new Config($this->plugin->getDataFolder() . "arenas/" . $this->getName() . "/settings.yml", Config::YAML);
		$wl = $t->get("Lobby");
		$s = $t->get("SPTP");
		$tt = new Config($this->plugin->getDataFolder() . "arenas/" . $this->getName() . "/wallpos.yml", Config::YAML);
		$m = $tt->get("1_WALL");
		if(!$wl){
			if($player->isOp()){
				$player->sendMessage("Cant Join Waiting lobby not set \nUsage: /mb setlobby");
			} else {
				$player->sendMessage("Cant Join");
			}
			return false;
		}
		if(!$s){
			if($player->isOp()){
				$player->sendMessage("Cant Join Spectator pos not set \nUsage: /mb setsp");
			} else {
				$player->sendMessage("Cant Join");
			}
			return false;
		}
		
		if(!$m){
			if($player->isOp()){
				$player->sendMessage("Cant Join Wall pos not set \nUsage: /mb setwall");
			} else {
				$player->sendMessage("Cant Join");
			}
			return false;
		}
		if ($this->GAME_STATE !== Arena::STATE_COUNTDOWN) {
            if ($sendErrorMessage) {
                $player->sendMessage($this->plugin->lang["sign.game.running"]);
            }

            return false;
        }
		if (count($this->players) >= $this->slot) {
            if ($sendErrorMessage) {
                $player->sendMessage($this->plugin->lang["sign.game.full"]);
            }
			
            return false;
        }
		
		//Sound
        $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);

        //Removes player things
        $player->setGamemode(2);

        $player->setMaxHealth($this->plugin->configs["join.max.health"]);
		if ($player->getAttributeMap() !== null) {//just to be really sure
            if (($health = $this->plugin->configs["join.health"]) > $player->getMaxHealth() || $health < 1) {
                $health = $player->getMaxHealth();
            }
			
            $player->setHealth($health);
            $player->setFood(20);
        }
		$player->setScale(1);
		$player->removeAllEffects();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
		$a = 0;
		$playerTeam = $this->plugin->getPlayerTeam($player);
		$items = array_fill(0, count($this->teams), Item::get(Item::WOOL));
		foreach($this->teams as $team){
			$items[$a]->setDamage(Utils::colorIntoWool($team->getColor()));
			$player->getInventory()->addItem($items[$a]);
			$a++;
		}

		$dada = ["§e§l< §6§lM §e§l>", "§e§l< §6§lMI §e§l>", "§e§l< §6§lMIC §e§l>", "§e§l< §6§lMICR §e§l>", "§e§l< §6§lMICRO §e§l>", "§e§l< §6§lMICRO§f§lB§e§l >", "§e§l< §6§lMICRO§f§lBA§e§l >", "§e§l< §6§lMICRO§f§lBAT§e§l >", "§e§l< §6§lMICRO§f§lBATT §e§l>", "§e§l< §6§lMICRO§f§lBATTL §e§l>", "§e§l< §6§lMICRO§f§lBATTLE §e§l>", "§e§l< §f§lMICROBATTLE §e§l>", "§e§l< §6§lMICROBATTLE §e§l>"];
		$date = date("d/m/Y");
		Scoreboard::new($player, "GameJoin", $dada[$this->kepd3]);
		Scoreboard::setLine($player, 1, "§7".$date);
		Scoreboard::setLine($player, 2, "               ");
		Scoreboard::setLine($player, 3, "Map §a".$this->world);
		Scoreboard::setLine($player, 4, "             ");
		Scoreboard::setLine($player, 5, "Waiting...");
		Scoreboard::setLine($player, 6, "                   ");
		Scoreboard::setLine($player, 7, "§ePlay.EXAMPLE.NET");
		//done scoreboard
		$player->getInventory()->setItem(8, Item::get(355, 14, 1)->setCustomName("§l§cReturn to Lobby§r§7 (Right Click)"));
		//$player->getInventory()->setItem(0, Item::get(Item::BOW, 0, 1)->setCustomName("§aKit Selector§7 (Right Click)"));
		//$player->getInventory()->setItem(1, Item::get(Item::CHEST)->setCustomName("§bVote Chests"));

        $server = $this->plugin->getServer();
        $server->loadLevel($this->world);
        $level = $server->getLevelByName($this->world);
		
		$this->setPlayerState($player, Arena::PLAYER_PLAYING);
		$this->setPState($player, Arena::PLAYER_PLAYING);
		$this->playing[$player->getRawUniqueId()] = Arena::PLAYER_PLAYING;
		
		$tm = array_shift($this->spawns);
		$this->playerSpawns[$player->getRawUniqueId()] = $tm;
		$cc = ($this->countdown - $this->time);
		if($this->tpToCage){
			$tmp = $this->playerSpawns[$player->getRawUniqueId()];
			$server = $this->plugin->getServer();
			$level = $server->getLevelByName($this->world);
			$pos = new Position($tmp["x"] + 0.5, $tmp["y"], $tmp["z"] + 0.5, $level);
            $player->setImmobile(true);
			$this->build($pos);
			$player->teleport($pos, $tmp["yaw"], $tmp["pitch"]);
		} elseif(!$this->tpToCage){
			$t = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML);
			$s = $t->get("Lobby");
		
			$x = $s["PX"];		
			$y = $s["PY"];
			$z = $s["PZ"];
			$player->teleport(new Position($x + 0.5, $y, $z + 0.5, $level));
		}
		$this->plugin->setPlayerArena($player, $this->getName());
        $this->sendMessage(TF::GRAY . $player->getName() . TF::YELLOW . " has joined (" . TF::AQUA . $this->getSlot(true) . TF::YELLOW . "/" . TF::AQUA . "12" . TF::YELLOW . ")!");

        $this->gametime--;
		$this->plugin->refreshSigns($this->SWname, $this->getSlot(true), $this->slot, $this->getState());

        return true;
	}
	
	public function getPlayers(?int $player_state = null) : array
    {
        return array_intersect_key($this->plugin->getServer()->getOnlinePlayers(), $player_state === null ? $this->players : array_intersect($this->players, [$player_state]));
    }
	
	public function sendMessage(string $message) : void
    {
        $this->plugin->getServer()->broadcastMessage($message, $this->getPlayers());
    }

    public function sendPopup(string $message) : void
    {
        $this->plugin->getServer()->broadcastPopup($message, $this->getPlayers());
    }

    public function sendSound(string $sound_class) : void
    {
        if (!is_subclass_of($sound_class, Sound::class, true)) {
            throw new \InvalidArgumentException($sound_class . " must be an instance of " . Sound::class);
        }

        foreach ($this->getPlayers() as $player) {
            $player->getLevel()->addSound(new $sound_class($player), [$player]);
        }
    }
	
	/**
     * @param Player $player
     * @param bool $left
     * @param bool $spectate
     * @return bool
     */
     private function quit(Player $player, bool $left = false, bool $spectate = false) : bool
	 {
		 $playerName = $player->getName();
		 $player->removeAllEffects();
		 //\laith98\MB\Utils\Scoreboard::remove($player);
		 $player->setNameTag($player->getName());
		 $current_state = $this->inArena($player);
		 if ($current_state === Arena::PLAYER_NOT_FOUND) {
            return false;
        }
		if(isset($this->playing[$player->getRawUniqueId()])){
			unset($this->playing[$player->getRawUniqueId()]);
		}
		$this->setPlayerState($player, null);
		$this->setPState($player, 03);
		$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		if ($this->GAME_STATE === Arena::STATE_COUNTDOWN) {
            $player->setImmobile(false);
			$uuid = $player->getRawUniqueId();
			if(isset($this->playerSpawns[$uuid])){
				$this->spawns[] = $this->playerSpawns[$uuid];
				unset($this->playerSpawns[$uuid]);
			}
        }
		if(isset($this->spectators[$player->getRawUniqueId()])){
			unset($this->spectators[$player->getRawUniqueId()]);
		}
		if ($left) {
			if($player->getGamemode() !== 3){
				$this->sendMessage(TF::GRAY . $player->getName() . TF::YELLOW . " has quit (" . TF::AQUA . $this->getSlot(true) . TF::YELLOW . "/" . TF::AQUA . "12" . TF::YELLOW . ")!");
			}
        }
		$player->getInventory()->clearAll();
		return true;
	 }
	 
	 public function closeSp(Player $player){
		 $player->setNameTag($player->getName());
		 if(isset($this->kills[$player->getRawUniqueId()])){
			unset($this->kills[$player->getRawUniqueId()]);
		 }
		 $player->removeAllEffects();
		 $player->getCraftingGrid()->clearAll();
		 if(in_array($player->getName(), $this->inNormal)){
			 $this->rmVote("Normal", 1);
			 unset($this->inNormal[array_search($player->getName(), $this->inNormal)]);
		 } elseif(in_array($player->getName(), $this->inOverPower)){
			 $this->rmVote("Overpower", 1);
			 unset($this->inOverPower[array_search($player->getName(), $this->inOverPower)]);
		}
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->setGamemode(3);
		$level = $this->plugin->getServer()->getLevelByName($this->world);
		$t = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML);
		$s = $t->get("SPTP");

		$x = $s["PX"];
		$y = $s["PY"];
		$z = $s["PZ"];
		$player->teleport(new Position($x + 0.5, $y, $z + 0.5, $level));
		
		$player->setHealth($player->getMaxHealth());
		
		$player->getInventory()->setItem(8, Item::get(355, 14, 1)->setCustomName("§l§cReturn to Lobby§r§7 (Right Click)"));
		$player->getInventory()->setItem(7, Item::get(339, 0, 1)->setCustomName("§l§bPlay Again§r§7 (Right Click)"));
		
		$this->spectators[$player->getRawUniqueId()] = Arena::PLAYER_SPECTATING;
		$this->setPlayerState($player, Arena::PLAYER_SPECTATING);
		$this->spp[$player->getRawUniqueId()] = 3;
		$this->setPState($player, Arena::PLAYER_SPECTATING);
		if(isset($this->playing[$player->getRawUniqueId()])){
			unset($this->playing[$player->getRawUniqueId()]);
		}

		$player->addTitle("§c§lYOU DIED!", "§7You are now spectator!", 5, 20, 5);
		$player->sendMessage(TF::RED . "You died! " . TF::YELLOW . "Want to play again? " . TF::BOLD . TF::AQUA . "Click to play again!");
		$player->setHealth($player->getMaxHealth());
        $player->setFood(20);
		if(isset($this->players[$player->getRawUniqueId()])){
			unset($this->players[$player->getRawUniqueId()]);
		}
	}
	 
	public function getWinE(Player $player){
		$t = new Config($this->plugin->getDataFolder() . "Players/{$player->getName()}.yml", Config::YAML);
		return $t->get("Win_EF");
	}
	
	/**
     * @param Player $p
     * @param bool $left
     * @param bool $spectate
     * @return bool
     */
    public function closePlayer(Player $player, bool $left = false, bool $spectate = false) : bool
    {
		//$i = new Config("plugin_data/KitGUI_HYPE/" . "{$player->getName()}.yml", Config::YAML);
		//$i->set("KIT", "SW");
		//$i->save();
		if(isset($this->kills[$player->getRawUniqueId()])){
			unset($this->kills[$player->getRawUniqueId()]);
		}
		
		$player->setNameTag($player->getName());
		$player->removeAllEffects();
		$player->getCraftingGrid()->clearAll();	
		$player->setGamemode(0);
		$player->setGamemode($player->getServer()->getDefaultGamemode());
		if (in_array($player->getName(), $this->ing)) {
            unset($this->ing[array_search($player->getName(), $this->ing)]);
        }

		if(in_array($player->getName(), $this->inNormal)){
			$this->rmVote("Normal", 1);
			unset($this->inNormal[array_search($player->getName(), $this->inNormal)]);
		} elseif(in_array($player->getName(), $this->inOverPower)){
			$this->rmVote("Overpower", 1);
			unset($this->inOverPower[array_search($player->getName(), $this->inOverPower)]);
		}
		
		//Scoreboard::remove($player);
		$playerName = $player->getName();
		if(isset($this->spectators[$player->getRawUniqueId()])){
			unset($this->spectators[$player->getRawUniqueId()]);
		}

		$player->getArmorInventory()->clearAll();
		
		$player->setImmobile(true);
		$this->setPlayerState($player, null);
		$this->setPState($player, 03);
		$this->plugin->setPlayerArena($player, null);
		if(isset($this->players[$player->getRawUniqueId()])){
			unset($this->players[$player->getRawUniqueId()]);
		}
		
		$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		$player->setImmobile(false);
		$p = $player;
		if(isset($this->playerSpawns[$p->getRawUniqueId()])){
			$tmp = $this->playerSpawns[$p->getRawUniqueId()];
			$server = $this->plugin->getServer();
			$level = $server->getLevelByName($this->world);
			$pos = new Position($tmp["x"] + 0.5, $tmp["y"], $tmp["z"] + 0.5, $level);
			$this->clearObstacle1($pos);
		}
        return true;
    }
	
	private function start() : void
    {
		$this->damageWater = true;
        if ($this->plugin->configs["chest.refill"]) {
            //$this->refillChests();
        }

        foreach ($this->getPlayers() as $player) {
			
			$playerTeam = $this->plugin->getPlayerTeam($player);
			if($playerTeam == null){
				 $players = array();
				 foreach($this->teams as $name => $object){
					 $players[$name] = count($object->getPlayers());
				 }
				 $lowest = min($players);
				 $teamName = array_search($lowest, $players);
				 $team = $this->teams[$teamName];
				 $team->add($player);
				 $playerTeam = $team;
			 }
			 
			$player->setNameTag(TextFormat::BOLD . $playerTeam->getColor() . strtoupper($playerTeam->getName()[0]) . " " .  TextFormat::RESET . $playerTeam->getColor() . $player->getName());
			
			$helmet = Item::get(Item::LEATHER_CAP);
			$chestplate = Item::get(Item::LEATHER_CHESTPLATE);
			$leggings = Item::get(Item::LEATHER_LEGGINGS);
			$boots = Item::get(Item::LEATHER_BOOTS);
			$hasArmorUpdated = true;
		 
			foreach(array_merge([$helmet, $chestplate, $leggings, $boots], !$hasArmorUpdated ? [$leggings, $boots] : []) as $armor){
				$armor->setCustomColor(Utils::colorIntoObject($playerTeam->getColor()));
			}
			
			$player->getArmorInventory()->setHelmet($helmet);
			$player->getArmorInventory()->setChestplate($chestplate);
			$player->getArmorInventory()->setLeggings($leggings);
			$player->getArmorInventory()->setBoots($boots);
			
			
			$tt = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML);
			$pos = $tt->get($playerTeam->getName() . "_SPAWN");
			$x = $pos["PX"];
			$y = $pos["PY"];
			$z = $pos["PZ"];
			$level = $this->plugin->getServer()->getLevelByName($this->world);
			$player->teleport(new Position($x + 0.5, $y, $z + 0.5, $level));
			$pk = new LevelEventPacket();
			$pk->evid = LevelEventPacket::EVENT_SOUND_ORB;
			$pk->data = 0;
			$pk->position = $player->asVector3();
			$player->dataPacket($pk);
			
            $player->setMaxHealth($this->plugin->configs["join.max.health"]);
            $player->setMaxHealth($player->getMaxHealth());

            if ($player->getAttributeMap() !== null) {//just to be really sure
                if (($health = $this->plugin->configs["join.health"]) > $player->getMaxHealth() || $health < 1) {
                    $health = $player->getMaxHealth();
                }

                $player->setHealth($health);
                $player->setFood(20);
            }

            Scoreboard::remove($player);
            $dada = ["§e§l< §6§lM §e§l>", "§e§l< §6§lMI §e§l>", "§e§l< §6§lMIC §e§l>", "§e§l< §6§lMICR §e§l>", "§e§l< §6§lMICRO §e§l>", "§e§l< §6§lMICRO§f§lB§e§l >", "§e§l< §6§lMICRO§f§lBA§e§l >", "§e§l< §6§lMICRO§f§lBAT§e§l >", "§e§l< §6§lMICRO§f§lBATT §e§l>", "§e§l< §6§lMICRO§f§lBATTL §e§l>", "§e§l< §6§lMICRO§f§lBATTLE §e§l>", "§e§l< §f§lMICROBATTLE §e§l>", "§e§l< §6§lMICROBATTLE §e§l>"];
            $date = date("d/m/Y");
            if(!isset($dada[$this->kedip1])){
                $this->kedip1 = 0;
            }
                $team = $this->teams["Red"];
                $redTeam = $team->getPlayers();
                $teams = $this->teams["Blue"];
                $blueTeam = $teams->getPlayers();
                $teama = $this->teams["Yellow"];
                $yellowTeam = $teama->getPlayers();
                $teamf = $this->teams["Green"];
                $greenTeam = $teamf->getPlayers();

                Scoreboard::new($player, "GameStart", $dada[$this->kedip1]);
                Scoreboard::setLine($player, 1, "§7".$date);
                Scoreboard::setLine($player, 2, "§l§b       ");
                Scoreboard::setLine($player, 3, "§l§cRed");
                Scoreboard::setLine($player, 4, "".$redTeam." Alive ");
                Scoreboard::setLine($player, 5, "§l§b    ");
                Scoreboard::setLine($player, 6, "§l§eYellow");
                Scoreboard::setLine($player, 7, "".$yellowTeam." Alive  ");
                Scoreboard::setLine($player, 8, "§l§b   ");
                Scoreboard::setLine($player, 9, "§a§lGreen");
                Scoreboard::setLine($player, 10, "".$greenTeam." Alive    ");
                Scoreboard::setLine($player, 11, "§l§b ");
                Scoreboard::setLine($player, 12, "§b§lBlue");
                Scoreboard::setLine($player, 13, "".$blueTeam." Alive");
                Scoreboard::setLine($player, 14, "§l§b  ");
                Scoreboard::setLine($player, 15, "§ePlay.EXAMPLE.NET");
			if(!isset($this->ing["0"])){
				$this->ing["0"] = $player->getName();
			} elseif(!isset($this->ing["0"])){
				$this->ing["1"] = $player->getName();
			} elseif(!isset($this->ing["1"])){
				$this->ing["2"] = $player->getName();
			} elseif(!isset($this->ing["2"])){
				$this->ing["3"] = $player->getName();
			} elseif(!isset($this->ing["3"])){
				$this->ing["4"] = $player->getName();
			} elseif(!isset($this->ing["4"])){
				$this->ing["5"] = $player->getName();
			} elseif(!isset($this->ing["5"])){
				$this->ing["6"] = $player->getName();
			} elseif(!isset($this->ing["6"])){
				$this->ing["7"] = $player->getName();
			} elseif(!isset($this->ing["7"])){
				$this->ing["8"] = $player->getName();
			} elseif(!isset($this->ing["8"])){
				$this->ing["9"] = $player->getName();
			} elseif(!isset($this->ing["9"])){
				$this->ing["10"] = $player->getName();
			} elseif(!isset($this->ing["10"])){
				$this->ing["11"] = $player->getName();
			}
			
            $player->sendMessage($this->plugin->lang['game.start']);
			$mode = $this->getMode();
				
 
			
            $player->setGamemode(0);
			if($this->getPlayerRank($player) == "Guest"){
					EconomyAPI::getInstance()->addMoney($player, 10);
					$player->sendMessage(TF::YELLOW . "+10 Coins Total");
				} elseif($this->getPlayerRank($player) == "VIP"){
					EconomyAPI::getInstance()->addMoney($player, 15);
					$player->sendMessage(TF::YELLOW . "+15 Coins Total [VIP]");
				} elseif($this->getPlayerRank($player) == "VIPp"){
					EconomyAPI::getInstance()->addMoney($player, 20);
					$player->sendMessage(TF::YELLOW . "+20 Coins Total [VIP+]");
				} elseif($this->getPlayerRank($player) == "MVP"){
					EconomyAPI::getInstance()->addMoney($player, 30);
					$player->sendMessage(TF::YELLOW . "+30 Coins Total [MVP]");
				} elseif($this->getPlayerRank($player) == "MVPp"){
					EconomyAPI::getInstance()->addMoney($player, 40);
					$player->sendMessage(TF::YELLOW . "+40 Coins Total [MVP+]");
				} elseif($this->getPlayerRank($player) == "MOD"){
					EconomyAPI::getInstance()->addMoney($player, 50);
					$player->sendMessage(TF::YELLOW . "+50 Coins Total [MOD]");
				} elseif($this->getPlayerRank($player) == "HELPER"){
					EconomyAPI::getInstance()->addMoney($player, 65);
					$player->sendMessage(TF::YELLOW . "+65 Coins Total [HELPER]");
				} elseif($this->getPlayerRank($player) == "Admin"){
					EconomyAPI::getInstance()->addMoney($player, 80);
					$player->sendMessage(TF::YELLOW . "+80 Coins Total [ADMIN]");
				} elseif($this->getPlayerRank($player) == "CoOwner"){
					EconomyAPI::getInstance()->addMoney($player, 85);
					$player->sendMessage(TF::YELLOW . "+85 Coins Total [CoOwner]");
				} elseif($this->getPlayerRank($player) == "Owner"){
					EconomyAPI::getInstance()->addMoney($player, 90);
					$player->sendMessage(TF::YELLOW . "+90 Coins Total [Owner]");
				}

			$player->getInventory()->remove( Item::get(355, 14, 1));
			$player->getInventory()->remove( Item::get(Item::CHEST));
			$player->getInventory()->remove( Item::get(Item::ANVIL));
			$player->getInventory()->remove( Item::get(Item::BOW));
			$player->getInventory()->remove( Item::get(339, 0, 1));
			$player->getInventory()->remove( Item::get(399, 0, 1));
			$player->getInventory()->clearAll();

			
			$inv = $player->getInventory();
			$ina = $player->getArmorInventory();
			$inv->addItem(Item::get(Item::WOODEN_SWORD));
			$inv->addItem(Item::get(Item::BOW));
			$inv->addItem(Item::get(Item::ARROW));
			$inv->setItem(4, Item::get(Item::LEATHER_CHESTPLATE));
			
			// KITS
			if(isset($this->KIT_0[$player->getName()])){
				$player->getInventory()->clearAll();
			} elseif(isset($this->KIT_1[$player->getName()])){
				$inv->addItem(Item::get(261, 0, 1));
				$inv->addItem(Item::get(262, 0, 16));
			} elseif(isset($this->KIT_2[$player->getName()])){
				$inv->addItem(Item::get(272, 0, 1));
				$ina->setChestplate(Item::get(303, 0, 1));
			} elseif(isset($this->KIT_3[$player->getName()])){
				$ina->setHelmet(Item::get(302, 0, 1));
				$ina->setChestplate(Item::get(303, 0, 1));
				$ina->setLeggings(Item::get(304, 0, 1));
				$ina->setBoots(Item::get(305, 0, 1));
			} elseif(isset($this->KIT_4[$player->getName()])){
				$ina->setBoots(Item::get(313, 0, 1));
				$inv->addItem(Item::get(368, 0, 8));
			} elseif(isset($this->KIT_5[$player->getName()])){
				$ina->setHelmet(Item::get(302, 0, 1));
				$ina->setBoots(Item::get(305, 0, 1));
			} elseif(isset($this->KIT_6[$player->getName()])){
				$ina->setBoots(Item::get(313, 0, 1));
				$inv->addItem(Item::get(30, 0, 24));
			}
			
            $level = $player->getLevel();
            $pos = $player->floor();

            /*for ($i = 1; $i <= 2; ++$i) {
                if ($level->getBlockIdAt($pos->x, $pos->y - $i, $pos->z) === Block::GLASS) {
                    $level->setBlock($pos->subtract(0, $i, 0), Block::get(Block::AIR), false);
                }
            }*/

			$player->setAllowFlight(false);
            $player->setImmobile(false);
        }

        $this->time = 0;
        $this->gametime = 420;
        $this->GAME_STATE = Arena::STATE_RUNNING;
        $this->plugin->refreshSigns($this->SWname, $this->getSlot(true), $this->slot, $this->getState());
    }
	
	public function getPlayerRank(Player $player): string{
		/** @var PurePerms $purePerms */
		$purePerms = $this->plugin->getServer()->getPluginManager()->getPlugin("PurePerms");
		if($purePerms !== null){
			$group = $purePerms->getUserDataMgr()->getData($player)['group'];
			if($group !== null){
				return $group;
			} else {
				return "No Rank";
			}
		}else{
			return "Plugin not found";
		}
	}
	
	public function stop(bool $force = false) : bool
    {
		$server = $this->plugin->getServer();
		$server->loadLevel($this->world);
        $this->gametime = 420;
		$this->chestrefill = 0;
		foreach ($this->plugin->getServer()->getLevelByName($this->world)->getPlayers() as $player) {
			if ($player instanceof Player){
				$s = $this->getPlayerState($player);
				if($s === Arena::PLAYER_SPECTATING){
					$this->closePlayer($player);
				}
			}
		}
		
		foreach ($this->getPlayers() as $player) {
			$is_winner = !$force && $this->inArena($player) === Arena::PLAYER_PLAYING;
			if($this->closePlayer($player)){
				$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
			}
			$e = new Config($this->plugin->getDataFolder() . "arenas/{$this->world}/vote.yml", Config::YAML);

			$e->set("Normal", 0);
			$e->set("Overpower", 0);
			Scoreboard::remove($player);
			$e->save();
			if ($is_winner) {
				//Broadcast winner
                $server->broadcastMessage(str_replace(["{SWNAME}", "{PLAYER}"], [$this->SWname, $player->getName()], $this->plugin->lang["server.broadcast.winner"]), $server->getDefaultLevel()->getPlayers());

                if($this->getLang($player->getName()) == "EN"){
					//$player->addTitle("§6§lVICTORY!", "§7You where the last man standing!", 10, 30, 10);
				} elseif($this->getLang($player->getName()) == "AR"){
					//$player->addTitle("§6§lﺕﺰﻓ ﺪﻘﻟ!", "§7ﻊﺋﺍﺭ ﺀﺍﺩﺍ!", 10, 30, 10);
				}

				$n = $player->getName();
				
                if(($api = $this->plugin->getServer()->getPluginManager()->getPlugin("TopWins")) instanceof \pocketmine\plugin\Plugin){
                  $api->addWin($player->getName(), 1);
                }

				//\laith98\MB\Utils\Scoreboard::remove($player);

				if(!$player->isOp() or !$player->hasPermission("fly.command")){
					$player->setAllowFlight(false);
					$player->setFlying(false);
				}

				if(($api = $this->plugin->getServer()->getPluginManager()->getPlugin("LevelUP-HYPE")) instanceof  \pocketmine\plugin\Plugin){
					$api->addPoints($player, 2);
					//$player->sendMessage(TF::YELLOW . "+2 XP Total");
				}

				if(($api = $this->plugin->getServer()->getPluginManager()->getPlugin("LobbyCore_System")) instanceof  \pocketmine\plugin\Plugin){
					$api->getItems($player);
				}
				
				$this->addXP($player, 2);
				$player->sendMessage(TF::YELLOW . "+2 XP Total");
				
				if($this->getPlayerRank($player) == "Guest"){
					EconomyAPI::getInstance()->addMoney($player, 100);
					$player->sendMessage(TF::YELLOW . "+100 Coins Total");
				} elseif($this->getPlayerRank($player) == "VIP"){
					EconomyAPI::getInstance()->addMoney($player, 150);
					$player->sendMessage(TF::YELLOW . "+150 Coins Total [VIP]");
				} elseif($this->getPlayerRank($player) == "VIPp"){
					EconomyAPI::getInstance()->addMoney($player, 200);
					$player->sendMessage(TF::YELLOW . "+200 Coins Total [VIP+]");
				} elseif($this->getPlayerRank($player) == "MVP"){
					EconomyAPI::getInstance()->addMoney($player, 300);
					$player->sendMessage(TF::YELLOW . "+300 Coins Total [MVP]");
				} elseif($this->getPlayerRank($player) == "MVPp"){
					EconomyAPI::getInstance()->addMoney($player, 400);
					$player->sendMessage(TF::YELLOW . "+400 Coins Total [MVP+]");
				} elseif($this->getPlayerRank($player) == "MOD"){
					EconomyAPI::getInstance()->addMoney($player, 500);
					$player->sendMessage(TF::YELLOW . "+500 Coins Total [MOD]");
				} elseif($this->getPlayerRank($player) == "HELPER"){
					EconomyAPI::getInstance()->addMoney($player, 650);
					$player->sendMessage(TF::YELLOW . "+650 Coins Total [HELPER]");
				} elseif($this->getPlayerRank($player) == "Admin"){
					EconomyAPI::getInstance()->addMoney($player, 800);
					$player->sendMessage(TF::YELLOW . "+800 Coins Total [ADMIN]");
				} elseif($this->getPlayerRank($player) == "CoOwner"){
					EconomyAPI::getInstance()->addMoney($player, 850);
					$player->sendMessage(TF::YELLOW . "+850 Coins Total [CoOwner]");
				} elseif($this->getPlayerRank($player) == "Owner"){
					EconomyAPI::getInstance()->addMoney($player, 900);
					$player->sendMessage(TF::YELLOW . "+900 Coins Total [Owner]");
				} elseif(!in_array($this->getPlayerRank($player), $this->ranks)){
					EconomyAPI::getInstance()->addMoney($player, 100); 
					$player->sendMessage(TF::YELLOW . "+100 Coins Total");
				}
			} elseif($force == true){
				if($this->getLang($player->getName()) == "EN"){
					$player->addTitle("§c§lGAME END", "§7You were't victorious this time", 10, 30, 10);
					$player->getInventory()->clearAll();
					$player->getArmorInventory()->clearAll();
					$this->plugin->saveResource($this->plugin->getDataFolder()."arenas/$this->SWname/pdata/wins.yml");
					$kfg = new Config($this->plugin->getDataFolder()."arenas/$this->SWname/pdata/wins.yml", Config::YAML);
					$kfg->set($player->getName()+1);
					$kfg->getAll();
					$kfg->save();
					if($player instanceof Player){
					    foreach ($player->getLevel()->getEntities() as $entity){
					        if($entity instanceof ItemEntity){
					            $entity->flagForDespawn();
                            }
                        }
                    }
				}

				if(($api = $this->plugin->getServer()->getPluginManager()->getPlugin("LobbyCore_System")) instanceof  \pocketmine\plugin\Plugin){
					$api->getItems($player);
				}
			}
		}
		$this->reload();
		return true;
	}
}