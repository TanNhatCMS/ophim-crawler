<?php

namespace Ophim\Crawler\OphimCrawler;
use Ophim\Core\Policies\PermissionPolicy;
use Ophim\Core\Policies\RolePolicy;
use Ophim\Core\Policies\UserPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as SP;
use Ophim\Crawler\OphimCrawler\Console\CrawlerScheduleCommand;
use Ophim\Crawler\OphimCrawler\Option;
use Ophim\Core\Policies\CrawlSchedulePolicy;


class OphimCrawlerServiceProvider extends SP
{

 /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        return [
            CrawlSchedule::class => CrawlSchedulePolicy::class
        ];
    }
    public function register()
    {
       
        config(['plugins' => array_merge(config('plugins', []), [
            'ophim-crawler' =>
            [
                'name' => 'Tải dữ liệu Ophim',
                'package_name' => 'tannhatcms/ophim-crawler',
                'icon' => 'la la-hand-grab-o',
                'entries' => [
                    ['name' => 'Tải dữ liệu', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/ophim-crawler')],
                    ['name' => 'Cài đặt', 'icon' => 'la la-cog', 'url' => backpack_url('/plugin/ophim-crawler/options')],
                ],
            ]
        ])]);

        config(['logging.channels' => array_merge(config('logging.channels', []), [
            'ophim-crawler' => [
                'driver' => 'daily',
                'path' => storage_path('logs/tannhatcms/ophim-crawler.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 7,
            ],
        ])]);

        config(['ophim.updaters' => array_merge(config('ophim.updaters', []), [
            [
                'name' => 'Tải dữ liệu Ophim',
                'handler' => 'Ophim\Crawler\OphimCrawler\Crawler'
            ]
        ])]);
    }

    public function boot()
    {
        $this->registerPolicies();
        $this->commands([
            CrawlerScheduleCommand::class,
        ]);

        $this->app->booted(function () {
            $this->loadScheduler();
        });

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ophim-crawler');
    }



    protected function loadScheduler()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('ophim:plugins:ophim-crawler:schedule')->cron(Option::get('crawler_schedule_cron_config', '*/10 * * * *'))->withoutOverlapping();
    }
}
