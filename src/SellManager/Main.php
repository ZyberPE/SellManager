<?php

namespace SellManager;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\item\StringToItemParser;
use onebone\economyapi\EconomyAPI;

class Main extends PluginBase{

    public function onEnable(): void{
        $this->saveDefaultConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{

        if(!$sender instanceof Player){
            return true;
        }

        $cfg = $this->getConfig();
        $messages = $cfg->get("messages");
        $prices = $cfg->get("sell-prices");
        $prefix = $messages["prefix"];

        $multiplier = $sender->hasPermission("sell.rank") ? 2 : 1;

        switch($command->getName()){

            case "sellhand":

                if(!$sender->hasPermission("sellmanager.sellhand")){
                    $sender->sendMessage($prefix . $messages["no-permission"]);
                    return true;
                }

                $item = $sender->getInventory()->getItemInHand();

                if($item->isNull()){
                    $sender->sendMessage($prefix . $messages["no-item-hand"]);
                    return true;
                }

                foreach($prices as $block => $price){

                    $parsed = StringToItemParser::getInstance()->parse($block);

                    if($parsed !== null && $parsed->getTypeId() === $item->getTypeId()){

                        $amount = $item->getCount();
                        $total = $price * $amount * $multiplier;

                        EconomyAPI::getInstance()->addMoney($sender, $total);

                        $sender->getInventory()->setItemInHand($item->setCount(0));

                        $msg = str_replace(
                            ["{amount}","{item}","{price}"],
                            [$amount,$block,$total],
                            $messages["sold-hand"]
                        );

                        $sender->sendMessage($prefix . $msg);
                        return true;
                    }
                }

                $sender->sendMessage($prefix . $messages["not-sellable"]);
                return true;


            case "sellall":

                if(!$sender->hasPermission("sellmanager.sellall")){
                    $sender->sendMessage($prefix . $messages["no-permission"]);
                    return true;
                }

                $inventory = $sender->getInventory();
                $totalMoney = 0;
                $totalBlocks = 0;

                foreach($inventory->getContents() as $slot => $item){

                    foreach($prices as $block => $price){

                        $parsed = StringToItemParser::getInstance()->parse($block);

                        if($parsed !== null && $parsed->getTypeId() === $item->getTypeId()){

                            $amount = $item->getCount();
                            $totalBlocks += $amount;

                            $totalMoney += ($price * $amount) * $multiplier;

                            $inventory->setItem($slot, $item->setCount(0));
                        }
                    }
                }

                if($totalMoney > 0){

                    EconomyAPI::getInstance()->addMoney($sender, $totalMoney);

                    $msg = str_replace(
                        ["{blocks}","{price}"],
                        [$totalBlocks,$totalMoney],
                        $messages["sold-all"]
                    );

                    $sender->sendMessage($prefix . $msg);

                }else{
                    $sender->sendMessage($prefix . $messages["not-sellable"]);
                }

                return true;


            case "blockprice":

                if(!$sender->hasPermission("sellmanager.blockprice")){
                    $sender->sendMessage($prefix . $messages["no-permission"]);
                    return true;
                }

                $item = $sender->getInventory()->getItemInHand();

                if($item->isNull()){
                    $sender->sendMessage($prefix . $messages["no-item-hand"]);
                    return true;
                }

                foreach($prices as $block => $price){

                    $parsed = StringToItemParser::getInstance()->parse($block);

                    if($parsed !== null && $parsed->getTypeId() === $item->getTypeId()){

                        $displayPrice = $price * $multiplier;

                        $sender->sendMessage($prefix . "§e".$block." §7sells for §a$".$displayPrice." §7each.");
                        return true;
                    }
                }

                $sender->sendMessage($prefix . $messages["not-sellable"]);
                return true;
        }

        return false;
    }
}
