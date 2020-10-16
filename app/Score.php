<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $table = 'bubble_users_networks_scores';

    protected $fillable = [
        'user_id', 'network_id', 'score'
    ];


    public function network()
    {
        return $this->belongsTo(Network::class, 'network_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
