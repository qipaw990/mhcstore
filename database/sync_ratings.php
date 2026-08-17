<?php
require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Review.php';

use App\Models\Review;

try {
    echo "Syncing all store and driver ratings...\n";
    Review::syncAllRatings();
    echo "Ratings synchronized successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
