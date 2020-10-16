<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    protected $table = 'bubble_networks';

    protected $fillable = [
        'network_name'
    ];
}
