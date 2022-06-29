<?php
/**
 * Created By yanghuan
 * Author yanghuan
 * Date 2022/6/28
 * Time 10:19
 */
namespace Yang\Repository\Providers;
use Illuminate\Support\ServiceProvider;
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * 服务提供者加是否延迟加载.
     *
     * @var bool
     */
    protected $defer = false; // 延迟加载服务
    /**
     * bootstrap the application services.
     *
     * @return voID
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../migrations/2022_06_29_000000_create_repositories_table.php');
        if ( ! $this->app->routesAreCached() ) {

            $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        }
    }
    /**
     * Register the application services.
     *
     * @return voID
     */
    public function register()
    {

    }

}
