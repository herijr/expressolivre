<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Commons\Requests;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
			$this->app->singleton("get-requests", function(){
				return new Requests();
			});
    }
}
