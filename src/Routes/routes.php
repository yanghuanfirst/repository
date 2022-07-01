<?php
/**
 * Created By yanghuan
 * Author yanghuan
 * Date 2022/6/28
 * Time 10:28
 */


$middleware = config("repos_config.middleware");
if(!$middleware){
    $config = require_once __DIR__.'/../config/repos_config.php';
    $middleware = $config['middleware'];
}
//知识产权相关接口
Route::middleware($middleware)->prefix("/api/holes")->group(function () {
    //漏洞文档列表
    Route::get("/", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'index'])->name("holes");
    //点击查看文件内容
    Route::get("show/{id}", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'show'])->name("holes.show");
    //点击编辑的时候
    Route::get("update-detail/{id}", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'detail'])->name("holes.update.detail");
    //修改
    Route::put("update", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'update'])->name("holes.update");
    //添加
    Route::post("store", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'store'])->name("holes.store");
    //删除
    Route::delete("del/{id}", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'destory'])->name("holes.destory");
    //搜索
    Route::get("search", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'search'])->name("holes.search");
    //搜索返回其他PwnWiki api接口返回的数据
    Route::get("pw/search", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'pwSearch'])->name("holes.pw.search");
    //获取目录分类
    Route::get("get-type", [\Yang\Repository\Controllers\Hole\HoleDocController::class, 'getTopLevel'])->name("holes.get.type");
});
