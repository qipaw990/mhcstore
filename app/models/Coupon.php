<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Coupon extends Model
{
    protected string $table = 'coupons';
    protected array $fillable = [
        'code', 'title', 'discount_type', 'discount', 'min_purchase',
        'max_discount', 'start_date', 'expire_date', 'limit_per_user',
        'usage_count', 'status'
    ];

    public function validateCoupon(string $code, float $orderAmount): ?array
    {
        $today = date('Y-m-d');
        $sql = "SELECT * FROM `coupons`
                WHERE `code` = ? AND `status` = 1
                  AND `start_date` <= ? AND `expire_date` >= ?
                LIMIT 1";
        $coupon = Database::fetchOne($sql, [$code, $today, $today]);

        if (!$coupon) return null;
        if ($orderAmount < (float)$coupon['min_purchase']) return null;

        $discount = 0;
        if ($coupon['discount_type'] === 'percent') {
            $discount = ($orderAmount * ((float)$coupon['discount'] / 100));
            if ((float)$coupon['max_discount'] > 0) {
                $discount = min($discount, (float)$coupon['max_discount']);
            }
        } else {
            $discount = (float)$coupon['discount'];
        }

        $coupon['calculated_discount'] = min($discount, $orderAmount);
        return $coupon;
    }
}
