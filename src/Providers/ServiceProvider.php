<?php

namespace Sypo\WineSearcher\Providers;

use Aero\Admin\AdminModule;
use Aero\Common\Providers\ModuleServiceProvider;
use Aero\Common\Facades\Settings;
use Aero\Common\Settings\SettingGroup;

class ServiceProvider extends ModuleServiceProvider
{
    protected $commands = [
        'Sypo\WineSearcher\Console\Commands\WineSearcherFeed',
    ];

    public function register(): void 
    {
        $this->commands($this->commands);
    }
	
    public function boot(): void 
    {
        parent::boot();
    }
}