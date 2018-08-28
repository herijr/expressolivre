<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;

class PhpgwCalUserModel extends Model
{
   	protected $table       = 'phpgw_cal_user';
    protected $primaryKey  = 'cal_id';
    protected $fillable    = [ 'cal_id', 'cal_login', 'cal_status', 'cal_type'];
		
		public $timestamps 			= false;
}
