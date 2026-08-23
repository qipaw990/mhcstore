<?php
// Generate clean standard telephone dial tone ("tuuut... tuuut...") matching user's audio request
$outputDir = __DIR__ . '/../public/assets/audio';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$sampleRate = 44100;
$pulseDuration = 1.0; // 1.0s tone pulse ("tuuut")
$silenceDuration = 2.5; // 2.5s pause
$totalDuration = $pulseDuration + $silenceDuration;

$data = '';

// 1. Tone pulse (425 Hz soft telephone dial tone)
$pulseSamples = (int)($sampleRate * $pulseDuration);
for ($i = 0; $i < $pulseSamples; $i++) {
    $t = $i / $sampleRate;
    
    // Very gentle envelope: 0.06s attack fade-in, 0.06s decay fade-out
    $env = 1.0;
    if ($t < 0.06) {
        $env = $t / 0.06;
    } elseif ($t > ($pulseDuration - 0.06)) {
        $env = ($pulseDuration - $t) / 0.06;
    }
    
    // Smooth 425Hz sine wave (Standard Telephone Tone)
    $s1 = sin(2 * M_PI * 425.0 * $t);
    
    // Soft amplitude (0.05 out of 1.0 = -26dB)
    $sample = $s1 * $env * 0.05;
    
    $pcm = (int)($sample * 32767);
    $pcm = max(-32768, min(32767, $pcm));
    $data .= pack('v', $pcm);
}

// 2. Silence gap
$silenceSamples = (int)($sampleRate * $silenceDuration);
for ($i = 0; $i < $silenceSamples; $i++) {
    $data .= pack('v', 0);
}

$dataSize = strlen($data);
$header = 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE';
$header .= 'fmt ' . pack('V', 16) . pack('v', 1) . pack('v', 1) . pack('V', $sampleRate) . pack('V', $sampleRate * 2) . pack('v', 2) . pack('v', 16);
$header .= 'data' . pack('V', $dataSize);

file_put_contents($outputDir . '/dialtone.wav', $header . $data);
echo "Successfully generated clean dialtone.wav audio asset! (Size: " . strlen($header . $data) . " bytes)\n";
