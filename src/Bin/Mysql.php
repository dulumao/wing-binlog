<?php namespace Wing\Bin;
use Wing\Bin\Constant\CommandType;

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
		$chunk_size = strlen($sql) + 1;
		$prelude    = pack('LC',$chunk_size, CommandType::COM_QUERY);

		Net::send($prelude . $sql);

		$res   = Net::readPacket();
		$fbyte = ord($res[0]);

		//这里可能是三种类型的报文 Result set、Field和Row Data
        //以下解析 Result set
		if ($fbyte >= Packet::RESULT_SET_HEAD[0] && $fbyte <= Packet::RESULT_SET_HEAD[1]) {
            //列数量
		    $column = $fbyte;

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
		    $columns = '';
            //一直读取直到遇到结束报文
            while (ord($res[0]) != Packet::EOF_HEAD) {
                $res = Net::readPacket();
                $columns .= $res;
            }

            //行信息
            $res  = Net::readPacket();
            $rows = $res;
            //一直读取直到遇到结束报文
            while (ord($res[0]) != Packet::EOF_HEAD) {
                $res = Net::readPacket();
                $rows .= $res;
            }

            //这里还需要对报文进行解析
            var_dump($columns);
            var_dump($rows);
            return null;
        }

        else if ($fbyte == Packet::OK_PACK_HEAD) {
		    //1byte 0x00 OK报文 恒定为0x00
            //1-9bytes 受影响的行数
            //1-9bytes 索引id，执行多个insert时，默认是第一个
            //2bytes 服务器状态
            //2bytes 告警计数
            //nbytes 服务器消息，无结束符号，直接读取到尾部

            //消息解析
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

    public static function excute($sql)
    {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_PREPARE);
        Net::send($prelude . $sql);
        $res = Net::readPacket();

        $smtid = unpack("L", $res[1].$res[2].$res[3].chr(0))[1];
        echo "smtid=",$smtid,"\r\n";

        //cloumns count
        echo "cloumns count=".unpack("n", $res[4].$res[5].$res[6].$res[7])[1],"\r\n";


        $chunk_size = strlen($smtid) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_EXECUTE);
        Net::send($prelude . $smtid);
        $res = Net::readPacket();
        var_dump($res);


       // $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',1, CommandType::COM_STMT_FETCH);
        Net::send($prelude);
        $res = Net::readPacket();
        return $res;
    }

}