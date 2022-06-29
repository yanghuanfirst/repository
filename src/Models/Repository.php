<?php

namespace Yang\Repository\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repository extends BaseModel
{

    use HasFactory,SoftDeletes;
    protected $guarded = [];

    function updateRepository($data,$map){
        if(!$map)return;

        return $this->where($map)->update($data);
    }

    public function getDetail(array $map,$field = ["id","file_time","pid","level"])
    {
        $info = $this->select($field)->where($map)->first();
        return $info;
    }

    public function addRepository(array $data)
    {
        return $this->create($data);
    }

    public function getListNav($map=[],$field = ["id","file_path","is_dir","pid","level","file_name"])
    {
        return $this
            ->select($field)
            ->where($map)
            ->orderBy("sort_field","asc")
            ->get();
    }
    public function getList($map=[],$field = ["id","file_path","is_dir"])
    {
        return $this
            ->select($field)
            ->where($map)
            ->orderBy("user_id","desc")
            ->orderBy("file_time","desc")
            ->get();
    }

    public function getDirs()
    {
        return $this->select("id","file_name","level","pid","file_path")->where("is_dir",1)->get();
    }

    function deleteById($id){
        return $this->where('id',$id)->delete();
    }
    //获取最后一层
    public function getHolesLastLevel($id)
    {
        $map = [
            "pid"=>$id
        ];
       return $this->select("file_name","id","is_dir")->where("user_id","!=",0)->where($map)->orderBy("created_at","desc")->get();
    }
    //用来每天定时更新，排序必须按照文件名来排序
    public function getListByCron()
    {
        return $this
            ->select("file_path","id","pid","file_name")
            ->orderBy("file_path","asc")
            ->get();
    }

}
