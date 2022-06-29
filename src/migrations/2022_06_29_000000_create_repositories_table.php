<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepositoriesTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary();
            $table->integer('user_id')->nullable(false)->default(0)->comment("提交知识库的用户ID");
            $table->integer('pid')->nullable(false)->default(0)->comment("父级ID");
            $table->longText('content')->comment("描述内容");
            $table->longText('poc_content')->comment("POC内容");;
            $table->longText('collect_content')->comment("其他内容");;
            $table->longText('other_content')->comment("POC内容");;
            $table->integer('file_time')->nullable(false)->default(0)->comment("文件的修改时间，用来判断是否需要更新content");;
            $table->tinyInteger('level')->nullable(false)->default(1)->comment("等级");
            $table->tinyInteger('is_dir')->nullable(false)->default(1)->comment("1:目录 2：不是");
            $table->string('file_name',255)->nullable(false)->default('')->comment("不含路径的名称");
            $table->tinyInteger('sort_field')->nullable(false)->default(0)->comment("用来排序");
            $table->timestamps();
            $table->dropSoftDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repositories');
    }
}
