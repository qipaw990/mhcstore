<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use Exception;

class Review extends Model
{
    protected string $table = 'reviews';
    protected array $fillable = [
        'order_id', 'user_id', 'store_id', 'product_id', 'delivery_man_id',
        'rating', 'comment', 'reply', 'created_at'
    ];

    /**
     * Submit review for an order (Store & Courier)
     */
    public function submitReview(
        int $orderId,
        int $userId,
        int $storeRating,
        ?string $storeComment = '',
        ?int $dmRating = null,
        ?string $dmComment = ''
    ): array {
        $order = Database::fetchOne("SELECT * FROM `orders` WHERE `id` = ? LIMIT 1", [$orderId]);
        if (!$order) {
            throw new Exception("Pesanan tidak ditemukan.");
        }

        if ((int)$order['customer_id'] !== $userId) {
            throw new Exception("Anda tidak memiliki akses untuk mengulas pesanan ini.");
        }

        if ($order['order_status'] !== 'delivered') {
            throw new Exception("Hanya pesanan yang sudah selesai (Delivered) yang dapat diulas.");
        }

        $now = date('Y-m-d H:i:s');
        $storeReviewId = null;
        $dmReviewId = null;

        // 1. Review Store (if order has store_id)
        if (!empty($order['store_id'])) {
            $storeId = (int)$order['store_id'];
            $existingStoreReview = Database::fetchOne(
                "SELECT id FROM `reviews` WHERE `order_id` = ? AND `user_id` = ? AND `store_id` = ? LIMIT 1",
                [$orderId, $userId, $storeId]
            );

            if ($existingStoreReview) {
                Database::update('reviews', [
                    'rating'     => min(5, max(1, $storeRating)),
                    'comment'    => sanitize($storeComment ?? ''),
                    'created_at' => $now
                ], 'id = ?', [$existingStoreReview['id']]);
                $storeReviewId = $existingStoreReview['id'];
            } else {
                $storeReviewId = $this->create([
                    'order_id'   => $orderId,
                    'user_id'    => $userId,
                    'store_id'   => $storeId,
                    'rating'     => min(5, max(1, $storeRating)),
                    'comment'    => sanitize($storeComment ?? ''),
                    'created_at' => $now
                ]);
            }

            // Recalculate Store Rating & Reviews Count
            $this->recalculateStoreRating($storeId);

            // Notify store vendor
            $store = Database::fetchOne("SELECT vendor_id, name FROM `stores` WHERE `id` = ? LIMIT 1", [$storeId]);
            if ($store && !empty($store['vendor_id'])) {
                (new Notification())->createNotification(
                    (int)$store['vendor_id'],
                    'Ulasan Toko Baru! ⭐',
                    "Toko Anda menerima ulasan {$storeRating} bintang untuk pesanan #{$order['order_code']}.",
                    'review'
                );
            }
        }

        // 2. Review Delivery Courier (if delivery_man_id exists)
        if (!empty($order['delivery_man_id']) && $dmRating !== null) {
            $dmId = (int)$order['delivery_man_id'];
            $existingDmReview = Database::fetchOne(
                "SELECT id FROM `reviews` WHERE `order_id` = ? AND `user_id` = ? AND `delivery_man_id` = ? LIMIT 1",
                [$orderId, $userId, $dmId]
            );

            if ($existingDmReview) {
                Database::update('reviews', [
                    'rating'     => min(5, max(1, $dmRating)),
                    'comment'    => sanitize($dmComment ?? ''),
                    'created_at' => $now
                ], 'id = ?', [$existingDmReview['id']]);
                $dmReviewId = $existingDmReview['id'];
            } else {
                $dmReviewId = $this->create([
                    'order_id'        => $orderId,
                    'user_id'         => $userId,
                    'delivery_man_id' => $dmId,
                    'rating'          => min(5, max(1, $dmRating)),
                    'comment'         => sanitize($dmComment ?? ''),
                    'created_at'      => $now
                ]);
            }

            // Recalculate Courier Rating & Reviews Count
            $this->recalculateDmRating($dmId);

            // Notify delivery courier
            $dm = Database::fetchOne("SELECT user_id FROM `delivery_men` WHERE `id` = ? LIMIT 1", [$dmId]);
            if ($dm && !empty($dm['user_id'])) {
                (new Notification())->createNotification(
                    (int)$dm['user_id'],
                    'Ulasan Driver Baru! ⭐',
                    "Anda menerima ulasan {$dmRating} bintang untuk pengantaran pesanan #{$order['order_code']}.",
                    'review'
                );
            }
        }

        return [
            'store_review_id' => $storeReviewId,
            'dm_review_id'    => $dmReviewId
        ];
    }

    /**
     * Check existing reviews for an order
     */
    public function getOrderReview(int $orderId, int $userId): array
    {
        $reviews = Database::query(
            "SELECT * FROM `reviews` WHERE `order_id` = ? AND `user_id` = ?",
            [$orderId, $userId]
        );

        $result = [
            'has_reviewed' => !empty($reviews),
            'store_review' => null,
            'dm_review'    => null
        ];

        foreach ($reviews as $rev) {
            if (!empty($rev['store_id'])) {
                $result['store_review'] = $rev;
            } elseif (!empty($rev['delivery_man_id'])) {
                $result['dm_review'] = $rev;
            }
        }

        return $result;
    }

    /**
     * Get reviews list for a store
     */
    public function getStoreReviews(int $storeId, int $limit = 20): array
    {
        return Database::query(
            "SELECT r.*, u.name as customer_name, u.avatar as customer_avatar, o.order_code
             FROM `reviews` r
             JOIN `users` u ON r.user_id = u.id
             LEFT JOIN `orders` o ON r.order_id = o.id
             WHERE r.store_id = ?
             ORDER BY r.id DESC
             LIMIT {$limit}",
            [$storeId]
        );
    }

    /**
     * Get reviews list for a delivery courier
     */
    public function getDmReviews(int $dmId, int $limit = 20): array
    {
        return Database::query(
            "SELECT r.*, u.name as customer_name, u.avatar as customer_avatar, o.order_code
             FROM `reviews` r
             JOIN `users` u ON r.user_id = u.id
             LEFT JOIN `orders` o ON r.order_id = o.id
             WHERE r.delivery_man_id = ?
             ORDER BY r.id DESC
             LIMIT {$limit}",
            [$dmId]
        );
    }

    /**
     * Recalculate average rating & reviews_count for store
     */
    public function recalculateStoreRating(int $storeId): void
    {
        $stat = Database::fetchOne(
            "SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM `reviews` WHERE `store_id` = ?",
            [$storeId]
        );

        $count = (int)($stat['count'] ?? 0);
        $avg = ($count > 0) ? round((float)$stat['avg_rating'], 1) : 0.0;

        Database::update('stores', [
            'rating'        => $avg,
            'reviews_count' => $count
        ], 'id = ?', [$storeId]);
    }

    /**
     * Recalculate average rating & reviews_count for delivery courier
     */
    public function recalculateDmRating(int $dmId): void
    {
        $stat = Database::fetchOne(
            "SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM `reviews` WHERE `delivery_man_id` = ?",
            [$dmId]
        );

        $count = (int)($stat['count'] ?? 0);
        $avg = ($count > 0) ? round((float)$stat['avg_rating'], 1) : 0.0;

        Database::update('delivery_men', [
            'rating'        => $avg,
            'reviews_count' => $count
        ], 'id = ?', [$dmId]);
    }

    /**
     * Sync all store ratings across database
     */
    public static function syncAllRatings(): void
    {
        $stores = Database::query("SELECT id FROM `stores`");
        $reviewInst = new self();
        foreach ($stores as $s) {
            $reviewInst->recalculateStoreRating((int)$s['id']);
        }
        $drivers = Database::query("SELECT id FROM `delivery_men`");
        foreach ($drivers as $d) {
            $reviewInst->recalculateDmRating((int)$d['id']);
        }
    }
}
