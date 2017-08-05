<?php namespace Wing\Library;
use Wing\Subscribe\Tcp;
use Wing\Subscribe\WebSocket;

/**
 * ParseWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:23
 * Email: huangxiaoan@xunlei.com
 */
class ParseWorker extends BaseWorker
{
	private $index;
	private $events_count = 0;
	public function __construct($workers, $index)
	{
		$this->workers = $workers;
		$this->index   = $index;
	}

    protected function scandir($callback)
    {
        $path[] = HOME."/cache/binfile/parse_process_".$this->index.'/*';
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    $callback($item);
                    unlink($item);
                }
            }
        }
    }


    /**
	 * @return int
	 */

	public function start()
	{
		$process_id = pcntl_fork();

		if ($process_id < 0) {
			echo "fork a process fail\r\n";
			exit;
		}

		if ($process_id > 0) {
			return $process_id;
		}

		$process_name = "wing php >> parse process - ".$this->index;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);

		$pdo = new PDO();
		$websocket = new WebSocket();
		$tcp = new Tcp();

		while (1) {
			ob_start();
			try {
				pcntl_signal_dispatch();
                $this->scandir(function($cache_file) use($pdo, $websocket, $tcp){
                    do {

                        if (!$cache_file || !file_exists($cache_file)) {
                            break;
                        }



                        $file = new FileFormat($cache_file, $pdo);

                        $file->parse(function ($database_name, $table_name, $event) use($websocket, $tcp) {
                            $params = [
                                "database_name" => $database_name,
                                "table_name"    => $table_name,
                                "event_data"    => $event,
                            ];
                            //var_dump($params);
                            $websocket->onchange($database_name, $table_name, $event);
                            $tcp->onchange($database_name, $table_name, $event);

                            $this->events_count++;

                            echo get_current_processid(),"处理事件次数：",$this->events_count,"\r\n";
                        });

                        unset($file);
                    } while (0);
                });


			} catch (\Exception $e) {
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();
			ob_end_clean();
			usleep(100000);

			if ($output) {
				echo $output;
			}
			unset($output);

		}

		return 0;
	}
}