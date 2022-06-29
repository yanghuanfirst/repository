<?php


namespace Yang\Repository\Service;

use Yang\Repository\Helper\ApiResponse;
use Yang\Repository\Libs\HttpRequest;
use Yang\Repository\Models\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HoleService
{
    use ApiResponse;
    public $sftp;
    public $sftpConfig;
    public $repository;

    function __construct(Repository $repository)
    {
       // $this->sftpConfig = $this->getSftpConfig();
       // $this->sftp = $this->sftpLogin($this->sftpConfig);
        $this->repository = $repository;
    }


    //获取sftp配置
   /* function getSftpConfig()
    {
        return config('filesystems.disks.sftp');
    }*/

    //sftp登录
    /*function sftpLogin($sftpConfig)
    {
        $sftp = new SFTP($sftpConfig['host']);
        if (!$sftp->login($sftpConfig['username'], $sftpConfig['password'])) {
            $this->exception("sftp连接失败");
        }
        return $sftp;
    }*/

    //获取所有文档目录列表
    function getDirList()
    {
        //先看redis里面有没有
        $list = Cache::get("HoleDirList");
        if (!$list) {
            $list = $this->recurDir($this->sftpConfig['dir']);
            Cache::set("HoleDirList", json_encode($list));
        } else {
            $list = json_decode($list, 1);
        }
        return $list;
    }

    //递归获取目录下的所有文件
    function recurDir($pathName)
    {
        //将结果保存在result变量中
        $result = array();
        //判断传入的变量是否是目录
        if (!$this->sftp->is_dir($pathName)) {
            return null;
        }
        //取出目录中的文件和子目录名,使用scandir函数
        $allFiles = $this->sftp->nlist($pathName);
        //遍历他们
        foreach ($allFiles as $fileName) {
            //判断是否是.和..因为这两个东西神马也不是。。。
            if (in_array($fileName, array('.', '..'))) {
                continue;
            }
            //路径加文件名
            $fullName = $pathName . '/' . $fileName;
            //如果是目录的话就继续遍历这个目录
            if ($this->sftp->is_dir($fullName)) {
                //将这个目录中的文件信息存入到数组中
                $v['name'] = $fileName;
                $v['son'] = $this->recurDir($fullName);
                $result[$fullName] = $v;
            } else {
                //如果是文件就先存入临时变量
                $result[] = [
                    "name" => $fileName,
                    "son" => [],
                ];
            }
        }
        return $result;
    }

    //展示文档内容
    function showContent($path)
    {

        $filePath = $this->sftpConfig['dir'] . $path;
        $this->fileExists($filePath);
        return $this->sftp->get($filePath);
    }

    //展示内容，（数据库读取）
    function showContentDb($id)
    {
        $map = [
            "id"=>$id
        ];
        $field = [
            "id",
            "file_path",
            "content",
            'poc_content',
            'collect_content',
            'other_content',
            "pid",
            "file_name",
            "created_at"
        ];
        $detail = $this->repository->getDetail($map,$field);
        return $detail;
    }
    //根据路径获取到各路径目录
    function getPathByList($filePath){
        $arr = explode("/",$filePath);
        array_pop($arr);
        $len = count($arr);
        $res =[];
        $path = "";
        $field = ["id","file_name","file_path"];
        for($i=0;$i < $len;$i++){
            $var = "level_".($i+1);
            if($i == 0){
                $map = [
                    ["level","=",1],
                    ["is_dir","=",1],
                ];
                $res[$var] = $this->repository->getList($map,$field);
                $path .= $arr[$i] . "/";
            }else{
                if(!$res["level_".$i])$this->exception("数据有误");
                //通过从上级中找到对应到key,然后得到的ID就是父ID
                $key = $this->findKey($res["level_".$i],$path);
                $path .= $arr[$i] . "/";
                $map = [
                    ["pid","=",$res["level_".$i][$key]['id']],
                ];
                $res[$var] = $this->repository->getList($map,$field);
            }
        }
        return $res;
    }

    //搜索二维数组中是否存在某个值，并返回键
    public function findKey($list,$keyword,$key="file_path"){
        $res = 0;
        foreach($list as $k=>$v){
            if($v[$key] == $keyword){
                $res = $k;
                break;
            }
        }
        return $res;
    }




    //获取数据库中几级目录
    function getDirs(){
        $dirs = $this->repository->getDirs();
        return $dirs;
    }



    /**
    public function getParents($data, $id)
    {
        $tree = array();
        foreach ($data as $item) {
            if ($item['id'] == $id) {
                if ($item['pid'] > 0)
                    $tree = array_merge($tree, $this->getParents($data, $item['pid']));
                $tree[] = $item;
                break;
            }
        }
        return $tree;
    }
     */


    //修改文档
    public function update($filePath, $content)
    {
        $filePath = $this->sftpConfig['dir'] . $filePath;
        $this->fileExists($filePath);
        return $this->sftp->put($filePath, $content);
    }

    /**
     * @param $user 用户信息
     * @param $dirPath 拼接好的路径
     * @param $content 文件内容
     * @return bool
     * @throws \Throwable
     */
    public function updateDb($user, $id,$dirPath, $content)
    {
        $dirArr = explode("/", $dirPath);
        $len = count($dirArr);
        $file_path = "";
        $pinfo = [];
        DB::connection('red_mysql')->transaction(function () use ($len, $file_path, $dirArr, $user, $content, $pinfo,$id) {
            $cur_time = time();
            for ($i = 0; $i < $len; $i++) {
                if (($i + 1) == $len) {
                    $file_path .= $dirArr[$i];
                } else {
                    $file_path .= $dirArr[$i] . "/";
                }
                $is_dir = 1;
                $file_time = 0;
                if(strrchr($dirArr[$i],".md") == ".md"){
                    $is_dir = 2;
                    $file_time = $cur_time;
                    $data = [
                        "content" => $content,
                        "file_path" => $file_path,
                        "pid" => $pinfo ? $pinfo['id'] : 0,
                        "level" => $i+1,
                        "is_dir"=>$is_dir,
                        "file_name"=>$dirArr[$i],
                        //"file_time"=>$file_time
                    ];
                    $map = [
                        "id" => $id
                    ];
                }else{
                    $data = [
                        "file_path" => $file_path,
                        "pid" => $pinfo ? $pinfo['id'] : 0,
                        "level" => $i+1,
                        "is_dir"=>$is_dir,
                        "file_name"=>$dirArr[$i],
                    ];
                    $map = [
                        "file_path" => $file_path
                    ];
                }
                $info = $this->repository->getDetail($map);
                if (!$info) {
                    $data['user_id'] = $user->id;
                    $pinfo = $this->repository->addRepository($data);
                } else {
                    $pinfo = ["id" => $info['id'],"level"=>$info['level']];
                    $this->repository->updateRepository($data,$map);

                }
            }
        });
        //清缓存
        Cache::forget("HoleDirList");
        return true;
    }
    //保存文档(往服务器上存)
    public function store($dirPath, $fileName, $content)
    {
        $dirPath = $this->sftpConfig['dir'] . $dirPath;
        $realPath = $dirPath . '/' . $fileName;
        $this->fileExists($dirPath, false);
        return $this->sftp->put($realPath, $content);
    }
    //存储内容到数据库

    /**
     * @param $dirPath 目录
     * @param $fileName 文件名
     * @param $content 文件内容
     */
    public function storeDb($user, $dirPath, $content)
    {
        $dirArr = explode("/", $dirPath);
        $len = count($dirArr);
        $file_path = "";
        $pinfo = [];
        DB::connection('red_mysql')->transaction(function () use ($len, $file_path, $dirArr, $user, $content, $pinfo) {
            $cur_time = time();
            for ($i = 0; $i < $len; $i++) {
                if (($i + 1) == $len) {
                    $file_path .= $dirArr[$i];
                } else {
                    $file_path .= $dirArr[$i] . "/";
                }
                $is_dir = 1;
                $file_time = 0;
                if(strrchr($dirArr[$i],".md") == ".md"){
                    $is_dir = 2;
                    $file_time = $cur_time;
                    $dirArr[$i] = str_replace(".md","",$dirArr[$i]);
                }
                $map = [
                    "file_path" => $file_path
                ];
                $data = [
                    "user_id" => $user->id,
                    "content" => ($is_dir==1)?"":$content['content'],
                    "poc_content" => ($is_dir==1)?"":$content['poc_content'],
                    "collect_content" => ($is_dir==1)?"":$content['collect_content'],
                    "other_content" => ($is_dir==1)?"":$content['other_content'],
                    "file_path" => $file_path,
                    "pid" => $pinfo ? $pinfo['id'] : 0,
                    "level" => $i+1,
                    "is_dir"=>$is_dir,
                    "file_name"=>$dirArr[$i],
                    "file_time"=>$file_time
                ];
                $info = $this->repository->getDetail($map);
                if (!$info) {
                    $pinfo = $this->repository->addRepository($data);
                } else {
                    $pinfo = ["id" => $info['id'],"level"=>$info['level']];
                }
            }
        });
        //清缓存
        Cache::forget("HoleDirList");
        return true;
    }


    //删除文档
    public function destory($filePath)
    {
        $filePath = $this->sftpConfig['dir'] . $filePath;
        $this->fileExists($filePath);
        return $this->sftp->delete($filePath);
    }
    //删除数据
    function destoryDb($id){
        Cache::forget("HoleDirList");
        return $this->repository->deleteById($id);
    }
    //检测文件存不存在
    function fileExists($filePath, $isFile = true)
    {
        if ($isFile) {
            if (!$this->sftp->file_exists($filePath)) return $this->exception("文件不存在");
        } else {
            if (!$this->sftp->is_dir($filePath)) return $this->exception("目录不存在");
        }
    }



    //获取所有文档目录列表

    /**
     * @param bool $isCache 是否需要用缓存
     * @return array|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    function getDirLists($isCache = true)
    {
        if ($isCache) {
            $list = Cache::get("HoleDirList");
            if ($list && $list != "[]") {
                $list = json_decode($list, 1);
            } else {
                $dirList = $this->repository->getListNav();
                //$formatList = $this->formatDirList($list);
                //$formatList1 = $this->formatFileList($dirList);
                //$list = $this->listToTree($dirList);
                $list = $this->recursion($dirList);
                Cache::set("HoleDirList", json_encode($list));
            }
        } else {
            $dirList = $this->repository->getListNav();
            //$formatList = $this->formatDirList($list);
            //$formatList1 = $this->formatFileList($dirList);
            //$list = $this->listToTree($dirList);
            $list = $this->recursion($dirList);
            //print_r($list);die();
        }

        return $list;
    }
    public function recursion($data, $pid = 0)
    {
        $child = [];   // 定义存储子级数据数组
        foreach ($data as $key => $value) {
            if ($value['pid'] == $pid) {
                unset($data[$key]);  // 使用过后可以销毁
                $value['name']  = $value['file_name'];
                $value['children'] = $this->recursion($data, $value['id']);   // 递归调用，查找当前数据的子级
                $child[] = $value;   // 把子级数据添加进数组
            }
        }
        return $child;
    }
    /**
     * @param array $list
     * 去除掉. .. /. /..
     */
    function formatDirList($list = [])
    {
        $res = [];
        foreach ($list as $file) {
            if ($file['file_path'] == '.' || $file['file_path'] == "..") {
                continue;
            }
            $file['file_path'] = str_replace(['/..', "/."], ["/", "/"], $file['file_path']);
            $res[] = $file;
        }
        return $res;
    }

    function formatFileList($files)
    {
        $data = []; //数组用来存放，格式化后的数据
        //sort($files); //对文件目录列表排序
        foreach ($files as $key => $file) {
            $path = rtrim($file['file_path'], '/'); //如果是目录，去掉目录后面的斜杠
            $arr = explode('/', $path); //把str路径转数组
            $parent = ''; //定义动态路径，放下找时的临时存放路径
            foreach ($arr as $item) { //从顶级一级一级往下找
                $pid = $parent == '' ? 0 : $data[$parent]['id']; //查找父ID
                $parent .= $parent == '' ? $item : '/' . $item; //拼接路径
                if (isset($data[$parent]))  //如果已存在，跳过
                    continue;
                $data[$parent] = array(
                    'id' => $file['id'], //ID
                    'pid' => $pid, //父ID
                    'level' => substr_count($parent, '/') + 1, //层级
                    'name' => str_replace(".md","",$item), //名称，如果是目录保留后面斜杠,并去掉.md
                    'path' => $parent, //目录
                    'is_dir' => $file['is_dir'], //目录
                );
            }
        }
        return $data;
    }

    /**
     * 列表转树形(迭代)
     * @param array $list
     * @param bool $useKey 是否使用ID作为键值
     * @return array
     */

    function listToTree($list, $useKey = false)
    {
        $list = array_column($list, null, 'id');
        foreach ($list as $key => $val) {
            if ($val['pid']) {
                if (isset($list[$val['pid']])) {
                    if ($useKey) {
                        $list[$val['pid']]['children'][$key] = &$list[$key];
                    } else {
                        $list[$val['pid']]['children'][] = &$list[$key];
                    }
                }
            }
        }
        foreach ($list as $key => $val) {
            if ($val['pid']) unset($list[$key]);
        }
        if ($useKey) {
            return $list;
        } else {
            return array_values($list);
        }
    }

    //递归获取指定目录
    function reGetDirList()
    {
        $list = $this->sftp->nlist($this->sftpConfig['dir'], true);
        return $list;
    }

    //更新知识库
    function updateRepository($list)
    {
        foreach ($list as $file) {
            if ($file == '.' || $file == "..") {
                continue;
            }
            //排除点指定不需要的目录
            if(stripos($file,"https:") === 0){
                continue;
            }
            $file = str_replace(['/..', "/."], ["/", "/"], $file);
            //检查是否是md结尾
            $ext = ".md";
            $map = [
                "file_path" => $file
            ];
            $info = $this->repository->getDetail($map);
            $dir = $this->sftpConfig['dir'];
            $level = count(explode("/",$file));
            //文件
            if (strrchr($file, $ext) == $ext) {
                //获取文件的修改时间
                $mtime = $this->sftp->filemtime($dir . '/' . $file);
                $content = $this->sftp->get($dir . '/' . $file);
                if ($info) {
                    if ($info['file_time'] != $mtime) {

                        //更改内容
                        $data = [
                            "file_time" => $mtime,
                            "content" => $content,
                            "is_dir"=>2
                        ];
                        $map = [
                            "id" => $info['id']
                        ];
                        $this->repository->updateRepository($data, $map);
                    }
                } else {
                    //直接插入
                    $data = [
                        "file_time" => $mtime,
                        "content" => $content,
                        "file_path" => $file,
                        "level"=>$level,
                        "is_dir"=>2
                    ];
                    $this->repository->addRepository($data);

                }
            } else {//目录
                if (!$info) {
                    $data = [
                        "file_path" => $file,
                        "level"=>$level-1
                    ];
                    $this->repository->addRepository($data);
                }
            }
        }
        Cache::forget("HoleDirList");
    }

    //更新PID
    public function updateRepositoryPid(array $list)
    {
        foreach ($list as $v) {
            $data = [
                "pid" => $v['pid'],
                "file_name"=>$v['name'],
            ];
            $map = [
                "id" => $v['id']
            ];
            $this->repository->where($map)->update($data);
            if (isset($v['children']) && $v['children']) {
                $this->updateRepositoryPid($v['children']);
            }
        }
    }

    //搜索
    public function search($keyword)
    {
        $result = [];
        $field = [
            "id",
            "content",
            "file_name",
        ];
        if($keyword){
            $map = [["content","like","%{$keyword}%"]];
            $list = $this->repository->getList($map,$field);
            //$sql = "SELECT id,content,file_name FROM cfj_repositories WHERE MATCH (content) AGAINST ('+?' IN BOOLEAN MODE)";
            //$result = DB::select($sql,[$keyword]);
        }else{
            $now = now();
            $startTime = $now->subDays(5)->format("Y-m-d H:i:s");
            $endTime = $now->format("Y-m-d H:i:s");
            $map = [
                ["is_dir",2],
                ["created_at",">=",$startTime],
                ["is_dir","<=",$endTime],
                ["content","!=",""],
            ];
            $list = $this->repository->getList($map,$field);
        }
        $result = $this->handleList($list,$keyword);

        return $result;
    }
    //处理list,显示指定字符的前后30个字

    /**
     * @param $list 数据列表
     * @param $keyword 关键字
     * @param int $count 如果长度大于这个值才处理
     */
    function handleList($list,$keyword,$count = 140){
        $half = ceil($count/2);
        $result = [];
        foreach($list as $k=>$v){
            $len = mb_strlen($v['content'],"utf-8");
            $v['content'] = str_replace("分割线","",$v['content']);
            if($len > $count){
                if($keyword){
                    $index = mb_stripos($v['content'],$keyword,0,"UTF-8");
                }else{
                    $index = 0;
                }
                if($index === false){
                    continue;
                }
                if($index < $half){
                    $v['content'] = mb_substr($v['content'],0,$count,"UTF-8");
                }else{
                    $start = $index - $half;
                    $v['content'] = mb_substr($v['content'],$start,$count,"UTF-8");
                }
            }
            //去除文件名的md
            $v['file_name'] = str_replace(".md","",$v['file_name']);
            $result[] = $v;
        }
        return $result;
    }
    //获取顶级目录，之后再根据ID来获取下级
    public function getTopLevel($pid,$fixed = "")
    {
        $map = [
            ["pid",$pid?$pid:0],
            ["is_dir",1],
        ];
        if($fixed == 1){
            $fixedId = config("common_config.holes.attack_step_id");
            $map[] = ["id",$fixedId];
        }
        $field = [
            "id",
            "file_name"
        ];
        return $this->repository->getList($map,$field);
    }
    //生成dirpath
    public function createDirPath(array $data)
    {
        $level1 = isset($data['level_1'])?$data['level_1']:"";
        $level2 = isset($data['level_2'])?$data['level_2']:"";
        $level3 = isset($data['level_3'])?$data['level_3']:"";
        if($level3){
            if(!$level2){
                $this->exception("请选择分类2");
            }
        }
        return $level1.($level2?("/".$level2):'').($level3?("/".$level3):'').("/".$data['file_name'].".md");
    }
    //获取详情
    public function getDetail($dirPath)
    {
        return $this->repository->getDetail(["file_path"=>$dirPath]);
    }
    //只针对编辑内容，不涉及修改分类
    public function updateDbNoTypeModify($user, $id, $content,$fileName,$filePath)
    {
        $data = [
            "content"=>$content['content'],
            "poc_content"=>$content['poc_content'],
            "collect_content"=>$content['collect_content'],
            "other_content"=>$content['other_content'],
            'file_name'=>$fileName,
        ];
        $newFilePath = $this->getFilePath($filePath, $fileName);
        $data['file_path'] = $newFilePath;
        $map = [
            "id"=>$id,
            "is_dir"=>2
        ];
        Cache::forget('HoleDirList');
        return $this->repository->updateRepository($data,$map);
    }
    //编辑的时候验证名称是否已经存在
    public function isHadForUpdate($id, $filePath, $fileName)
    {
        $newFilePath = $this->getFilePath($filePath,$fileName);
        $map = [
            ['id','!=',$id],
            ['file_path','=',$newFilePath]
        ];
        return $this->repository->getDetail($map);
    }

    function getFilePath($filePath, $fileName){
        $filePathArr = explode("/",$filePath);
        array_pop($filePathArr);
        $fileName .= ".md";
        array_push($filePathArr,$fileName);
        $newFilePath = implode("/",$filePathArr);
        return $newFilePath;
    }
    //搜索返回其他PwnWiki api接口返回的数据
    public function pwSearch($keyword)
    {
        $list = [];
        if(!$keyword)
            return $list;
        //$url = "https://www.pwnwiki.org/api.php?action=query&list=search&srlimit=100&srsort=create_timestamp_desc&srprop=title&srsearch={$keyword}&format=json";
        //$url = "https://www.pwnwiki.org/api.php?action=opensearch&search=thinkphp&limit=5&format=json";
        $url = "https://www.pwnwiki.org/routes.php";
        $http = new HttpRequest();
        /*$params = [
            "action"=>"query",
            "list"=>"search",
            "srlimit"=>"5",
            "srsort"=>"create_timestamp_desc",
            "srprop"=>"title",
            "srsearch"=>$keyword,
            "format"=>"json",
        ];
        $list = data_get($result,"query.search",[]);
        foreach($list as $k=>$v){
            $v['file_name'] = $v['title'];
            $v['url'] = "https://www.pwnwiki.org/index.php?title=".$v['title'];
            $list[$k] = $v;
        }
        */
        $params = [
            "action"=>"opensearch",
            "search"=>$keyword,
            "limit"=>5,
            "format"=>"json",
        ];
        $result = $http->getRequest($url,$params)->getContent();
        $titles = data_get($result,"1",[]);
        $urls = data_get($result,"3",[]);
        if(!$titles)return $list;
        foreach($titles as $k=>$v){
            $tmp = [
                "file_name"=>$v,
                "url"=>data_get($urls,$k,"")
            ];
            $list[] = $tmp;
        }

        return $list;
    }
    //用来
    public function getDirListsByCron()
    {
        //用来每天定时更新，排序必须按照文件名来排序
        $dirList = $this->repository->getListByCron();
        $formatList = $this->formatDirList($dirList);
        $formatList1 = $this->formatFileList($formatList);
        $list = $this->listToTree($formatList1);
        return $list;
    }


}
