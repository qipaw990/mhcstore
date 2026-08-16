<?php
/**
 * Distance Calculation (Haversine Formula)
 */

function calculate_distance(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371; // Earth radius in kilometers

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return round($distance, 2);
}

function haversine_distance(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    return calculate_distance($lat1, $lon1, $lat2, $lon2);
}

function calculate_delivery_fee(float $distanceKm, float $minFee = 5000.00, float $perKm = 2500.00): float
{
    if ($distanceKm <= 2.0) {
        return $minFee;
    }
    $extraKm = $distanceKm - 2.0;
    return round($minFee + ($extraKm * $perKm));
}
