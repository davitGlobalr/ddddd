<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books';

    protected $fillable = [
        'name',
        'author',
        'description',
        'quntity',
        'img',
        'price',
    ];

    protected $casts = [
        'quntity' => 'integer',
        'price' => 'decimal:2',
    ];
}
