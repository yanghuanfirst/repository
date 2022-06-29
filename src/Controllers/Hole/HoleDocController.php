<?php

namespace Yang\Repository\Controllers\Hole;

use Yang\Repository\Controllers\BaseController;
use Yang\Repository\Requests\HoleRequest;
use Yang\Repository\Service\HoleService;

class HoleDocController extends BaseController
{

    public $holeService;
    function __construct(HoleService $holeService)
    {
        $this->holeService = $holeService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$list = $this->holeService->getDirList();
        $list = $this->holeService->getDirLists();
        return $this->success("获取成功",['list'=>$list]);
    }

    function search(HoleRequest $request){
        $keyword = $request->input("keyword");
        $list = $this->holeService->search($keyword);
        return $this->success("获取成功",['list'=>$list]);
    }
    //搜索返回其他PwnWiki api接口返回的数据
    function pwSearch(HoleRequest $request){
        $keyword = $request->input("keyword");
        $list = $this->holeService->pwSearch($keyword);
        return $this->success("获取成功",['wiki_list'=>$list]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(HoleRequest $request)
    {
        //$fileName = $request->input("file_name");
        $user = auth()->user();
        //$dirPath = $request->input("dir_path");//最多3级，是由/分开(完整路径)
        $contents= $request->only(['content','poc_content','collect_content','other_content']);
        $data = $request->only(["level_1","level_2","level_3","file_name"]);
        //生成dirPath
        $dirPath = $this->holeService->createDirPath($data);
        $had = $this->holeService->getDetail($dirPath);
        if($had) return $this->fail("该数据已存在");
        $isSuc = $this->holeService->storeDb($user,$dirPath,$contents);
        return $isSuc?$this->success("添加成功"):$this->fail("添加失败，请稍后再试");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //$filePath  文件目录+文件名  qingybak/IOT安全/q.txt
        //$filePath = $request->input("file_path");
        //$id = $request->input("id");
        //$content = $this->holeService->showContent($filePath);
        //详情
        $result = $this->holeService->showContentDb($id);
        if(!$result)return $this->fail("暂无数据");
        $result['file_name'] = str_replace('.md','',$result['file_name']);
        $result['file_path'] = str_replace('.md','',$result['file_path']);

        $result['content'] = $result['content']?$result['content']:"";
        $result['poc_content'] = $result['poc_content']?$result['poc_content']:"";
        $result['collect_content'] = $result['collect_content']?$result['collect_content']:"";
        $result['other_content'] = $result['other_content']?$result['other_content']:"";



        //$result['content'] = $result['content']."\n".$result['poc_content']."\n".$result['collect_content']."\n".$result['other_content'];
        //$result->makeHidden(['poc_content','collect_content','other_content']);
        //获取几级目录(前端暂时不显示)
        //$dirs = $this->holeService->getPathByList($result['file_path']);
        $dirs = [];
        return $this->success("获取成功",['detail'=>$result,"dirs"=>$dirs]);
    }
    //编辑前查看详情，其实和show方法一样，但是这个方法不能去掉前端的关键字-分割线
    function detail($id){
        $result = $this->holeService->showContentDb($id);

        if(!$result)return $this->fail("暂无数据");
        $result['file_name'] = str_replace('.md','',$result['file_name']);
        $result['file_path'] = str_replace('.md','',$result['file_path']);
        //获取几级目录(前端暂时不显示)
        //$dirs = $this->holeService->getPathByList($result['file_path']);
        $result['content'] = $result['content']?$result['content']:"";
        $result['poc_content'] = $result['poc_content']?$result['poc_content']:"";
        $result['collect_content'] = $result['collect_content']?$result['collect_content']:"";
        $result['other_content'] = $result['other_content']?$result['other_content']:"";
        $dirs = [];
        return $this->success("获取成功",['detail'=>$result,"dirs"=>$dirs]);
    }

    //获取顶级目录，之后再根据ID来获取下级
    function getTopLevel(HoleRequest $request){
        //$level = $request->input("level",1);
        $pid = $request->input("id",0);
        $fixed = $request->input("fixed","");//1：如果有这个值传过来。那就需要指定一级目录
        $list = $this->holeService->getTopLevel($pid,$fixed);
        $level1 = "反诈";
        $level2 = "攻击步骤";
        $level3 = "";
        return $this->success("获取成功",["list"=>$list,"level_1"=>$level1,"level_2"=>$level2,"level_3"=>$level3]);
    }





    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(HoleRequest $request)
    {
        //
        $user = auth()->user();
       // $filePath = $request->input("dir_path");
        $contents= $request->only(['content','poc_content','collect_content','other_content']);
        $id = $request->input("id");
        $filePath = $request->input("file_path");
        $fileName = $request->input("file_name");
        $had = $this->holeService->isHadForUpdate($id,$filePath,$fileName);
        if($had)return $this->fail("该名称已经存在");
        //生成dirPath
        //有修改目录的时候用以下方法
        /*
        $data = $request->only(["level_1","level_2","level_3","file_name"]);
        $dirPath = $this->holeService->createDirPath($data);
        $had = $this->holeService->getDetail($dirPath);
        if($had) return $this->fail("该数据已存在");
        //$isSuc = $this->holeService->update($filePath,$content);
        $isSuc = $this->holeService->updateDb($user,$id,$dirPath,$content);*/
        //只针对编辑内容，不涉及修改分类
        $isSuc = $this->holeService->updateDbNoTypeModify($user,$id,$contents,$fileName,$filePath);
        return $isSuc?$this->success("修改成功"):$this->fail("修改失败，请稍后再试");;
    }

    /**
     * Delete docment
     */
    function destory($id){
        //$filePath = $request->input("file_path");
        //$id = $request->input("id");
        $isSuc = $this->holeService->destoryDb($id);
        return $isSuc?$this->success("删除成功"):$this->fail("删除失败，请稍后再试");
    }


}
