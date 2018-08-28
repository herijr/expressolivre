<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;

class PhpgwCalModel extends Model
{
   	protected $table       = 'phpgw_cal';
    protected $primaryKey  = 'cal_id';
    protected $fillable    = [ 'uid', 'owner', 'category', 'groups', 'datetime',
															'mdatetime', 'edatetime', 'priority', 'cal_type', 'is_public', 'title',
															'description', 'location', 'reference', 'ex_participants', 'last_status', 'last_update' ];
		
		public $timestamps 			= false;
}
