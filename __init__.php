<?php
    /**
    * OneBotForPHP
    * 基于OneBot协议的QQ机器人开发框架
    * @author BudingXiaoCai <budingxiaocai@outlook.com>
    * @version 1.0.4
    * @license MIT
    */

    ini_set('display_errors','on');

    require_once __DIR__ . '/app/Log.php';
    Log::$LogDir = getcwd() . '/logs';

    use Workerman\Worker;
    use \Workerman\WebServer;
    use \GatewayWorker\Gateway;
    use \GatewayWorker\BusinessWorker;
    use \Workerman\Autoloader;
    use \GatewayWorker\Register;
    
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

    $port = 6880;
    $enableFileMonitor = true;
    
    if (isset($argv[1]) && ($argv[1] == '-h' || $argv[1] == '--help')) {
        echo "命令: php ".$argv[0]." [options]\n";
        echo "选项:\n";
        echo "  -p, --port <number>         设置服务器运行的端口号，默认为6880\n";
        echo "  -d, --run-dir <path>        设置服务器运行的目录，默认为当前目录\n";
        echo "  --disableFileMonitor <1|0>  禁用文件监控功能，默认为启用\n";
        echo "  -h, --help                  显示帮助信息\n";
        exit(0);
    }

    if (($argc + 1) % 2 == 1) {
        Log::log('提供给程序的参数有误，请检查参数是否正确',Log::ERROR);
        exit(1);
    }

    array_shift($argv);
    
    $argv = array_chunk($argv,2);
    foreach ($argv as $arg) {
        switch ($arg[0]) {
            case '-p':
            case '--port':
                $port = $arg[1];
                break;
            case '-d':
            case '--run-dir':
                if (is_dir($arg[1])) {
                    chdir($arg[1]);
                } else {
                    Log::log('提供给程序的运行目录不存在或无效，已自动忽略该参数',Log::WARNING);
                }
                break;
            case '--disableFileMonitor':
                if ($arg[1] == '1') {
                    $enableFileMonitor = false;
                    Log::log('禁用了文件监控功能');
                }
        }
    }

    Log::log('程序将在'.getcwd().'中运行');
    Log::log("服务器将运行在端口:$port");
    if (!file_exists(getcwd() . '/app/QQEvents.php')) {
        mkdir(getcwd() . '/app',0777,true);
        copy(__DIR__ . '/app/QQEvents.php.sample',getcwd() . '/app/QQEvents.php');
        Log::log('<QQEvents.php>不存在于<app>目录中，已自动创建示例文件，请根据实际需要修改文件',Log::WARNING);
        exit(1);
    }
    
    if (strtoupper(substr(PHP_OS,0,3)) === 'WIN') {
        $runServer = function (string $tmpPath,string|int $port) {
            global $enableFileMonitor;
            file_put_contents($tmpPath . '\\windows\\data.json',json_encode([
                "port" => $port,
                "appPath" => getcwd(),
                "enableFileMonitor" => $enableFileMonitor
            ],JSON_UNESCAPED_UNICODE));
            chdir($tmpPath);
            Log::log("准备运行服务器......");
            system('"'.PHP_BINARY."\" \"$tmpPath\\windows\\startGateway.php\" \"$tmpPath\\windows\\startBusinessworker.php\" \"$tmpPath\\windows\\startRegister.php\"");
        };

        if (file_exists(getcwd() . '\\app\\windowsAppPath.json')) {
            $tmpPath = json_decode(file_get_contents(getcwd() . '\\app\\windowsAppPath.json'),true)['path'];
            $tmpFileVersion = json_decode(file_get_contents($tmpPath . '\\version.json'),true)['build_version'];
            $nowFileVersion = json_decode(file_get_contents(__DIR__ . '\\version.json'),true)['build_version'];
            if (is_dir($tmpPath) && $tmpFileVersion === $nowFileVersion) {
                $runServer($tmpPath,$port);
                exit(0);
            } else {
                system("rd /s /q \"$tmpPath\"");
            }
        }

        $tmpPath = getenv('TEMP') . '\\' . md5(uniqid('OneBotForPHP'));
        mkdir($tmpPath,0777,true);
        Log::log("正在复制运行环境至$tmpPath");
        foreach (new RecursiveIteratorIterator(new Phar(__DIR__)) as $file) {
            // 复制文件到目标目录
            $relativePath = dirname(substr($file->getPathname(),strlen(__DIR__)));
            if (!is_dir($tmpPath . '\\' . $relativePath)) {
                mkdir($tmpPath . '\\' . $relativePath,0777,true);
            } 
            copy($file->getPathname(),$tmpPath . "\\" . $relativePath . "\\" . $file->getFilename());
        }
        
        file_put_contents(getcwd() . '\\app\\windowsAppPath.json',json_encode([
            "path" => $tmpPath
        ],JSON_UNESCAPED_UNICODE));
        $runServer($tmpPath,$port);
        
        exit(0);
    } else {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/app/Events.php';

        $worker = new BusinessWorker();
        $worker->name = 'MainBusinessWorker';
        $worker->count = 4;
        $worker->registerAddress = '127.0.0.1:1238';
        $register = new Register('text://127.0.0.1:1238');
        $gateway = new Gateway("websocket://0.0.0.0:$port");
        $gateway->name = 'MainGateway';
        $gateway->count = 2;
        $gateway->lanIp = '127.0.0.1';
        $gateway->startPort = 2900;
        $gateway->registerAddress = '127.0.0.1:1238';
        // 运行所有服务
        Worker::runAll();
    }