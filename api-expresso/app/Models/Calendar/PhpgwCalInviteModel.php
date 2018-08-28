<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;

class PhpgwCalInviteModel extends Model
{
   	protected $table       = 'phpgw_cal_invite';
    protected $primaryKey  = 'invite_id';
    protected $fillable    = [ 'hash', 'contents', 'owner' ];
		
		public $timestamps 			= false;
}
