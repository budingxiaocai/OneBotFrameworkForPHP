<?php
/**
 * OneBotForPHP
 * 基于OneBot协议的QQ机器人开发框架
 * @author BudingXiaoCai <budingxiaocai@outlook.com>
 * @version 1.0.4
 * @license MIT
 */

use GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;

require_once __DIR__ . '/Log.php';

set_exception_handler(function (Throwable $exception) {
    Log::log($exception->getMessage(),Log::WARNING);
    Log::log($exception->getTraceAsString(),Log::WARNING);
});

set_error_handler(function (
    int $errno,
    string $errstr,
    string $errfile = '',
    int $errline = 0
) {
    Log::log($errstr,Log::ERROR);
    Log::log("错误出现在文件".$errfile."的第".$errline."行",Log::ERROR);
});

if (strtoupper(substr(PHP_OS,0,3)) === 'WIN') {
    $enableFileMonitor = true;
    $configData = json_decode(file_get_contents(__DIR__ . '\\..\\windows\\data.json'),true);
    
    if (isset($configData['appPath']) && is_string($configData['appPath'])) {
        chdir($configData['appPath']);
        Log::$LogDir = getcwd() . '/logs';
    }
    
    if (isset($configData['enableFileMonitor']) && is_bool($configData['enableFileMonitor'])) {
        $enableFileMonitor = $configData['enableFileMonitor'];
    }
}

require_once getcwd() . "/app/QQEvents.php";

class Events {
    public static $pendingActions = [];

    private static $fileLastModifiedTime = 0;

    private static $QEClassName = "QQEvents";

    public const string VERSION = "1.0.4";

    public static function fileMonitor() {
        clearstatcache();
        if (!file_exists(getcwd() . "/app/QQEvents.php")) {
            Log::log("文件 ".getcwd() . "/app/QQEvents.php"." 被删除",Log::WARNING);
            return;
        }

        $fileCurrentModifiedTime = filemtime(getcwd() . "/app/QQEvents.php");

        // 检查文件是否更新
        if ($fileCurrentModifiedTime > Self::$fileLastModifiedTime) {
            $code = file_get_contents(getcwd() . "/app/QQEvents.php");
            $QEClassName = "QQEvents_" . md5(uniqid((string)rand(),true));
            $code = str_replace('class '.Self::$QEClassName,"class $QEClassName",$code);
            $code = str_replace('<?php','',$code);
            Self::$QEClassName = $QEClassName;

            eval($code);
            Self::$fileLastModifiedTime = $fileCurrentModifiedTime;
            Log::log("文件 ".getcwd() . "/app/QQEvents.php"." 更新，已重新加载文件");
            Log::log("新类名(ClassName):$QEClassName");
        }

        return;
    }

    public static function onWorkerStart($worker) {
        Log::log("QQBot 消息处理服务器 已启动!");
        Log::log("欢迎使用！版本号:".Self::VERSION);
        Self::$fileLastModifiedTime = time();
    }

    public static function onMessage($client_id,$message) {
        Log::log("接收到新事件");
        Log::log($message);

        global $enableFileMonitor;
        if ($enableFileMonitor) {
            Self::fileMonitor();
        }
        
        $message = json_decode($message,true);
        if (isset($message["post_type"])) {
            switch ($message["post_type"]) {
                case 'message' :
                case 'message_sent' :
                    Log::log("事件类型：消息");
                    if (method_exists(Self::$QEClassName,"onMessage")) {
                        Log::log("回调存在，处理数据");
                        $action = new QQAction();
                        $action->clientId = $client_id;
                        $data = [
                            $message["post_type"],
                            $message["message"],
                            $message["raw_message"],
                            $message["message_type"],
                            $message["sub_type"],
                            $message["message_id"],
                            $message["user_id"],
                            $message,
                            $message["sender"],
                            $action
                        ];
    
                        if (isset($message["group_id"])) {
                            $data[] = $message["group_id"];
                        }
    
                        call_user_func_array([Self::$QEClassName,"onMessage"],$data);
                    } else {
                        Log::log("消息（onMessage）回调不存在",LOG::WARNING);
                    }
                    break;
                case 'request' :
                    Log::log("事件类型：请求");
                    if (method_exists(Self::$QEClassName,"onRequest")) {
                        Log::log("回调存在，处理数据");
                        $action = new QQAction();
                        $action->clientId = $client_id;
                        $data = [
                            $message["post_type"],
                            $message["request_type"],
                            $message["user_id"],
                            $message["comment"],
                            $message["flag"],
                            $message,
                            $action
                        ];

                        call_user_func_array([Self::$QEClassName,"onRequest"],$data);
                    } else {
                        Log::log("请求（onRequest）回调不存在",LOG::WARNING);
                    }
                    break;
                case 'notice' :
                    Log::log("事件类型：通知");
                    if (method_exists(Self::$QEClassName,"onNotice")) {
                        Log::log("回调存在，处理数据");
                        $action = new QQAction();
                        $action->clientId = $client_id;
                        $data = [
                            $message["post_type"],
                            $message["notice_type"],
                            $message,
                            $action
                        ];
                        
                        call_user_func_array([Self::$QEClassName,"onNotice"],$data);
                    } else {
                        Log::log("通知（onNotice）回调不存在",LOG::WARNING);
                    }
                    break;
                case 'meta_event' :
                    Log::log("事件类型：元事件");
                    // 心跳请求，防止连接被强行断开
                    Gateway::sendToClient($client_id,json_encode([
                        "action" => "get_status",
                        "params" => [],
                        "echo" => "-1"
                    ],JSON_UNESCAPED_UNICODE));
                    break;
                default :
                    break;
            }
        } else if (isset($message["echo"])) {
            // 处理回调
            Log::log("事件类型：回调");
            if (isset(Self::$pendingActions[$message["echo"]])) {
                Log::log("Callback有效，执行".$message["echo"]);
                $callback = Self::$pendingActions[$message["echo"]];
                $callbackData = [$message["status"],$message["retcode"]];
                if ($message["status"] == "failed") {
                    array_push($callbackData,$message["msg"].$message["wording"]);
                }
                unset(Self::$pendingActions[$message["echo"]]);
                call_user_func_array($callback,$callbackData);
            } else {
                if ($message["echo"] != "-1") {
                    Log::log("队列中没有关于".$message["echo"]."的回调数据",LOG::WARNING);
                }
            }
        }
        return;
    }
}

/**
 * OneBot操作类
 */
class QQAction {
    public $clientId;

    /**
     * 向OneBot服务器发送数据
     * @param string $method 方法名
     * @param array $args 参数
     * @return bool
     */
    public function __call(string $method,array $args = []) {
        $callbackId = "-1";

        if (count($args) % 2 == 1) {
            return false;
        }

        $args = array_chunk($args,2);
        $data = [];

        foreach ($args as $arg) {
            if (!is_string($arg[0])) {
                continue;
            }

            if (substr($arg[0],-1) != ":") {
                continue;
            }

            $methodStr = substr($arg[0],0,-1);
            if ($methodStr == "callback" && is_callable($arg[1])) {
                $callback = $arg[1];
                $callbackId = uniqid();
                // 添加进Actions队列
                Events::$pendingActions[$callbackId] = $callback;
                continue;
            }

            $data[$methodStr] = $arg[1];
        }

        $dataStr = json_encode([
            "action" => $method,
            "params" => $data,
            "echo" => $callbackId
        ],JSON_UNESCAPED_UNICODE);

        Log::log("向OneBot发送数据");
        Log::log($dataStr);
        Gateway::sendToClient($this->clientId,$dataStr);

        return true;
    }

    /**
     * SendData方法 等同于 __call 方法
     * @param string $method 方法名
     * @param array $args 参数
     * @return bool
     */
    public function sendData(string $method,...$args) {
        return $this->__call($method,$args);
    }
}

