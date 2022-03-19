<?php
// +----------------------------------------------------------------------
// | 此文件主要是操作数据库用的帮助函数，帮助我们更加方便的操作数据库
// +----------------------------------------------------------------------


/**建议使用该函数
 * 连接MYSQL函数,通过常量的形式来连接数据库
 * 自定义配置文件，配置文件中自定义常量，包含需要使用的信息
 * @return resource
 */
function connect(){ 
    //连接mysql
    $link=@mysqli_connect(DB_HOST,DB_USER,DB_PWD) or die ('<h2>数据库连接失败，请去config/config.php进行配置</h2><br/>ERROR '.mysqli_errno().':'.mysqli_error());
    //设置字符集
    mysqli_set_charset($link,DB_CHARSET);
    //打开指定的数据库
    mysqli_select_db($link,DB_DBNAME) or die('指定的数据库打开失败,请去config/config.php进行配置');
    return $link;
}



/* array(
'username'=>'king',
'password'=>'123123',
'email'=>'dh@qq.com'
) */

/**
 * 插入记录的操作
 * @param array $array
 * @param string $table
 * @return boolean
 */
function insert($link,$array,$table){
    $keys=join(',',array_keys($array));
    $values="'".join("','", array_values($array))."'";
    $sql="insert {$table}({$keys}) VALUES ({$values})";
    $res=mysqli_query($link,$sql);
    if($res){
        return mysqli_insert_id($link);
    }else{
        exit(mysqli_error($link));
    }
}


/**
 * MYSQL更新操作
 * @param array $array
 * @param string $table
 * @param string $where
 * @return number|boolean
 */
function update($link,$array,$table,$where=null){
    foreach ($array as $key=>$val){
        @$sets.=$key."='".$val."',";
    }
    $sets=rtrim($sets,','); //去掉SQL里的最后一个逗号
    $where=$where==null?'':' WHERE '.$where;
    $sql="UPDATE {$table} SET {$sets} {$where}";
    $res=mysqli_query($link,$sql);
    if ($res){
        return mysqli_affected_rows($link);
    }else {
        return false;
    }
}


/**
 * 删除记录的操作
 * @param string $table
 * @param string $where
 * @return number|boolean
 */
function mysql_delete($link,$table,$where=null){
    $where=$where==null?'':' WHERE '.$where;
    $sql="DELETE FROM {$table}{$where}";
    $res=mysqli_query($link,$sql);
    if ($res){
        return mysqli_affected_rows($link);
    }else {
        return false;
    }
}

/**
 * 查询一条记录
 * @param string $sql
 * @param string $result_type
 * @return boolean
 */
function fetchOne($link,$sql,$result_type=MYSQLI_ASSOC){
    $result=mysqli_query($link,$sql);
    if ($result && mysqli_num_rows($result)>0){
        return mysqli_fetch_array($result,$result_type);
    }else {
        return false;
    }
}

/**
 * 得到表中的所有记录
 * @param string $sql
 * @param string $result_type
 * @return boolean
 */
function fetchAll($link,$sql,$result_type=MYSQLI_ASSOC){
    $result=mysqli_query($link,$sql);
    if ($result && mysqli_num_rows($result)>0){
        while ($row=mysqli_fetch_array($result,$result_type)){
            $rows[]=$row;
        }
        return $rows;
    }else {
        return false;
    }
}


/**取得结果集中的记录的条数
 * @param string $sql
 * @return number|boolean
 */
function getTotalRows($link,$sql){
    $result=mysqli_query($link,$sql);
    if($result){
        return mysqli_num_rows($result);
    }else {
        return false;
    }
    
}

/**释放结果集
 * @param resource $result
 * @return boolean
 */
function  freeResult($result){
    return  mysqli_free_result($result);
}



/**断开MYSQL
 * @param resource $link
 * @return boolean
 */
function close($link=null){
    return mysqli_close($link);
}


/**得到客户端的信息
 * @return string
 */
function getClintInfo($link){
    return mysqli_get_client_info($link);
}


/**得到MYSQL服务器端的信息
 * @return string
 */
function getServerInfo($link=null){
    return mysqli_get_server_info($link);
}



/**得到主机的信息
 * @return string
 */
function getHostInfo($link=null){
    return mysqli_get_host_info($link);
}

/**得到协议信息
 * @return string
*/
function getProtoInfo($link=null){
    return mysqli_get_proto_info($link);
}

/**
* 对查询结果集进行排序
* @access public
* @param array $list 查询结果
* @param string $field 排序的字段名
* @param array $sortby 排序类型
* asc正向排序 desc逆向排序 nat自然排序
* @return array
*/
function list_sort_by($list,$field, $sortby='asc') {
   if(is_array($list)){
       $refer = $resultSet = array();
       foreach ($list as $i => $data)
           $refer[$i] = &$data[$field];
       switch ($sortby) {
           case 'asc': // 正向排序
                asort($refer);
                break;
           case 'desc':// 逆向排序
                arsort($refer);
                break;
           case 'nat': // 自然排序
                natcasesort($refer);
                break;
       }
       foreach ( $refer as $key=> $val)
           $resultSet[] = &$list[$key];
       return $resultSet;
   }
   return false;
}

function getInfo($url){
    $ch = curl_init(); 
    curl_setopt ($ch, CURLOPT_URL, $url); 
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10); 
    $info = @json_decode(curl_exec($ch));
    if($info){
        if($info->status==0){
            exit($info->message);
        }
    }
}


/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
 
    if(is_array($list)) {
 
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $val) {
            $refer[$val[$pk]] =& $list[$key];
        }
 
 
        foreach ($list as $key => $val) {
            // 判断是否存在parent
            $parentId =  $val[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}


