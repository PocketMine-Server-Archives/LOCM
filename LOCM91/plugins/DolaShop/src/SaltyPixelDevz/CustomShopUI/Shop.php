<?php

declare(strict_types=1);

namespace SaltyPixelDevz\CustomShopUI;

use onebone\doramine\EconomyAPI;
use pmmp\TesterPlugin\TestFailedException;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Server;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\utils\TextFormat as TF;
use jojoe77777\FormAPI\CustomForm;
use JackMD\ConfigUpdater\ConfigUpdater;
use phuongaz\Coin\Coin;

class Shop extends PluginBase
{

    public $msg;
    // For Config Updates!
    private const CONFIG_VERSION = 5;
    // For shop Updates!
    private const SHOP_VERSION = 4;
    // For Message Updates!
    private const MESSAGE_VERSION = 5;
    public function onEnable(): void
    {
        $this->saveDefaultConfig();
        $this->saveResource("messages.yml");
        $this->saveResource("shop.yml");
        $this->checkConfigs();
    }
    public function checkConfigs(): void
    {
        if ((!$this->getConfig()->exists("config-version")) || ($this->getConfig()->get("config-version") != self::CONFIG_VERSION)) {
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
            $this->saveResource("config.yml");
        }
        $dataConfig = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
        if ((!$dataConfig->exists("shop-version")) || ($dataConfig->get("shop-version") != self::SHOP_VERSION)) {
            rename($this->getDataFolder() . "shop.yml", $this->getDataFolder() . "shop_old.yml");
            $this->saveResource("shop.yml");
        }
        $dataConfig = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        if ((!$dataConfig->exists("message-version")) || ($dataConfig->get("message-version") != self::MESSAGE_VERSION)) {
            rename($this->getDataFolder() . "messages.yml", $this->getDataFolder() . "messages_old.yml");
            $this->saveResource("messages.yml");

        }

    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ("a") {
            case "a":
                if ($sender instanceof Player) {
                    $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                    $cfg = yaml_parse_file($this->getDataFolder() . "shop.yml");
                    $level = $sender->getLevel()->getName();
                    $worlds = $this->getConfig()->get("Worlds");
                        if(in_array($level, $worlds) === true){
                            $sender->sendMessage($msg->getNested("Messages.BannedinWorld"));
                        }else {
                            if (empty($args)) {
                                $ans = strtolower($this->getConfig()->get("Default-Shop"));
                                $cfg = $cfg[$ans];
                                if ($sender->getGamemode() != 0 and $this->getConfig()->get("Survival") === true) {
                                    $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                                    $sender->sendMessage($msg->getNested("messages.Survival"));
                                    return true;
                                } else {
                                    $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                                    $this->Category($cfg, $sender, $msg, $ans);
                                    return true;
                                }
                            } else if (!empty($args) && $this->getConfig()->get("Multi-Shop") === true) {
                                $ans = strtolower($args[0]);
                                if (array_key_exists($ans, $cfg)) {
                                    $cfg = $cfg[$ans];
                                    if ($sender->hasPermission("shop.$ans")) {
                                        if ($sender->getGamemode() != 0 and $this->getConfig()->get("Survival") === true) {
                                            $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                                            $sender->sendMessage($msg->getNested("messages.Survival"));
                                            return true;
                                        } else {
                                            $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                                            $this->Category($cfg, $sender, $msg, $ans);
                                            return true;
                                        }
                                        break;
                                    } else if (!$sender->hasPermission("shop.$ans")) {
                                        $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                                        $sender->sendMessage($msg->getNested("Messages.NoPermission"));
                                    }
                                } else {
                                    $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                                    $sender->sendMessage($msg->getNested("Messages.ShopError"));
                                }
                            }
                        }
                } else {
                    $sender->sendMessage(TF::RED . "Please use this in-game.");
                }
                return true;
        }
    }
    public function Category($cfg, Player $player, Config $msg, $ans): void
    {
        $form = new SimpleForm(function (Player $player, int $data = null) use ($cfg, $msg, $ans) : void {
            $categorys = $data;
            if (($categorys === null) && ($this->getConfig()->get("Thanks") === true)) {
                
            } else if (($this->getConfig()->get("Thanks") === true) or ($categorys !== null)) {
                if ($data == 0 && $this->getConfig()->get("Category_ExitButton") === true) {
                   
                } else {
                    if ($this->getConfig()->get("Category_ExitButton") === true) {
                        $categorys = $data - 1;
                        $this->Items($player, $categorys, $cfg, $ans);
                    } else {
                        $categorys = $data;
                        $this->Items($player, $categorys, $cfg, $ans);
                    }
                }
            }
        });
        if ($this->getConfig()->getNested("Titles.$ans" . "title") !== null) {
            $form->setTitle($this->getConfig()->getNested("Titles.$ans" . "title"));
        } else {
            $form->setTitle($this->getConfig()->getNested("Titles.Default"));
        }
        $coin = Coin::getInstance()->getCoin($player);
        $form->setContent("§l§e↦§f Your Lcoin:§e ". $coin);
        if ($this->getConfig()->get("Category_ExitButton") === true) {
            $form->addButton("§l§f§c Ｔhoát §l§f§0", 1 , "https://icons-for-free.com/iconfiles/png/512/cross+exit+remove+icon-1320161389317562876.png");
        }
        foreach ($cfg as $cate => $category) {
            if ($category == self::SHOP_VERSION) {
            } else {
                $list = explode(":", $category["Name"]);
                if (substr($list[1], 0, 4) == "http") {
                    $form->addButton("§l§a".$list[0], 1, $list[1] . ":" . $list[2]);
                } else {
                    $form->addButton("§l§a".$list[0], 0, $list[1]);
                }
            }
        }
        $player->sendForm($form);
    }
    public function Items(Player $player, $categorys, $cfg, $ans)
    {
        $form = new SimpleForm(function (Player $player, int $data = null) use ($cfg, $categorys, $ans) : void {
            $items = $cfg[$categorys];
            if (!isset($items["Sub"])) {
                if ($data == 0 and $this->getConfig()->get("Items_ExitButton") === true) {
                    $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                    $this->Category($cfg, $player, $msg, $ans);
                } else {
                    if ($this->getConfig()->get("Items_ExitButton") === true){
                        $command = $data - 1;
                    }else{
                        $command = $data;
                    }
                    foreach ($items["Items"] as $cate => $item) {
                        $list4[] = $item;
                    }
                    $list = explode(":", $list4[$command]);
                    if ($list[0] == "cmd") {
                        if ($this->getConfig()->get("command_confirm") === true) {
                            $sub = 0;
                            $this->Commandform($player, $cfg, $categorys, $command, $sub, $ans);
                        } else {
                            $sub = 0;
                            $this->Command($player, $cfg, $categorys, $command, $sub, $ans);
                        }
                    } elseif (($list[0] != "cmd")) {
                        $item = $command;
                        $sub = 0;
                        $this->Confirmation($player, $cfg, $categorys, $item, $ans, $sub);
                    }
                }
            }else{
                if ($this->getConfig()->get("Category_ExitButton") === true) {
                    if ($data == 0){
                        $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                        $this->Category($cfg, $player, $msg, $ans);
                    }else {
                        $ans = $data - 1;
                        $this->SubItems($player, $categorys, $cfg, $ans);
                    }
                }else{
                    $ans = $data;
                    $this->SubItems($player, $categorys, $cfg, $ans);
                }
            }
        });
        $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        $form->setTitle($msg->getNested("Titles.Items"));
        $coin = Coin::getInstance()->getCoin($player);
        $form->setContent("§l§e↦§f Your Lcoin:§e ". $coin);
        if ($this->getConfig()->get("Items_ExitButton") === true) {
            $form->addButton("§l§f§c Ｔhoát §l§f§0", 1 , "https://icons-for-free.com/iconfiles/png/512/cross+exit+remove+icon-1320161389317562876.png");
        }
            $items = $cfg[$categorys];
            if (!isset($items["Sub"])) {
                foreach ($items["Items"] as $cate => $item) {
                    $list = explode(":", $item);
                    if ($list[5] == "Default") {
                        $name = Item::get((int)$list[0], (int)$list[1], 1)->getName();
                    } else {
                        $name = $list[5];
                    }
                    if ($list[0] == "cmd") {
                        $msg2 = str_replace("{price}", "$list[2]", $msg->getNested("Messages.Each"));
                        if (substr($list[5], 0, 4) == "http") {
                            $form->addButton("§l§a".$list[1] . " " . $msg2, 1, $list[5] . ":" . $list[6]);
                        } else {
                            $form->addButton("§l§a".$list[1] . " " . $msg2, 0, $list[5]);
                        }
                    } else {
                        $msg2 = str_replace("{price}", "$list[2]", $msg->getNested("Messages.Each"));
                        if (substr($list[6], 0, 4) == "http") {
                            $form->addButton("§l§a".$name . " " . $msg2, 1, $list[6] . ":" . $list[7]);
                        } else {
                            $form->addButton("§l§a".$name . " " . $msg2, 0, $list[6]);
                        }
                    }
                }
            } else {
                $items2 = $items["Sub"];
                foreach ($items2 as $cate => $item) {
                    $list = explode(":", $item["Name"]);
                    if (substr($list[1], 0, 4) == "http") {
                        $form->addButton("§l§a".$list[0], 1, $list[1] . ":" . $list[2]);
                    } else {
                        $form->addButton("§l§a".$list[0], 0, $list[1]);
                    }
                }
            }
        $player->sendForm($form);
    }
    public function SubItems(Player $player, $categorys, $cfg, $ans)
    {
        $form = new SimpleForm(function (Player $player, int $data = null) use ($cfg, $categorys, $ans) : void {
            if ($data == 0 and $this->getConfig()->get("Items_ExitButton") === true) {
                $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                $this->Category($cfg, $player, $msg, $ans);
            } else {
                if ($this->getConfig()->get("Items_ExitButton") === true){
                    $command = $data - 1;
                }else{
                    $command = $data;
                }
                $items = $cfg[$categorys];
                $items2 = $items["Sub"];
                $items3 = $items2[$ans];
                foreach ($items3["Items"] as $cate => $item) {
                    $list4[] = $item;
                }
                $list = explode(":", $list4[$command]);
                if ($list[0] == "cmd") {
                    if ($this->getConfig()->get("command_confirm") === true) {
                        $sub = 1;
                        $this->Commandform($player, $cfg, $categorys, $command, $sub, $ans);
                    } else {
                        $sub = 1;
                        $this->Command($player, $cfg, $categorys, $command, $sub, $ans);
                    }
                } elseif (($list[0] != "cmd")) {
                    $item = $command;
                    $sub = 1;
                    $this->Confirmation($player, $cfg, $categorys, $item, $ans, $sub);
                }
            }
        });
        $coin = Coin::getInstance()->getCoin($player);
        $form->setContent("§l§e↦§f Your Lcoin:§e ". $coin);
        $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        if ($this->getConfig()->get("Items_ExitButton") === true) {
            $form->addButton("§l§f§c Ｔhoát §l§f§0", 1 , "https://icons-for-free.com/iconfiles/png/512/cross+exit+remove+icon-1320161389317562876.png");
        }
            $items = $cfg[$categorys];
            $items2 = $items["Sub"];
            if ($ans !== null) {
                $items3 = $items2[$ans];
                foreach ($items3["Items"] as $cate => $item) {
                    $list = explode(":", $item);
                    if ($list[5] == "Default") {
                        $name = Item::get((int)$list[0], (int)$list[1], 1)->getName();
                    } else {
                        $name = $list[5];
                    }
                    if ($list[0] == "cmd") {
                        if (substr($list[5], 0, 4) == "http") {
                            if($list[2] == 0){
                            $form->addButton("§l§a". $list[1] , 1, $list[5] . ":" . $list[6]);                                
                            }else{
                            $form->addButton("§l§a". $list[1] . " " . $list[2] . $msg->getNested("Messages.Each"), 1, $list[5] . ":" . $list[6]);                                
                            }

                        } else {
                            if($list[2] == 0){
                            $form->addButton("§l§a". $list[1], 0, $list[5]);                                
                        }else{
                           $form->addButton("§l§a". $list[1] . " " . $list[2] . $msg->getNested("Messages.Each"), 0, $list[5]);  
                        } 

                        }
                    } else {
                        $msg2 = str_replace("{price}", "$list[3]", $msg->getNested("Messages.Each"));
                        if (substr($list[6], 0, 4) == "http") {
                            if($list[3] == 0){
                                $form->addButton("§l§a". $name, 1, $list[6] . ":" . $list[7]);                                
                            }else{
                                 $form->addButton("§l§a". $name . " " . $msg2, 1, $list[6] . ":" . $list[7]);
                            }

                        } else {
                            $form->addButton("§l§a". $name . " " . $msg2, 0, $list[6]);
                        }
                    }
                }
                $player->sendForm($form);
            }else if ($ans === null and $this->getConfig()->get("Straight_Back") === false){
                $this->Category($cfg, $player, $msg, $ans);
            }
        }
    // For Items
    public function commandform(Player $player, $cfg, $categorys, $command, $sub, $ans)
    {
        $form = new SimpleForm(function (Player $player, $data) use ($cfg, $categorys, $command, $sub, $ans) {
            $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
            if ($data == 1) {
                $player->sendMessage($msg->getNested("Messages.command_buy_cancel"));
            }
            if ($data == 0) {
                $this->Command($player, $cfg, $categorys, $command, $sub, $ans);
            }
        });
        $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        $form->setContent($msg->getNested("Messages.cmd_confirm"));
        $form->addButton($msg->getNested("Messages.yes"));
        $form->addButton($msg->getNested("Messages.no"));
        $player->sendForm($form);
    }

    // For Commands
    public function Command(Player $player, $cfg, $categorys, $command, $sub, $ans)
    {
        if ($sub != 1) {
            $items = $cfg[$categorys];
            foreach ($items["Items"] as $cate => $item2) {
                $item1[] = $item2;
            }
            $list = explode(":", $item1[$command]);
        }else if ($sub = 1){
            $items = $cfg[$categorys];
            $items2 = $items["Sub"];
            $items3 = $items2[$ans];
            foreach ($items3["Items"] as $cate => $item2) {
                $item1[] = $item2;
            }
            $list = explode(":", $item1[$command]);
        }
        if (Coin::getInstance()->getCoin($player) >= $list[2]) {
            if ($list[3] == "Console") {
                $cmd = str_replace("{player}", $player->getName(), $list[4]);
                $explode = explode(" ", $cmd);
                if($explode[0] == "setuperm"){
                    if($player->hasPermission($explode[1])){
                        $player->sendMessage("§l§cMua không thành công, bạn đã sở hữu");
                        return;
                    }
                }
                $player->sendMessage("§l§aMua Thành công");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                Coin::getInstance()->reduceCoin($player, (int)$list[2]);
               // EconomyAPI::getInstance()->reduceMoney($player->getName(), $list[2]);
            } elseif ($list[3] == "Player") {
                $cmd = str_replace("{player}", $player->getName(), $list[4]);
                Server::getInstance()->dispatchCommand($player, $cmd);
                Coin::getInstance()->reduceCoin($player, (int)$list[2]);

//                EconomyAPI::getInstance()->reduceMoney($player->getName(), $list[2]);
            }
        } else {
            $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
            $player->sendMessage($msg->getNested("Messages.Not_enough_money_command"));
        }
    }
    // For Commands
    // For Confirm Form (LONG BOI)
    public function Confirmation(Player $player, $cfg, $categorys, $item, $ans, $sub)
    {
        $form = new CustomForm(function (Player $player, $data) use ($cfg, $categorys, $item, $ans, $sub) {

            if ($data === null) {
                $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                if (($this->getConfig()->get("Straight_Back") == true)) {
                    
                } elseif ($this->getConfig()->get("Back_to_Start") == true) {
                    $this->Category($cfg, $player, $msg, $ans);
                }
            } else {
                $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                $money = Coin::getInstance()->getCoin($player);
                $items = $cfg[$categorys];
                $message = $msg->getNested("Messages.Information");
                if ($sub != 1) {
                    foreach ($items["Items"] as $cate => $item2) {
                        $item1[] = $item2;
                    }
                    $list = explode(":", $item1[$item]);
                }else if ($sub = 1){
                    $items = $cfg[$categorys];
                    $items2 = $items["Sub"];
                    $items3 = $items2[$ans];
                    foreach ($items3["Items"] as $cate => $item2) {
                        $item1[] = $item2;
                    }
                    $list = explode(":", $item1[$item]);
                }
                $name = Item::get((int)$list[0], (int)$list[1], 1)->getName();
                $vars = ["{item}" => $name, "{cost}" => $list[4]];
                foreach ($vars as $var => $replacement) {
                    $message = str_replace($var, $replacement, $message);
                }
                if ($this->getConfig()->getNested("Types.Toggle") == true) {
                    if ($this->getConfig()->getNested("Types.Input") == true) {
                        $data1 = (int)$data[2];
                    }
                    if ($this->getConfig()->getNested("Types.Slider") == true) {
                        $data1 = $data[2];
                    }
                    if ($this->getConfig()->getNested("Types.StepSlider") == true) {
                        $numbers = $this->getConfig()->getNested("Slider_Numbers");
                        $data1 = $numbers[$data[4]];
                    }
                }

                if (($this->getConfig()->get("Thanks")) === true) {
                    if ($data1 === null) {
                    }
                } else {
                    if ($data[1] == false) {
                        if ($money >= $list[3] * $data1) {
                            $item = $player->getInventory();
                            if ($list[5] != "Default") {
                                $item->addItem(Item::get(intval($list[0]), intval($list[1]), intval($data1))->setCustomName($list[5]));
                            } elseif ($list[5] == "Default") {
                                $item->addItem(Item::get((int)$list[0], (int)$list[1], $data1));
                            }
                           // EconomyAPI::getInstance()->reduceMoney($player, $list[3] * $data1);
                            Coin::getInstance()->reduceCoin($player, $list[3] * $data1);
                            $message = $msg->getNested("Messages.Paid_for");
                            $vars = ["{amount}" => $data1, "{item}" => $name, "{cost}" => $list[3] * $data1];
                            foreach ($vars as $var => $replacement) {
                                $message = str_replace($var, $replacement, $message);
                            }
                            $player->sendMessage($message);
                        } else {
                            $message = $msg->getNested("Messages.Not_enough_money");
                            $tags = ["{amount}" => $data1, "{item}" => $name, "{cost}" => $list[3] * $data1, "{missing}" => $list[3] * $data1 - $money];
                            foreach ($tags as $tag => $replacement) {
                                $message = str_replace($tag, $replacement, $message);
                            }
                            $player->sendMessage($message);
                        }
                    }


                    if ($data[1] == true) {
                        if ($player->getInventory()->contains(Item::get((int)$list[0], (int)$list[1], $data1)) === true) {
                            $player->getInventory()->removeItem(Item::get((int)$list[0], (int)$list[1], intval($data1)));
                           // EconomyAPI::getInstance()->addMoney($player, $list[4] * $data1);
                            Coin::getInstance()->reduceCoin($player, $list[4] *$data1);
                            $message = $msg->getNested("Messages.Paid");
                            $vars = ["{amount}" => $data1, "{item}" => $name, "{pay}" => $list[4] * $data1];
                            foreach ($vars as $var => $replacement) {
                                $message = str_replace($var, $replacement, $message);
                            }
                            $player->sendMessage($message);
                        } else {
                            $message = $msg->getNested("Messages.Not_enough_items");
                            $tags = ["{amount}" => $data1, "{item}" => $name, "{pay}" => $list[4] * $data1];
                            foreach ($tags as $tag => $replacement) {
                                $message = str_replace($tag, $replacement, $message);
                            }
                            $player->sendMessage($message);
                        }
                    }
                }
            }
        });

        $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        if ($item !== null) {
            $items = $cfg[$categorys];
            $message = $msg->getNested("Messages.Information");
            if ($sub != 1) {
                foreach ($items["Items"] as $cate => $item2) {
                    $item1[] = $item2;
                }
                $list = explode(":", $item1[$item]);
            } else if ($sub = 1) {
                $items = $cfg[$categorys];
                $items2 = $items["Sub"];
                $items3 = $items2[$ans];
                foreach ($items3["Items"] as $cate => $item2) {
                    $item1[] = $item2;
                }
                $list = explode(":", $item1[$item]);
            }
            $name = Item::get((int)$list[0], (int)$list[1], 1)->getName();
            $vars = ["{item}" => $name, "{cost}" => $list[3], "{sell}" => $list[4]];
            foreach ($vars as $var => $replacement) {
                $message = str_replace($var, $replacement, $message);
            }
            $form->setTitle($msg->getNested("Titles.Amount"));
            $form->addLabel($message);
            if ($this->getConfig()->getNested("Types.Toggle") == true) {
                $message2 = $msg->getNested("Messages.BuySell");
                $vars2 = ["{item}" => $name, "{cost}" => $list[3], "{sell}" => $list[4]];
                foreach ($vars2 as $var => $replacement) {
                    $message2 = str_replace($var, $replacement, $message2);
                }
                $form->addToggle($message2);
            }
            if ($this->getConfig()->getNested("Types.Input") == true) {
                $form->addInput($msg->getNested("Messages.Input"));
            }
            if ($this->getConfig()->getNested("Types.Slider") == true) {
                $form->addSlider("Amount", $this->getConfig()->getNested("Types.Slider_Minimum"), $this->getConfig()->getNested("Types.Slider_Maximum"));
            }
            if ($this->getConfig()->getNested("Types.StepSlider") == true) {
                $form->addStepSlider("Amount", $this->getConfig()->getNested("Types.Slider_Numbers"));
            }
            $player->sendForm($form);
        }
    }

}
