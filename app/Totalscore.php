<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Totalscore extends Model
{
    protected $table = 'bubble_users_total_scores';

    protected $fillable = [
        'user_id', 'score'
    ];
}
