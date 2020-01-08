<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $casts = [
        'favorites' => 'array',
    ];
    protected $fillable = ['id','name','phone','address','favorites'];
}