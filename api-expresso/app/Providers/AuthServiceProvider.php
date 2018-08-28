<?php

namespace App\Providers;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Auth\ExpressoUser;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
		private $adapter;
	
		public function register(){
			$this->adapter = new ExpressoAdapter();
		}

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
			$this->app['auth']->viaRequest('api', function ($request) {

				$this->adapter->setParams( App("get-requests")->getRequest( $request ) );

				$result = $this->adapter->isLoggedIn();

				if( $result ){
					$attributes['id']   = $GLOBALS['phpgw']->accounts->data['account_id'];
					$attributes['name'] = $GLOBALS['phpgw']->accounts->data['fullname'];
					return new ExpressoUser( $attributes );
				}
			});
    }
}
