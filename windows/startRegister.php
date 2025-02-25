<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \GatewayWorker\Register;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Events.php';

$register = new Register('text://127.0.0.1:1238');

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}

