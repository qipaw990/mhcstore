<?php
namespace App\Models;

use App\Core\Model;

class Review extends Model
{
    protected string $table = 'reviews';
    protected array $fillable = [
        'order_id', 'user_id', 'store_id', 'product_id', 'delivery_man_id',
        'rating', 'comment', 'reply'
    ];
}
