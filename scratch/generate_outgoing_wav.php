<?php
// Generate ultra-soft outgoing dial tone WAV audio asset for CicalengkaGO
$outputDir = __DIR__ . '/../public/assets/audio';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$sampleRate = 44100;
$pulseDuration = 0.9; // 0.9s tone pulse
$silenceDuration = 2.3; // 2.3s silence
$totalDuration = $pulseDuration + $silenceDuration;

$data = '';

// 1. Tone pulse
$pulseSamples = (int)($sampleRate * $pulseDuration);
for ($i = 0; $i < $pulseSamples; $i++) {
    $t = $i / $sampleRate;
    
    // Extremely smooth bell envelope (Attack-Decay)
    $env = 1.0;
    if ($t < 0.08) {
        $env = $t / 0.08;
    } else {
        $env = exp(-4.0 * ($t - 0.08));
    }
    
    // Warm dual harmonic frequencies (349.23 Hz F4 + 440.00 Hz A4 - Warm F major dyad)
    $s1 = sin(2 * M_PI * 349.23 * $t);
    $s2 = sin(2 * M_PI * 440.00 * $t);
    
    // Amplitude set to ultra-low peak (0.035 out of 1.0 => -29dB ultra gentle)
    $sample = ($s1 + $s2) * 0.5 * $env * 0.035;
    
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

file_put_contents($outputDir . '/outgoing.wav', $header . $data);
echo "Successfully generated ultra-soft outgoing.wav audio file! (Size: " . strlen($header . $data) . " bytes)\n";
