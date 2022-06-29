<?php

namespace Yang\Repository\Requests;

use Yang\Repository\Requests\BaseRequest;
use Illuminate\Http\Request;

class HoleRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        $route = $request->route()->getName();
        $reg = "/[@_!#$%^&*()<>?\\/|\\\}{~:]/";
        $rules = [];
        if($route == "holes.show"){
            $rules = [
                //"file_path"=>['required'],
                "id"=>['required','numeric'],
            ];
        }elseif($route == "holes.update"){
            $rules = [
                //"dir_path"=>['required'],
                "content"=>[function($attribute, $value, $fail){
                    $this->verifyContent($attribute, $value, $fail);
                }],
                "file_path"=>['required'],
                "file_name"=>['required',function ($attribute, $value, $fail) use($reg){
                    if(preg_match($reg,$value))
                        $fail("名称不能有特殊字符");
                    }],
                "id"=>['required',"numeric"],
               /* "level_1"=>['required',function ($attribute, $value, $fail) use($reg){
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                }],
                "level_2"=>[function ($attribute, $value, $fail) use($reg) {
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                },"nullable"],
                "level_3"=>[function ($attribute, $value, $fail)use($reg) {
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                },"nullable"],
                "file_name"=>[function ($attribute, $value, $fail)use($reg) {
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                },"required"],*/
            ];
        }elseif($route == "holes.store"){
            $rules = [
                //"file_name"=>['required'],
                //"dir_path"=>['required','unique:repositories,file_path'],
                "content"=>[function($attribute, $value, $fail){
                    $this->verifyContent($attribute, $value, $fail);
                }],
                "level_1"=>['required',function ($attribute, $value, $fail) use($reg){
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                    }],
                "level_2"=>[function ($attribute, $value, $fail) use($reg) {
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                    },"nullable"],
                "level_3"=>[function ($attribute, $value, $fail)use($reg) {
                    if(preg_match($reg,$value))
                        $fail("分类名不能有特殊字符");
                    },"nullable"],
                "file_name"=>[function ($attribute, $value, $fail)use($reg) {
                    if(preg_match($reg,$value))
                        $fail("名称不能有特殊字符");
                },"required"],
            ];
        }elseif($route == "holes.destory"){
            $rules = [
                //"file_path"=>['required'],
                "id"=>['required',"numeric"],
            ];
        }
        return $rules;
    }

    function verifyContent($attribute, $value, $fail){
        $request = request();
        $pocContent = $request->input("poc_content");
        $collectContent = $request->input("collect_content");
        $otherContent = $request->input("other_content");
        if(!$value && !$pocContent && !$collectContent && !$otherContent){
            $fail("请输入内容");
        }
    }

    public function messages(){
        return [
            "file_path.required"=>"缺少参数",
            "id.required"=>"缺少参数",
            "id.numeric"=>"缺少参数",
            "content.required"=>"请先输入内容",
            "dir_path.required"=>"缺少参数",
            "dir_path.unique"=>"文件名已经存在",
            "level_1.required"=>"分类1不能为空",
            //"level_1.regex"=>"分类名不能包含特殊字符1",
            "level_2.required"=>"分类2不能为空",
            //"level_2.regex"=>"分类名不能包含特殊字符",
            "level_3.required"=>"分类3不能为空",
            //"level_3.regex"=>"分类名不能包含特殊字符",
            "file_name.required"=>"文件名不能为空",
            //"file_name.required"=>"文件名不能包含特殊字符",
        ];

    }



}
