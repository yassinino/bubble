<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserNetwork extends Model
{
     protected $table = 'bubble_users_networks';

     protected $fillable = [
        'user_id', 'network_id', 'access_keys', 'userId'
    ];

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

     public function network()
    {
        return $this->belongsTo(Network::class, 'network_id');
    }

}
