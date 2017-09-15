<?php namespace Wing\Bin;
use Wing\Bin\Constant\CommandType;
use Wing\Bin\Constant\Cursor;
use Wing\Bin\Constant\FieldType;

/**
 * Mysql.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:29
 * Email: huangxiaoan@xunlei.com
 */
class Mysql
{
	public static function query($sql)
    {
		$packet = Packet::query($sql);
		Net::send($packet);

		$res   = Net::readPacket();
		$fbyte = ord($res[0]);

		//这里可能是三种类型的报文 Result set、Field和Row Data
        //以下解析 Result set
		if ($fbyte >= Packet::RESULT_SET_HEAD[0] && $fbyte <= Packet::RESULT_SET_HEAD[1]) {
            //列数量
		    //$column_num = $fbyte;

		    /**
            n	目录名称（Length Coded String）
            n	数据库名称（Length Coded String）
            n	数据表名称（Length Coded String）
            n	数据表原始名称（Length Coded String）
            n	列（字段）名称（Length Coded String）
            4	列（字段）原始名称（Length Coded String）
            1	填充值
            2	字符编码
            4	列（字段）长度
            1	列（字段）类型
            2	列（字段）标志
            1	整型值精度
            2	填充值（0x00）
            n	默认值（Length Coded String）

            目录名称：在4.1及之后的版本中，该字段值为"def"。
            数据库名称：数据库名称标识。
            数据表名称：数据表的别名（AS之后的名称）。
            数据表原始名称：数据表的原始名称（AS之前的名称）。
            列（字段）名称：列（字段）的别名（AS之后的名称）。
            列（字段）原始名称：列（字段）的原始名称（AS之前的名称）。
            字符编码：列（字段）的字符编码值。
            列（字段）长度：列（字段）的长度值，真实长度可能小于该值，例如VARCHAR(2)类型的字段实际只能存储1个字符。
            列（字段）类型：列（字段）的类型值，取值范围如下（参考源代码/include/mysql_com.h头文件中的enum_field_type枚举类型定义
             */

		    //列信息
		    $columns = [];
		    //$times = 0;
            //一直读取直到遇到结束报文
            while (1) {
                $res = Net::readPacket();

				if (ord($res[0]) == Packet::EOF_HEAD) break;
				//var_dump($res);

                $packet = new Packet($res);
//                $column['dir_name']         = $packet->next();
//                $column['database_name']    = $packet->next();
//                $column['table_name']       = $packet->next();
//                $column['old_table_name']   = $packet->next();
//                $column['column_name']      = $columns[] = $packet->next();

                //def 移动游标至下一个
                $packet->next();
                //数据库名称
                $packet->next();
                //数据表名称
                $packet->next();
                //原数据表名称
                $packet->next();
                //列名称 这个才是我们要的
                $columns[] = $packet->next();
                unset($packet);
//
                //
//                var_dump($column);
//                exit;

                /*$len = ord($res[0]);
               // echo $len,"\r\n";
                $start = 0;
                $start++;
                //目录名称
				$column['dir_name'] = substr($res."", $start, $len);
                $start += $len;
                $len = ord($res[$start]);
                $start++;
//                echo $column['dir_name'],"\r\n";
//                exit;
               // echo $len,"\r\n";

//                var_dump(substr($res, $start-1));
//                var_dump(substr($res, $start));

                //数据库名称
				$column['database_name'] = substr($res, $start, $len);
                $start += $len;
                $len = ord($res[$start]);
                $start++;
               // echo $database_name,"\r\n";

				$column['table_name'] = substr($res, $start, $len);
                $start += $len;
                $len = ord($res[$start]);
                $start++;
               // echo $table_name,"\r\n";

				$column['old_table_name'] = substr($res, $start, $len);
                $start += $len;
                $len = ord($res[$start]);
                $start++;
                //echo $old_table_name,"\r\n";

                //echo $len,"\r\n";
				$column['column_name'] = $columns[] = substr($res, $start, $len);
               // echo $column1_name,"\r\n";

                $start += $len;
                $len = ord($res[$start]);
                $start++;
				$column['old_column_name']  = substr($res, $start, $len);
                //echo $old_column1_name, "\r\n";

             //   if ($len<4)$len = 4;
                $start += $len;
                $start++;

               // echo "character_set\r\n";
               // for ($i = $start; $i<strlen($res);$i++)
                //    echo ord($res[$i])."-";

               // echo "\r\n\r\n";
				$column['character_set'] = ord($res[$start]);//.$res[$start+1];
                $start+=2;

                //echo $chart_set,"\r\n";
               // for ($i = $start; $i<strlen($res);$i++)
                //    echo ord($res[$i])."-";
               // echo "\r\n";

				//这里的解析不确定是否正确
				$column['column_len'] = unpack("I",$res[$start].$res[$start+1].$res[$start+2].$res[$start+3])[1];

//				$data = unpack('C4', $res[$start].$res[$start+1].$res[$start+2].$res[$start+3]);
//				$column['column_len'] = ($data[1] << 24)|($data[2] << 16) | ($data[3] << 8) | $data[4];

                $start+=4;
               // echo $column_len,"\r\n";
				$column['column_type'] = ord($res[$start]);
                $start++;
               // echo $column_type,"\r\n";

				//索引什么的
				$column['column_flag'] = ord($res[$start]).ord($res[$start+1]);
                $start+=2;
               // echo $column_flag,"\r\n";

                $start+=2;//填充值0x00

                $len = ord($res[$start]);
                $start++;
               // echo $len;
				$column['default_value'] = substr($res, $start, $len);
                //echo $default_value,"\r\n";


//				for ($i = 0; $i<strlen($res);$i++)
//					echo ord($res[$i])."-";
//
//				echo "\r\n\r\n";
//				var_dump($column);
				//$columns[] = $column;
                //$columns .= $res;
                $times++;
               // exit;*/
            }
            //var_dump($columns);
            //exit;

            //行信息
            $rows = [];
            //一直读取直到遇到结束报文
            while (1) {
                $res = Net::readPacket();
                if (ord($res[0]) == Packet::EOF_HEAD) break;
                $index = 0;
                $row = [];

                /**
				0-250	0	第一个字节值即为数据的真实长度
				251		0	空数据，数据的真实长度为零
				252		2	后续额外2个字节标识了数据的真实长度
				253		3	后续额外3个字节标识了数据的真实长度
				254		8	后续额外8个字节标识了数据的真实长度
				 */

                $start = 0;
                while ($start <strlen($res)) {
					$len = ord($res[$start]);

					/**
					case
					when first_byte <= 250
					first_byte
					when first_byte == 251
					nil
					when first_byte == 252
					read_uint16
					when first_byte == 253
					read_uint24
					when first_byte == 254
					read_uint64
					when first_byte == 255
					 */
					if ($len == 251) {
						$row[$columns[$index++]] = null;
						$start++;
						continue;
					}

					if ($len == 252) {
						//$len = unpack("v",$res[$start+1].$res[$start+2]);

						$len = unpack("v", $res[$start+1].$res[$start+2])[1];
						$start+=2;
					} else if ($len == 253) {
						$data = unpack("C3", $res[$start+1].$res[$start+2].$res[$start+3]);//[1];


      					$len = $data[1] + ($data[2] << 8) + ($data[3] << 16);
//						var_dump($len);
//						exit;
						$start+=3;
					}
				else if ($len == 254) {
					$len = unpack("Q",
						$res[$start+1].
						$res[$start+2].
						$res[$start+3].
						$res[$start+4].
						$res[$start+5].
						$res[$start+6].
						$res[$start+7].
						$res[$start+8]
					)[1];
					$start+=8;
				}
					$start++;


					$row[$columns[$index++]] = substr($res, $start, $len);
					$start += $len;
				}
				$rows[] = $row;
            }
            var_dump($rows);

            //这里还需要对报文进行解析
//            var_dump($columns);
//            var_dump($rows);
            return $rows;
        }

        else if ($fbyte == Packet::OK_PACK_HEAD) {
		    //1byte 0x00 OK报文 恒定为0x00
            //1-9bytes 受影响的行数
            //1-9bytes 索引id，执行多个insert时，默认是第一个
            //2bytes 服务器状态
            //2bytes 告警计数
            //nbytes 服务器消息，无结束符号，直接读取到尾部

			/**
			1	OK报文，值恒为0x00
			1-9	受影响行数（Length Coded Binary）
			1-9	索引ID值（Length Coded Binary）
			2	服务器状态
			2	告警计数
			n	服务器消息（字符串到达消息尾部时结束，无结束符，可选）
			受影响行数：当执行INSERT/UPDATE/DELETE语句时所影响的数据行数。

			索引ID值：该值为AUTO_INCREMENT索引字段生成，如果没有索引字段，则为0x00。注意：当INSERT插入语句为多行数据时，该索引ID值为第一个插入的数据行索引值，而非最后一个。

			服务器状态：客户端可以通过该值检查命令是否在事务处理中。

			告警计数：告警发生的次数。

			服务器消息：服务器返回给客户端的消息，一般为简单的描述性字符串，可选字段。
			 */

            //消息解析
			for ($i=0;$i<strlen($res);$i++) {
				echo ord($res[$i]),"-";
			}
			echo "\r\n";
			$start = 1;
			$len = ord($res[$start]);
			$start++;
			$last_insert_id = ord($res[$start]);//substr($res, $start, $len);
			if ($last_insert_id <= 0) {
				return $len;
			}
//			var_dump($rows_affected);
//			$start+=$len;
//
//			$len = ord($res[$start]);
//			$start++;
//			$rows_affected = substr($res, $start, $len);
//			var_dump($rows_affected);
//			echo "insert";
			return $last_insert_id;
        }

        else if ($fbyte == Packet::ERR_PACK_HEAD) {
		    //1byte Error报文 恒定为0xff
            //2bytes 错误编号，小字节序
            //1byte 服务器状态标志，恒为#字符
            //5bytes 服务器状态
            //nbytes 服务器消息
            $error_code = unpack("v", $res[1] . $res[2])[1];
            $error_msg  = '';
            //第9个字符到结束的字符，均为错误消息
            for ($i = 9; $i < strlen($res); $i ++) {
                $error_msg .= $res[$i];
            }
            throw new \Exception($error_msg, $error_code);
        }

        return true;
	}

    public static function excute($sql,array $params = null)
    {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_PREPARE);
        Net::send($prelude . $sql);
        $res = Net::readPacket();

        $smtid = unpack("L", $res[1].$res[2].$res[3].chr(0))[1];
        echo "smtid=",$smtid,"\r\n";

        //cloumns count
        echo "cloumns count=".unpack("n", $res[4].$res[5].$res[6].$res[7])[1],"\r\n";



        /**
        字节	说明
        4	预处理语句的ID值
        1	标志位
            0x00: CURSOR_TYPE_NO_CURSOR
            0x01: CURSOR_TYPE_READ_ONLY
            0x02: CURSOR_TYPE_FOR_UPDATE
            0x04: CURSOR_TYPE_SCROLLABLE
        4	保留（值恒为0x01）

        如果参数数量大于0
        n	空位图（Null-Bitmap，长度 = (参数数量 + 7) / 8 字节）
        1	参数分隔标志

        如果参数分隔标志值为1
        n	每个参数的类型值（长度 = 参数数量 * 2 字节）
        n	每个参数的值
         */
        $data  = pack('C', CommandType::COM_STMT_EXECUTE);
        //4字节预处理语句的ID值
        $data .= pack("V", $smtid);
        $data .= Cursor::TYPE_NO_CURSOR;
        $data .= pack("V", 0x01);

        $len = intval((count($params)+7)/8);
        for ($i=0;$i<$len;$i++)
            $data.=chr(0x00);

        $data .= chr(0x01);
        for ($i=0;$i<count($params);$i++)
        $data .= pack("v", FieldType::TINY);

        $data = pack("L", strlen($data)).$data;


        Net::send($data );
        $res = Net::readPacket();
        //Packet::success($res);
        var_dump($res);


       // $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',1, CommandType::COM_STMT_FETCH);
        Net::send($prelude);
        $res = Net::readPacket();
        return $res;
    }

}