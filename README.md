## 说明
    该包没有任何通用性，该包没有任何通用性，该包没有任何通用性，只是我自己需要重复用到的一个模块
    
    
## 安装
    composer require yang-repository/yang
    
##  生成数据迁移
    php artisan migrate --path=vendor/yang-repository/yang/src/migrations/2022_06_29_000000_create_repositories_table.php


## 发布配置文件
    该配置文件主要作用是配置这些路由的中间件

    php artisan vendor:publish --provider="Yang\Repository\Providers\RepositoryServiceProvider"



