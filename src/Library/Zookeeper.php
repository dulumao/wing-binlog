<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/12
 * Time: 21:36
 * beta-分布式，配置管理、服务发现，使用redis实现
 */
class Zookeeper
{
    protected $redis;
    protected $session_id;

    const SERVICE_KEY = "wing-binlog-services";
    const NOTIFY_TYPE_REDIS = "redis";
    const NOTIFY_TYPE_HTTP  = "http";
    const NOTIFY_TYPE_MQ    = "rabbitmq";

    public function __construct(RedisInterface $redis)
    {
        $this->redis      = $redis;
        $this->session_id = $this->createSessionId();
    }

    /**
     * create a rand session id
     *
     * @return string
     */
    protected function createSessionId()
    {
        $str1 = md5(rand(0,999999));
        $str2 = md5(rand(0,999999));
        $str3 = md5(rand(0,999999));

        return time()."-".
            substr($str1,rand(0,strlen($str1)-16),16).
            substr($str2,rand(0,strlen($str2)-16),16).
            substr($str3,rand(0,strlen($str3)-16),16);
    }

    /**
     * service report
     */
    public function serviceReport()
    {
        if (!Context::instance()->zookeeper_config["enable"])
            return false;

        if (!$this->redis)
            return false;
        return $this->redis->hset(
            self::SERVICE_KEY.":". Context::instance()->zookeeper_config["group_id"],
            $this->session_id,
            time()
        );
    }

    /**
     * get all services
     *
     * @return array like this
     *  [
     *     session_id => 1489478544
     *  ]
     */
    public static function getServices()
    {
        $services = Context::instance()->redis_zookeeper->keys(self::SERVICE_KEY."*");
        $res = [];
        foreach ($services as $service) {
            $temp = explode(":",$service);
            $key  = str_replace($temp[0].":", "", $service);
            $res[$key] = Context::instance()->redis_zookeeper->hgetall($service);
            unset($temp,$key);
        }
        return $res;
    }

    /**
     * 配置管理，实现配置下发，针对通知的实现
     *
     * @return array
     */
    public function getNotify()
    {
        /**
        "host"     => "localhost",
        "user"     => "admin",
        "password" => "admin",
        "port"     => 5672,
        "vhost"    => "/"
         */
        return [
            "type"     => self::NOTIFY_TYPE_REDIS,
            "host"     => "127.0.0.1",
            "port"     => 6379,
            "password" => null,
            "user"     => null,  //仅针对mq
            "url"      => null   //仅针对http
        ];
    }

    /**
     * app配置下发实现
     */
    public function getAppConfig()
    {
        return [
            "app_id" => "wing-binlog",
            //app_id可以定义不同的名称，用于区分不同的服务器，
            //在分布式多服务器部署的时候，如果遇到库和表的名字都相同即可区分来源

            "memory_limit" => "10240M",
            //最大内存限制

            "log_dir" => __APP_DIR__."/logs",
            //日志目录 默认为当前路径下的logs文件夹 log_dir目录下的文件，
            //在指定--clear参数后 在重启或者停止进程后将全部被删除
            //在设定目录和使用--clear参数时请注意

            "binlog_cache_dir" => __APP_DIR__."/cache",
            //binlog采集中金生成的临时文件目录 binlog_cache_dir目录下的文件，
            //在指定--clear参数后 在重启或者停止进程后将全部被删除
            //在设定目录和使用--clear参数时请注意

            "process_cache_dir" => __APP_DIR__."/process_cache",
            //生成的一些进程控制的缓存文件目录

            "mysqlbinlog_bin"   => "mysqlbinlog",
            //如果mysqlbinlog没有加到环境变量或者无法识别，这里可以写上绝对路径

            "logger"     => \Seals\Logger\Local::class,
            //日志实现，可以自定义 必须继承psr/log标准的日志实现
            //比如需要将日志推送到别的服务器等需求 可以自定义日志的实现
            "log_levels" => [
                \Psr\Log\LogLevel::ALERT,
                \Psr\Log\LogLevel::CRITICAL,
                \Psr\Log\LogLevel::DEBUG,
                \Psr\Log\LogLevel::EMERGENCY,
                \Psr\Log\LogLevel::ERROR,
                \Psr\Log\LogLevel::INFO,
                \Psr\Log\LogLevel::NOTICE,
                \Psr\Log\LogLevel::WARNING
            ],
            //记录那些级别的日志

        ];
    }
}