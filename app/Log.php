<?php
    /**
    * OneBotForPHP
    * 基于OneBot协议的QQ机器人开发框架
    * @author BudingXiaoCai <budingxiaocai@outlook.com>
    * @version 1.0.4
    * @license MIT
    */

    /**
    * 日志类
    */
    class Log {
        public const INFO = 0;
        public const WARNING = 1;
        public const ERROR = 2;
        private static $LogName = null;
        public static $LogDir;

        /**
        * 输出日志
        * @param string $message 日志内容
        * @param int $level 日志类别
        * @return void
        */
        public static function log(string $message,int $level = Self::INFO) {
            $levelStr = match ($level) {
                Self::INFO => "Info",
                Self::WARNING => "Warning",
                Self::ERROR => "Error",
                default => "Unknown",
            };

            if (Self::$LogName === null) {
                Self::$LogName = "log-".date("Y-m-d-H-i-s").".log";
            }

            $logData = "[".date("Y-m-d H:i:s")."][$levelStr] $message".PHP_EOL;
            echo $logData;

            if (!is_dir(Self::$LogDir)) {
                mkdir(Self::$LogDir,0777,true);
            }

            if (!file_exists(Self::$LogDir.'/'.Self::$LogName)) {
                file_put_contents(Self::$LogDir.'/'.Self::$LogName,$logData);
            } else {
                file_put_contents(Self::$LogDir.'/'.Self::$LogName,$logData,FILE_APPEND);
            }
            
            return;
        }
    }