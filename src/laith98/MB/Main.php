<?php

namespace laith98\MB;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{CommandSender, Command};
use pocketmine\{Player, Server};
use pocketmine\utils\{Config, TextFormat as TF};
use pocketmine\utils\TextFormat;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\level\sound\{
	ClickSound, 
	EndermanTeleportSound, 
	Sound
	};

use laith98\MB\Tasks\GameTask;
use laith98\MB\Events\EventListener;
use laith98\MB\Game\Arena;
use laith98\MB\Game\Team;

class Main extends PluginBase
{
	/** @var array $arenas         */
	public $arenas = [];
	
	/** @var array $LastDamage     */
	public $LastDamage = [];
	
	/** @var array $signs          */
    public $signs = [];
	
	/** @var array $wallSetup          */
    public $wallSetup = [];
	
	/** @var array $players_arenas */
	public $players_arenas = [];
	
	/** @var Config $config        */
	public $config;
	
	/** @var Config $configs       */
	public $configs;
	
	/** @var Config $lang          */
	public $lang;
	
	/** @var Config $langs         */
	public $langs;
	
	public static $instance;

	public function onEnable(){
		self::$instance = $this;
		
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "Players");
		
		// registerEvents & Tasks
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
		
		// save all files
		foreach ($this->getResources() as $resource) {
            $this->saveResource($resource->getFilename());
        }
		
		// load configs
		$this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
		$this->langs = new Config($this->getDataFolder() . "Lang.yml", Config::YAML);
		
		$this->configs = array_map(function($value){ return is_string($value) ? TextFormat::colorize($value) : $value; }, yaml_parse_file($this->getDataFolder() . "Config.yml"));
        $this->lang = array_map([TextFormat::class, "colorize"], yaml_parse_file($this->getDataFolder() . "Lang.yml"));
		
		// load signs & arenas & enchantments
		$this->registerTypes();
		$this->loadArenas();
		$this->loadSigns();
	}
	
	public static function getInstance(){
		return self::$instance;
	}
	
	public function getLang(string $n){
		$api = $this->getServer()->getPluginManager()->getPlugin("LobbyCore_System");// get Player Lang u can set plugin name
		if($api !== null){
			$m = $api->getPlayerLang($n);
			if($m == "EN"){
				return "EN";
			} elseif($m == "AR"){
				return "AR";	
			}
		}
	}
	
	public function registerTypes() : void{
        Enchantment::registerEnchantment(new Enchantment(Enchantment::POWER, "Power", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 5));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::FLAME, "Flame", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 2));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::INFINITY, "Infinity", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 1));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::PUNCH, "Punch", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 2));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::KNOCKBACK, "Knockback", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 2));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::FIRE_ASPECT, "Fire aspect", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 2));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::SHARPNESS, "Sharpness", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_AXE, 5));
    }

	
	private function formatUsageMessage(string $message) : string
    {
        //The widely-accepted rule is <> brackets for mandatory fields and [] for optional.
        //Highlights message in red, <> in yellow and [] in gray

        return TextFormat::RED . strtr($message, [
                "[" => TextFormat::GRAY . "[",
                "]" => "]" . TextFormat::RED,
                "<" => TextFormat::YELLOW . "<",
                ">" => ">" . TextFormat::RED
            ]);
    }
	
	public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool
    {
		switch(strtolower($command->getName())){
			case "mb":
				if(!$sender instanceof Player){
					$sender->sendMessage("USE IN GAME");
					return false;
				}
				
				if(!isset($args[0])){
					$sender->sendMessage("Usage: /mb help");
					return false;
				}
				if(strtolower($args[0]) == "state"){
					if(!$sender instanceof Player || isset($args[1])){
						return false;
					}
					
					$this->OpenPlayerStateUI($sender);
					return true;
				}
				
				if(strtolower($args[0]) == "openkit"){
					if(!$sender instanceof Player || $this->getPlayerArena($sender) == null || isset($args[1])){
						return false;
					}
					
					$this->OpenKitUI($sender);
					return true;
				}
				
				if(strtolower($args[0]) == "donewall"){
					if(isset($this->wallSetup[$sender->getId()])){
						unset($this->wallSetup[$sender->getId()]);
						return true;
					}
					$sender->sendMessage(TF::RED . "you not in wall setup");
					return false;
				}
				if(strtolower($args[0]) == "setwall"){
					if(!$sender instanceof Player){
						return false;
					}
					if (isset($args[1])) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " setwall"));
						return false;
					}
					
					$level_name = $sender->getLevel()->getFolderName();

					foreach ($this->arenas as $name => $arena_instance) {
						if ($arena_instance->getWorld() === $level_name) {
							$arena = $arena_instance;
							break;
						}
					}

					if (!isset($arena)) {
						$sender->sendMessage(TextFormat::RED . "Arena not found here, try " . TextFormat::GREEN . "/" . $commandLabel . " create");
						return false;
					}
					$sender->sendMessage(TF::YELLOW . "Ok, now break all the wall blocks");
					$this->wallSetup[$sender->getId()] = 1;
					return true;
				}
				
				if(strtolower($args[0]) == "setlobby"){
					if(!$sender->isOp()){
						return false;
					}
					if (isset($args[1])) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " setlobby"));
						return false;
					}

					$level_name = $sender->getLevel()->getFolderName();

					foreach ($this->arenas as $name => $arena_instance) {
						if ($arena_instance->getWorld() === $level_name) {
							$arena = $arena_instance;
							break;
						}
					}

					if (!isset($arena)) {
						$sender->sendMessage(TextFormat::RED . "Arena not found here, try " . TextFormat::GREEN . "/" . $commandLabel . " create");
						return false;
					}
					
					$t = new Config($this->getDataFolder() . "arenas/" . $arena->getName() . "/settings.yml", Config::YAML);
					$t->set("Lobby", ["PX" => $sender->getX(), "PY" => $sender->getY(), "PZ" => $sender->getZ()]);
					$t->save();
					$sender->sendMessage(TextFormat::GREEN . "Done Set Waiting Lobby");
					return true;
				}
				
				if(strtolower($args[0]) == "setsp"){
					if(!$sender->isOp()){
						return false;
					}
					if (isset($args[1])) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " setsp"));
						return false;
					}

					$level_name = $sender->getLevel()->getFolderName();

					foreach ($this->arenas as $name => $arena_instance) {
						if ($arena_instance->getWorld() === $level_name) {
							$arena = $arena_instance;
							break;
						}
					}

					if (!isset($arena)) {
						$sender->sendMessage(TextFormat::RED . "Arena not found here, try " . TextFormat::GREEN . "/" . $commandLabel . " create");
						return false;
					}

					$t = new Config($this->getDataFolder() . "arenas/" . $arena->getName() . "/settings.yml", Config::YAML);
					$t->set("SPTP", ["PX" => $sender->getX(), "PY" => $sender->getY(), "PZ" => $sender->getZ()]);
					$t->save();
					$sender->sendMessage(TextFormat::GREEN . "Done Set Spectete Pos");
					return true;
				}
				
				if(strtolower($args[0]) == "join"){
					if (count($args) < 1 || !($sender instanceof Player)) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /mb join <arena> [PlayerName=YOU]"));
						return false;
					}
					/*if(isset($args[3])){
						$p = $args[3];
						$pp = $this->getServer()->getPlayer($p);
						if($pp !== null){
							$sender = $pp;
						}
					}*/
					if(!isset($args[1])){
						foreach ($this->arenas as $arena) {
							if ($arena->join($sender)) {
								return true;
							}
						}
					}
					if(isset($args[1])){
						if(isset($this->arenas[$args[1]])){
							$arena = $this->arenas[$args[1]];
							if ($arena->join($sender)) {
								return true;
							}
						} else {
							$sender->sendMessage(TextFormat::RED . "Arena not found.");
							return false;
						}
					}
					$sender->sendMessage(TextFormat::RED . "No games, retry later.");
					return false;
				}
				
				if(strtolower($args[0]) == "quit"){
					if ($sender instanceof Player) {
						foreach ($this->arenas as $arena) {
							if ($arena->closePlayer($sender)) {
								if(($api = $this->getServer()->getPluginManager()->getPlugin("LobbyCore_System")) instanceof  \pocketmine\plugin\Plugin){
									$api->getItems($sender);
								}
								$sender->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
								return true;
							}
								$sender->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
							}
							$sender->sendMessage(TextFormat::RED . "You are not in an arena.");
							return false;
						}
					$sender->sendMessage(TextFormat::RED . "This command is only avaible in game.");
					return false;
				}
				
				if(strtolower($args[0]) == "create"){
					if(!$sender->isOp()){
						return false;
					}
					$args_c = count($args);
					if ($args_c < 2) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " create <SWname> <slots> [countdown=30] [maxGameTime=600] [Mode=n-i, normal-insane]1"));
						return false;
					}
					$arena = $args[1];
					var_dump("1 => " . $args[1]);
					if (isset($this->arenas[$arena])) {
						$sender->sendMessage(TextFormat::RED . "An arena with this name already exists.");
						return false;
					}

					$len = strlen($arena);
					if ($len < 3 || $len > 20 || !ctype_alnum($arena)) {//TODO: Figure out the reason behind this.
						$sender->sendMessage(TextFormat::RED . "Arena name must contain 3-20 digits and must be alpha-numeric.");
						return false;
					}
					$level = $sender->getLevel();
					$level_name = $level->getFolderName();

					if ($this->getServer()->getDefaultLevel() === $level) {//TODO: Figure out the reason behind this.
						$sender->sendMessage(TextFormat::RED . "You can't create an arena in the default world.");
						return false;
					}

					//Checks if there is already an arena in the world
					foreach ($this->arenas as $aname => $arena_instance) {
						if ($arena_instance->getWorld() === $level_name) {
							$sender->sendMessage(
								TextFormat::RED . "You can't create multiple arenas in the same world. Try:" . TextFormat::EOL .
								TextFormat::GOLD . "/" . $commandLabel . " list " . TextFormat::RED . "for a list of arenas." . TextFormat::EOL .
								TextFormat::GOLD . "/" . $commandLabel . " delete " . TextFormat::RED . "to delete an arena."
							);
							return false;
						}
					}
					 //Checks if there is already a join sign in the world
					foreach ($this->signs as $loc => $name) {
						$xyzworld = explode(":", $loc);
						if ($xyzworld[3] === $level_name) {
							$sender->sendMessage(
								TextFormat::RED . "You can't create an arena in the same world of a join sign:" . TextFormat::EOL .
								TextFormat::YELLOW . "Remove the sign at (X=" . $xyzworld[0] . ", Y=" . $xyzworld[1] . ", Z=" . $xyzworld[2] . ")" . TextFormat::EOL .
								TextFormat::RED . "Use " . TextFormat::YELLOW . "/" . $commandLabel . " signdelete " . TextFormat::RED . "to delete signs."
							);
							return false;
						}
					}
					$maxslots = $args[2];
					var_dump("2 => " . $args[2]);
					if (!is_numeric($maxslots) || strpos($maxslots, ".") !== false || $maxslots < 1) {
						$sender->sendMessage(TextFormat::RED . "Invalid maxslots value '" . $maxslots . "', maxslots must be an integer > 0.");
						return false;
					}

					$maxslots = (int) $maxslots;

					if (isset($args[3])) {
						$countdown = $args[3];
						var_dump("3 => " . $args[3]);
						if (!is_numeric($countdown) || strpos($countdown, ".") !== false || $countdown < 1) {
							$sender->sendMessage(TextFormat::RED . "Invalid countdown value '" . $countdown . "', countdown must be an integer > 0.");
							return false;
						}

						$countdown = (int) $countdown;
					} else {
						$countdown = 30;
					}

					if (isset($args[4])) {
						$maxtime = $args[4];
						var_dump("4 => " . $args[4]);
						if (!is_numeric($maxtime) || strpos($maxtime, ".") !== false || $maxtime < 1) {
							$sender->sendMessage(TextFormat::RED . "Invalid maxGameTime value '" . $maxtime . "', maxGameTime must be an integer > 0.");
							return false;
						}
					} else {
						$maxtime = 600;
					}
					
					$provider = $sender->getLevel()->getProvider();
					if ($this->configs["world.generator.air"]) {
						$level_data = $provider->getLevelData();
						$level_data->setString("generatorName", "flat");
						$level_data->setString("generatorOptions", "0;0;0");
						$provider->saveLevelData();
					}

					$sender->sendMessage(TextFormat::LIGHT_PURPLE . "Calculating minimum void in world '" . $level_name . "'...");

					//This is the "fake void"
					$void_y = Level::Y_MAX;
					foreach ($level->getChunks() as $chunk) {
						for ($x = 0; $x < 16; ++$x) {
							for ($z = 0; $z < 16; ++$z) {
								for ($y = 0; $y < $void_y; ++$y) {
									$block = $chunk->getBlockId($x, $y, $z);
									if ($block !== Block::AIR) {
										$void_y = $y;
										break;
									}
								}
							}
						}
					}
					--$void_y;
					$sender->sendMessage(TextFormat::LIGHT_PURPLE . "Minimum void set to: " . $void_y);

					$server = $sender->getServer();

					$sender->teleport($server->getDefaultLevel()->getSpawnLocation());
					$server->unloadLevel($level);
					unset($level);
					$sender->sendMessage(TextFormat::LIGHT_PURPLE . "Creating backup of world '" . $level_name . "'...");

					@mkdir($this->getDataFolder() . "arenas/" . $arena, 0755);

					$tar = new \PharData($this->getDataFolder() . "arenas/" . $arena . "/" . $level_name . ".tar");
					$tar->startBuffering();
					$tar->buildFromDirectory(realpath($sender->getServer()->getDataPath() . "worlds/" . $level_name));

					if ($this->configs["world.compress.tar"]) {
						$sender->sendMessage(TextFormat::ITALIC . TextFormat::GRAY . "Compressing world (tar-gz)...");
						$tar->compress(\Phar::GZ);
						$sender->sendMessage(TextFormat::ITALIC . TextFormat::GRAY . "World compressed.");
					}

					$tar->stopBuffering();
					$sender->sendMessage(TextFormat::LIGHT_PURPLE . "Backup of world '" . $level_name . "' created.");

					if ($this->configs["world.compress.tar"]) {
						$tar = null;
						@unlink($this->getDataFolder() . "arenas/" . $arena . "/" . $level_name . ".tar");
					}
					
					$sender->getServer()->loadLevel($level_name);
					$this->arenas[$arena] = new Arena($this, $arena, $maxslots, $level_name, $countdown, $maxtime, $void_y);

					$sender->sendMessage(
						TextFormat::GREEN . "Arena " . TextFormat::DARK_GREEN . $arena . TextFormat::GREEN . " created successfully!" . TextFormat::EOL .
						TextFormat::GREEN . "Use " . TextFormat::YELLOW . "/" . $commandLabel . " setspawn <slot#> " . TextFormat::GREEN . "to set spawnpoints for " . TextFormat::YELLOW . $arena . TextFormat::GREEN . "."
					);

					$sender->teleport($sender->getServer()->getLevelByName($level_name)->getSpawnLocation());
					return true;
				}
				
				if(strtolower($args[0]) == "setspawn"){
					if(!$sender->isOp()){
						return false;
					}
					if (isset($args[2])) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " setspawn <red|blue|green|yellow>"));
						return false;
					}
					$level_name = $sender->getLevel()->getFolderName();

					foreach ($this->arenas as $name => $arena_instance) {
						if ($arena_instance->getWorld() === $level_name) {
							$arena = $arena_instance;
							break;
						}
					}
					
					if (!isset($arena)) {
						$sender->sendMessage(TextFormat::RED . "Arena not found here, try " . TextFormat::YELLOW . "/" . $commandLabel . " create");
						return false;
					}
					
					if(!in_array(strtolower($args[1]), array('blue', 'red', 'yellow', 'green'))){
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " setspawn <red|blue|green|yellow>"));
						return false;
					}
					$t = "";
					if(strtolower($args[1]) == "blue"){
						$t = "Blue";
					}
					
					if(strtolower($args[1]) == "red"){
						$t = "Red";
					}
					
					if(strtolower($args[1]) == "green"){
						$t = "Green";
					}
					
					if(strtolower($args[1]) == "yellow"){
						$t = "Yellow";
					}
					$tt = new Config($this->getDataFolder() . "arenas/" . $arena->getName() . "/settings.yml", Config::YAML);
					$tt->set($t . "_SPAWN", ["PX" => $sender->getX(), "PY" => $sender->getY(), "PZ" => $sender->getZ()]);
					$tt->save();
					$sender->sendMessage(TF::YELLOW . "Done set spawn");
					return true;
				}
				
				if(strtolower($args[0]) == "signdelete"){
					if(!$sender->isOp()){
						return false;
					}
					if (count($args) !== 1) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " signdelete <arena|all>"));
						return false;
					}
					$arena = $args[1];

					if (isset($this->arenas[$arena])) {
						$count = $this->deleteAllSigns($arena);
					} elseif ($arena === "all") {
						$count = $this->deleteAllSigns();
					} else {
						$sender->sendMessage(TextFormat::RED . "Arena '" . TextFormat::YELLOW . $arena . TextFormat::RED . " does not exist!");
						return false;
					}

					$sender->sendMessage(TextFormat::YELLOW . "Deleted " . $count . " signs from " . ($arena === "all" ? "ALL arenas" : "'" . $arena . "' Arena") . ".");
				}
				
				if(strtolower($args[0]) == "help"){
					if ($sender->isOp()) {
						$sender->sendMessage($this->formatUsageMessage("Usage: /mb <join|quit|setsp|setlobby|setwall|donewall|signdelete|create|setspawn>"));
					} else {
						$sender->sendMessage($this->formatUsageMessage("Usage: /" . $commandLabel . " <join|quit>"));
					}
				}
			break;
		}
		return true;
	}
	
	/**
     * @param Player $player
     * @return Team|null
     */
    public function getPlayerTeam(Player $player) : ?Team{
        $game = $this->getPlayerArena($player);
        if($game == null)return null;

        foreach($game->teams as $team){
            if(in_array($player->getRawUniqueId(), array_keys($team->getPlayers()))){
                return $team;
            }
        }
        return null;
    }
	
	public function loadArenas() : void
    {
        $base_path = $this->getDataFolder() . "arenas/";
        @mkdir($base_path);

        foreach (scandir($base_path) as $dir) {
            $dir = $base_path . $dir;
            $settings_path = $dir . "/settings.yml";

            if (!is_file($settings_path)) {
                continue;
            }

            $arena_info = yaml_parse_file($settings_path);

            $this->arenas[$arena_info["name"]] = new Arena(
                $this,
                $arena_info["name"],
                (int) $arena_info["slot"],
                $arena_info["world"],
                (int) $arena_info["countdown"],
                (int) $arena_info["maxGameTime"],
                (int) $arena_info["void_Y"]
			);
        }
    }
	
	public function getNearbySigns(Position $pos, int $radius, &$arena = null) : \Generator
    {
        $pos->x = floor($pos->x);
        $pos->y = floor($pos->y);
        $pos->z = floor($pos->z);

        $level = $pos->getLevel()->getFolderName();

        $minX = $pos->x - $radius;
        $minY = $pos->y - $radius;
        $minZ = $pos->z - $radius;

        $maxX = $pos->x + $radius;
        $maxY = $pos->y + $radius;
        $maxZ = $pos->z + $radius;

        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($y = $minY; $y <= $maxY; ++$y) {
                for ($z = $minZ; $z <= $maxZ; ++$z) {
                    $key =  $x . ":" . $y . ":" . $z . ":" . $level;
                    if (isset($this->signs[$key])) {
                        $arena = $this->signs[$key];
                        yield new Vector3($x, $y, $z);
                    }
                }
            }
        }
    }
	
	public function setSign(string $arena, Position $pos) : void
    {
        $this->signs[$pos->x . ":" . $pos->y . ":" . $pos->z . ":" . $pos->getLevel()->getFolderName()] = $arena;
    }

    public function deleteSign(Position $pos) : void
    {
        $level = $pos->getLevel();

        $pos = $pos->floor();
        $level->useBreakOn($pos);

        unset($this->signs[$pos->x . ":" . $pos->y . ":" . $pos->z . ":" . $level->getFolderName()]);
    }

    public function deleteAllSigns(?string $arena = null) : int
    {
        $count = 0;

        if ($arena === null) {
            foreach ($this->signs as $arena) {
                $count += $this->deleteAllSigns($arena);
            }
        } else {
            foreach (array_keys($this->signs, $arena, true) as $xyzw) {
                $xyzw = explode(":", $xyzw, 4);

                $server = $this->getServer();
                $server->loadLevel($xyzw[3]);
                $level = $server->getLevelByName($xyzw[3]);

                if ($level !== null) {
                    $this->deleteSign(new Position((int) $xyzw[0], (int) $xyzw[1], (int) $xyzw[2], $level));
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function refreshSigns(?string $arena = null, int $players = 0, int $maxplayers = 0, string $state = TextFormat::WHITE . "Tap to join") : void
    {
        if ($arena === null) {
            foreach (array_unique($this->signs) as $arena) {
                $this->refreshSigns($arena);
            }

            return;
        }

        $server = $this->getServer();

        foreach ($this->signs as $xyzworld => $arena_name) {
            if ($arena_name === $arena) {
                [$x, $y, $z, $world] = explode(":", $xyzworld);

                $level = $server->getLevelByName($world);
                if ($level === null) {//console error?
                    continue;
                }

                $tile = $level->getTileAt($x, $y, $z);
                if ($tile instanceof Sign) {
                    $tile->setText(
						TextFormat::YELLOW . "-* " . TextFormat::BOLD . TextFormat::AQUA . "SW" . TextFormat::RESET . TextFormat::YELLOW . " *-",
                        TextFormat::BOLD . TextFormat::GOLD . $arena,
                        TextFormat::GREEN . $players . TextFormat::BOLD . TextFormat::DARK_GRAY . "/" . TextFormat::RESET . TextFormat::GREEN . $maxplayers,
                        $state
                    );
                }
            }
        }
    }
	
	public function getChestContents() : array//TODO: **rewrite** this and let the owner decide the contents of the chest
    {
        $items = [
            //ARMOR
            "armor" => [
                [
                    Item::LEATHER_CAP,
                    Item::LEATHER_TUNIC,
                    Item::LEATHER_PANTS,
                    Item::LEATHER_BOOTS
                ],
                [
                    Item::GOLD_HELMET,
                    Item::GOLD_CHESTPLATE,
                    Item::GOLD_LEGGINGS,
                    Item::GOLD_BOOTS
                ],
                [
                    Item::CHAIN_HELMET,
                    Item::CHAIN_CHESTPLATE,
                    Item::CHAIN_LEGGINGS,
                    Item::CHAIN_BOOTS
                ],
                [
                    Item::IRON_HELMET,
                    Item::IRON_CHESTPLATE,
                    Item::IRON_LEGGINGS,
                    Item::IRON_BOOTS
                ],
                [
                    Item::DIAMOND_HELMET,
                    Item::DIAMOND_CHESTPLATE,
                    Item::DIAMOND_LEGGINGS,
                    Item::DIAMOND_BOOTS
                ]
            ],

            //WEAPONS
            "weapon" => [
                [
                    Item::WOODEN_SWORD,
                    Item::WOODEN_AXE,
                    Item::FLOWING_LAVA
                ],
                [
                    Item::GOLD_SWORD,
                    Item::GOLD_AXE,
                    Item::FLOWING_WATER
                ],
                [
                    Item::STONE_SWORD,
                    Item::STONE_AXE
                ],
                [
                    Item::IRON_SWORD,
                    Item::IRON_AXE,
                    Item::FLINT_AND_STEEL
                ],
                [
                    Item::DIAMOND_SWORD,
                    Item::DIAMOND_AXE
                ]
            ],

            //FOOD
            "food" => [
                [
                    Item::RAW_PORKCHOP,
                    Item::RAW_CHICKEN,
                    Item::MELON_SLICE,
                    Item::COOKIE
                ],
                [
                    Item::RAW_BEEF,
                    Item::CARROT
                ],
                [
                    Item::APPLE,
                    Item::GOLDEN_APPLE,
                    Item::TNT
                ],
                [
                    Item::BEETROOT_SOUP,
                    Item::BREAD,
                    Item::BAKED_POTATO
                ],
                [
                    Item::COOKED_CHICKEN
                ],
                [
                    Item::STEAK,
                ],
            ],

            //THROWABLE
            "throwable" => [
                [
                    Item::BOW,
                    Item::ARROW
                ],
                [
                    Item::SNOWBALL,
                ],
                [
                    Item::EGG
                ]
            ],

            //BLOCKS
            "block" => [
                Item::STONE,
                Item::WOODEN_PLANKS,
                Item::COBBLESTONE,
                Item::DIRT
            ],

            //OTHER
            "other" => [
                [
                    Item::WOODEN_PICKAXE,
                    Item::GOLD_PICKAXE,
                    Item::STONE_PICKAXE,
                    Item::IRON_PICKAXE,
                    Item::DIAMOND_PICKAXE
                ],
                [
                    326
                ]
            ]
        ];

        $templates = [];
        for ($i = 0; $i < 10; $i++) {//TODO: understand wtf is the stuff in here doing

            $armorq = mt_rand(0, 1);
            $armortype = $items["armor"][array_rand($items["armor"])];

            $armor1 = [$armortype[array_rand($armortype)], 1];
            if ($armorq) {
                $armortype = $items["armor"][array_rand($items["armor"])];
                $armor2 = [$armortype[array_rand($armortype)], 1];
            } else {
                $armor2 = [0, 1];
            }

            $weapontype = $items["weapon"][array_rand($items["weapon"])];
            $weapon = [$weapontype[array_rand($weapontype)], 1];

            $ftype = $items["food"][array_rand($items["food"])];
            $food = [$ftype[array_rand($ftype)], mt_rand(2, 5)];

            if (mt_rand(0, 1)) {
                $tr = $items["throwable"][array_rand($items["throwable"])];
                if (count($tr) === 2) {
                    $throwable1 = [$tr[1], mt_rand(10, 20)];
                    $throwable2 = [$tr[0], 1];
                } else {
                    $throwable1 = [0, 1];
                    $throwable2 = [$tr[0], mt_rand(5, 10)];
                }
                $other = [0, 1];
            } else {
                $throwable1 = [0, 1];
                $throwable2 = [0, 1];
                $ot = $items["other"][array_rand($items["other"])];
                $other = [$ot[array_rand($ot)], 1];
            }

            $block = [$items["block"][array_rand($items["block"])], 32];

            $contents = [
                $armor1,
                $armor2,
                $weapon,
                $food,
                $throwable1,
                $throwable2,
                $block,
                $other
            ];
            shuffle($contents);

            $fcontents = [
                mt_rand(0, 1) => array_shift($contents),
                mt_rand(2, 4) => array_shift($contents),
                mt_rand(5, 9) => array_shift($contents),
                mt_rand(10, 14) => array_shift($contents),
                mt_rand(15, 16) => array_shift($contents),
                mt_rand(17, 19) => array_shift($contents),
                mt_rand(20, 24) => array_shift($contents),
                mt_rand(25, 26) => array_shift($contents),
           ];

            $templates[] = $fcontents;
        }

        shuffle($templates);
        return $templates;
    }
	
	public function getPlayerArena(Player $player) : ?Arena
    {
        return isset($this->player_arenas[$pid = $player->getId()]) ? $this->arenas[$this->player_arenas[$pid]] : null;
    }
	
	public function setPlayerArena(Player $player, ?string $arena) : void
    {
        if ($arena === null) {
            unset($this->player_arenas[$player->getId()]);
            return;
        }

        $this->player_arenas[$player->getId()] = $arena;
    }

    public function getArenaFromSign(Position $pos) : ?string
    {
        return $this->signs[$pos->x . ":" . $pos->y . ":" . $pos->z . ":" . $pos->getLevel()->getFolderName()] ?? null;
    }

	
	public function loadSigns() : void
    {
        $signs = yaml_parse_file($this->getDataFolder() . "signs.yml");
        if (!empty($signs)) {
            foreach ($signs as $xyzworld => $arena) {
                [$x, $y, $z, $world] = explode(":", $xyzworld, 4);
                $this->signs[$x . ":" . $y . ":" . $z . ":" . $world] = $arena;
            }
        }
    }
	
	public function getLast(Player $player): ?string
	{
		return isset($this->LastDamage[$player->getId()]) ? $this->LastDamage[$player->getId()] : null;
	}
	
	public function setLast(Player $player, ?Player $killer): void
	{
		if($killer === null){
			if(isset($this->LastDamage[$player->getId()])){
				unset($this->LastDamage[$player->getId()]);
				return;
			}
		}
		$this->LastDamage[$player->getId()] = $killer->getName();
	}
	
	public function OpenPlayerStateUI(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
			}
			 switch($result){
				case 0:
					
				break;
				}
		});
		$t = new Config($this->getDataFolder() . "Players/{$player->getName()}.yml", Config::YAML);
		$kills = $t->get("Kills");
		$souls = $t->get("Souls");
		$xp = $t->get("XP");
		$form->setTitle(TF::YELLOW . "SkyWars State");
		$form->setContent(TF::AQUA . $player->getName() . "s State:\n\n" . TF::WHITE . " - Kills: " . TF::GREEN . $kills . TF::WHITE . "\n - Souls: " . TF::GREEN . $souls . TF::WHITE . "\n - XP: " . TF::GREEN . $xp . "\n");
		$form->addButton(TF::YELLOW . "OKAY!");
		$form->sendToPlayer($player);
	}
	
	public function OpenKitUI(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
			}
			$arena = $this->getPlayerArena($player);
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
		//return $form;
	}
	public function onDisable() : void
    {
        yaml_emit_file($this->getDataFolder() . "signs.yml", $this->signs);
        foreach ($this->arenas as $arena) {
            $arena->stop(true);
        }
    }
}