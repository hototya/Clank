<?php
namespace hototya\item;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\utils\Config;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;

class Clank extends PluginBase implements Listener
{
    private $config;
    private $C;
    private $economy;
    private $fid;
    private $fid2;
    private $fid3;
    private $fid4;
    private $fid5;

    public function onEnable()
    {
        $dir = $this->getDataFolder();
        if (!file_exists($dir)) {
            mkdir($dir, 0744, true);
        }
        $this->config = new Config($dir . "clank.yml", Config::YAML, [
            "money" => 200,
            "item" => [
                0 => "1:0:10",
                1 => "5:0:32"
            ]
        ]);
        $this->C = $this->config->getAll();
        $this->fid = mt_rand(0, 99999999);
        $this->fid2 = mt_rand(0, 99999999);
        $this->fid3 = mt_rand(0, 99999999);
        $this->fid4 = mt_rand(0, 99999999);
        $this->fid5 = mt_rand(0, 99999999);
        while (in_array($this->fid, [$this->fid2, $this->fid3, $this->fid4, $this->fid5])) {
            $this->fid2 = mt_rand(0, 99999999);
            $this->fid3 = mt_rand(0, 99999999);
            $this->fid4 = mt_rand(0, 99999999);
            $this->fid5 = mt_rand(0, 99999999);
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($economy !== null) {
            $this->economy = $economy;
        } else {
            $this->getServer()->getPluginManager()->disablePlugin($this);
            $this->getLogger()->warning("EconomyAPIが見つからなかった為、Clankが起動できません。");
        }
    }

    public function onDisable()
    {
        $this->config->setAll($this->C);
        $this->config->save();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($label) {
            case "clank":
                if ($sender instanceof Player) {
                    if ($this->C["money"] <= $this->economy->myMoney($sender)) {
                        $items = $this->C["item"];
                        $count = count($items);
                        if ($count === 0) {
                            $sender->sendMessage("エラーによりガチャはキャンセルされました。");
                            return false;
                        }
                        $item = explode(":", $items[mt_rand(0, count($items) - 1)]);
                        $resultItem = Item::get($item[0], $item[1], $item[2]);
                        if ($sender->getInventory()->canAddItem($resultItem)) {
                            $this->economy->reduceMoney($sender, $this->C["money"]);
                            $sender->setImmobile(true);
                            $this->getServer()->getScheduler()->scheduleRepeatingTask(new ItemAnime($sender, $resultItem), 2);
                        } else {
                            $sender->sendMessage("アイテムが追加できない為、ガチャはキャンセルされました。");
                        }
                    } else {
                        $sender->sendMessage("お金が足りない為、ガチャはキャンセルされました。");
                    }
                } else {
                    $sender->sendMessage("サーバー内で使用して下さい。");
                }
                break;
            case "aclank":
                if ($sender instanceof Player) {
                    $data = [
                        "type" => "form",
                        "title" => "Clank >> メインメニュー",
                        "content" => "行う操作を選んでください",
                        "buttons" => [
                            ["text" => "アイテム追加"],
                            ["text" => "アイテム削除"],
                            ["text" => "金額変更"],
                            ["text" => "排出アイテムリスト表示"]
                        ]
                    ];
                    $this->createWindow($sender, $data, $this->fid);
                } else {
                    $sender->sendMessage("サーバー内で使用して下さい。");
                }
                break;
            default:
        }
        return true;
    }

    public function onPacketReceive(DataPacketReceiveEvent $event)
    {
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if ($pk instanceof ModalFormResponsePacket) {
            $id = $pk->formId;
            $fData = $pk->formData;
            switch ($id) {
                case $this->fid:
                    switch ($fData) {
                        case 0:
                            $data = [
                                "type" => "custom_form",
                                "title" => "Clank >> アイテム追加",
                                "content" => [
                                    [
                                        "type" => "label",
                                        "text" => "追加したいアイテムの各データをそれぞれ入力して下さい。"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "ID",
                                        "placeholder" => "ID",
                                        "default" => "1"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "データ値",
                                        "placeholder" => "データ値",
                                        "default" => "0"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "個数",
                                        "placeholder" => "個数",
                                        "default" => "1"
                                    ]
                                ]
                            ];
                            $this->createWindow($player, $data, $this->fid2);
                            break;
                        case 1:
                            $data = [
                                "type" => "custom_form",
                                "title" => "Clank >> アイテム削除",
                                "content" => [
                                    [
                                        "type" => "label",
                                        "text" => "削除したいアイテムの各データをそれぞれ入力して下さい。"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "ID",
                                        "placeholder" => "ID",
                                        "default" => "1"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "データ値",
                                        "placeholder" => "データ値",
                                        "default" => "0"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "個数",
                                        "placeholder" => "個数",
                                        "default" => "1"
                                    ]
                                ]
                            ];
                            $this->createWindow($player, $data, $this->fid3);
                            break;
                        case 2:
                            $data = [
                                "type" => "custom_form",
                                "title" => "Clank >> 金額変更",
                                "content" => [
                                    [
                                        "type" => "label",
                                        "text" => "変更したい金額の値を入れて下さい。"
                                    ],
                                    [
                                        "type" => "input",
                                        "text" => "金額",
                                        "placeholder" => "金額",
                                        "default" => "200"
                                    ],
                                ]
                            ];
                            $this->createWindow($player, $data, $this->fid4);
                            break;
                        case 3:
                            $items = $this->C["item"];
                            $text = "";
                            foreach ($items as $item) {
                                $text .= $item . "\n";
                            }
                            /*
                            何故かエラー出るので後回しです。
                            $data = [
                                "type" => "label",
                                "text" => $text
                            ];
                            $this->createWindow($player, $data, $this->fid5);
                            */
                            $player->sendMessage("§e=== §fClank List §e===");
                            $player->sendMessage("ID:データ値:個数");
                            $player->sendMessage("§f" . $text . "§e================");
                            break;
                        default:
                    }
                    break;
                case $this->fid2:
                    $array = json_decode($fData);
                    if ($array === null) return;
                    unset($array[0]);
                    foreach ($array as $element) {
                        if (!is_numeric($element)) {
                            $player->sendMessage("§c正しくない型が検出されました。整数を入力して下さい。");
                            return;
                        }
                    }
                    $text = (int) $array[1] . ":" . (int) $array[2] . ":" . (int) $array[3];
                    $this->C["item"][] = $text;
                    $player->sendMessage("追加しました。");
                    break;
                case $this->fid3:
                    $array = json_decode($fData);
                    if ($array === null) return;
                    unset($array[0]);
                    foreach ($array as $element) {
                        if (!is_numeric($element)) {
                            $player->sendMessage("§c正しくない型が検出されました。整数を入力して下さい。");
                            return;
                        }
                    }
                    $text = (int) $array[1] . ":" . (int) $array[2] . ":" . (int) $array[3];
                    if (in_array($text, $this->C["item"])) {
                        $result = array_diff($this->C["item"], [$text]);
                        $result = array_values($result);
                        $this->C["item"] = $result;
                        $player->sendMessage("削除しました。");
                    } else {
                        $player->sendMessage("そのアイテムはガチャリストに含まれてません。");
                    }
                    break;
                case $this->fid4:
                    $array = json_decode($fData);
                    if ($array === null) return;
                    if (is_numeric($array[1])) {
                        $this->C["money"] = (int) $array[1];
                        $player->sendMessage("変更が完了しました。");
                    } else {
                        $player->sendMessage("§c正しくない型が検出されました。整数を入力して下さい。");
                    }
                    break;
                default:
            }
        }
    }

    private function createWindow(Player $player, array $data, int $id)
    {
        $pk = new ModalFormRequestPacket();
        $pk->formId = $id;
        $pk->formData = json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE);
        $player->dataPacket($pk);
    }
}

class ItemAnime extends Task
{
    private $player;
    private $pk;
    private $count = 0;

    public function __construct(Player $player, Item $resultItem)
    {
        $dir = $player->getDirectionVector();
        $pk = new AddItemEntityPacket();
        $pk->entityUniqueId = mt_rand(1000000, 9999999);
        $pk->entityRuntimeId = $pk->entityUniqueId;
        $pk->item = $resultItem;
        $pk->position = new Vector3($player->x + $dir->x, $player->y + $player->getEyeHeight() + $dir->y, $player->z + $dir->z);
        $pk->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE]];
        $this->player = $player;
        $this->pk = $pk;
    }

    public function onRun(int $currentTick)
    {
        $id = mt_rand(0, 500);
        while (!ItemFactory::isRegistered($id)) {
            $id = mt_rand(0, 500);
        }
        $pk = clone $this->pk;
        $pk->item = Item::get($id, 0, 1);
        if (40 < $this->count) {
            $pk->item = $this->pk->item;
            Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
        }
        $this->player->dataPacket($pk);
        ++$this->count;
    }

    public function onCancel()
    {
        Server::getInstance()->getScheduler()->scheduleDelayedTask(new Result($this->player, $this->pk), 20 * 3);
    }
}

class Result extends Task
{
    private $player;
    private $pk;

    public function __construct(Player $player, AddItemEntityPacket $pk)
    {
        $this->player = $player;
        $this->pk = $pk;
    }

    public function onRun(int $currentTick)
    {
        $pk = new RemoveEntityPacket();
        $pk->entityUniqueId = $this->pk->entityUniqueId;
        $this->player->dataPacket($pk);
        $spk = new LevelEventPacket();
        $spk->evid = LevelEventPacket::EVENT_SOUND_ORB;
        $spk->position = $this->pk->position;
        $spk->data = 0;
        $this->player->dataPacket($spk);
        $this->player->getInventory()->addItem($this->pk->item);
        $this->player->sendTip("§l§o§e" . $this->pk->item->getName() . "を手に入れた！");
        $this->player->setImmobile(false);
    }
}