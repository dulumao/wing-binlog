<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/23
 * Time: 16:38
 */
define("__APP_DIR__", dirname(__DIR__));
include __DIR__."/../vendor/autoload.php";

//\Seals\Library\Context::instance()->initRedisLocal();
//$report = new \Seals\Library\Report(\Seals\Library\Context::instance()->redis_local);
//
//echo $report->getDayWriteMax("20170323");
//echo $report->getDayReadMax("20170323");
\Seals\Library\Context::instance()->zookeeperInit();

var_dump(\Seals\Library\Zookeeper::getServices());