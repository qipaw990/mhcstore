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
    /**
     * Submit review for an order (Store & Courier)
     */
    public function submitReview(
        int $orderId,
        int $userId,
        $storeRating,
        ?string $storeComment = '',
        ?int $dmRating = null,
        ?string $dmComment = '',
        ?array $multiStoreReviews = []
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

        // 1. Multi-store batch reviews (if array provided)
        if (!empty($multiStoreReviews)) {
            foreach ($multiStoreReviews as $ms) {
                $sId = (int)($ms['store_id'] ?? 0);
                $oId = (int)($ms['order_id'] ?? $orderId);
                $sRating = min(5, max(1, (int)($ms['rating'] ?? 5)));
                $sComment = sanitize($ms['comment'] ?? '');

                if ($sId) {
                    $existingStoreReview = Database::fetchOne(
                        "SELECT id FROM `reviews` WHERE `order_id` = ? AND `user_id` = ? AND `store_id` = ? LIMIT 1",
                        [$oId, $userId, $sId]
                    );

                    if ($existingStoreReview) {
                        Database::update('reviews', [
                            'rating'     => $sRating,
                            'comment'    => $sComment,
                            'created_at' => $now
                        ], 'id = ?', [$existingStoreReview['id']]);
                        $storeReviewId = $existingStoreReview['id'];
                    } else {
                        $storeReviewId = $this->create([
                            'order_id'   => $oId,
                            'user_id'    => $userId,
                            'store_id'   => $sId,
                            'rating'     => $sRating,
                            'comment'    => $sComment,
                            'created_at' => $now
                        ]);
                    }

                    $this->recalculateStoreRating($sId);
                }
            }
        } elseif (!empty($order['store_id'])) {
            // Single store review
            $storeId = (int)$order['store_id'];
            $existingStoreReview = Database::fetchOne(
                "SELECT id FROM `reviews` WHERE `order_id` = ? AND `user_id` = ? AND `store_id` = ? LIMIT 1",
                [$orderId, $userId, $storeId]
            );

            if ($existingStoreReview) {
                Database::update('reviews', [
                    'rating'     => min(5, max(1, (int)$storeRating)),
                    'comment'    => sanitize($storeComment ?? ''),
                    'created_at' => $now
                ], 'id = ?', [$existingStoreReview['id']]);
                $storeReviewId = $existingStoreReview['id'];
            } else {
                $storeReviewId = $this->create([
                    'order_id'   => $orderId,
                    'user_id'    => $userId,
                    'store_id'   => $storeId,
                    'rating'     => min(5, max(1, (int)$storeRating)),
                    'comment'    => sanitize($storeComment ?? ''),
                    'created_at' => $now
                ]);
            }

            $this->recalculateStoreRating($storeId);
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

            $this->recalculateDmRating($dmId);
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
        $order = Database::fetchOne("SELECT id, delivery_batch_id FROM `orders` WHERE `id` = ? LIMIT 1", [$orderId]);
        $orderIds = [$orderId];
        if ($order && !empty($order['delivery_batch_id'])) {
            $batchOrds = Database::query("SELECT id FROM `orders` WHERE `delivery_batch_id` = ?", [$order['delivery_batch_id']]);
            if (!empty($batchOrds)) {
                $orderIds = array_column($batchOrds, 'id');
            }
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $params = array_merge($orderIds, [$userId]);

        $reviews = Database::query(
            "SELECT r.*, s.name as store_name, s.logo as store_logo,
                    dmu.name as dm_name, dmu.avatar as dm_avatar,
                    dm.vehicle_type, dm.vehicle_number
             FROM `reviews` r
             LEFT JOIN `stores` s ON r.store_id = s.id
             LEFT JOIN `delivery_men` dm ON r.delivery_man_id = dm.id
             LEFT JOIN `users` dmu ON dm.user_id = dmu.id
             WHERE r.order_id IN ({$placeholders}) AND r.user_id = ?",
            $params
        );

        $result = [
            'has_reviewed'  => !empty($reviews),
            'store_review'  => null,
            'store_reviews' => [],
            'dm_review'     => null
        ];

        foreach ($reviews as $rev) {
            if (!empty($rev['store_id'])) {
                if ($result['store_review'] === null) {
                    $result['store_review'] = $rev;
                }
                $result['store_reviews'][] = $rev;
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

    public function getByDriverId(int $dmId, int $limit = 20): array
    {
        return $this->getDmReviews($dmId, $limit);
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
        if ($count > 0) {
            $avg = round((float)$stat['avg_rating'], 1);
            Database::update('stores', [
                'rating'        => $avg,
                'reviews_count' => $count
            ], 'id = ?', [$storeId]);
        }
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
        if ($count > 0) {
            $avg = round((float)$stat['avg_rating'], 1);
            Database::update('delivery_men', [
                'rating'        => $avg,
                'reviews_count' => $count
            ], 'id = ?', [$dmId]);
        }
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
