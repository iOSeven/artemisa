<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $dates = ['deleted_at'];
    
    //protected $table = 'demo';

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'url',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
