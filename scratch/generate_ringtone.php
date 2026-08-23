<?php
// Generate a high-quality PCM WAV phone ringtone for CicalengkaGO
$sampleRate = 44100;
$durationSec = 6.0;
$totalSamples = (int)($sampleRate * $durationSec);

$outputDir = __DIR__ . '/../public/assets/audio';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$filePath = $outputDir . '/ringtone.wav';

// Define dual-tone ring melody: E5 (659.25 Hz) + G#5 (830.61 Hz) / B5 (987.77 Hz)
$f1 = 659.25;
$f2 = 830.61;
$f3 = 987.77;

$data = '';

for ($i = 0; $i < $totalSamples; $i++) {
    $t = $i / $sampleRate;
    
    // Cycle pattern: 1.2s ring, 0.6s pause
    $cycle = fmod($t, 1.8);
    
    $sample = 0;
    if ($cycle < 1.2) {
        // Enveloping for smooth ring tone
        $env = sin(M_PI * ($cycle / 1.2));
        
        // Alternating melody notes every 0.3s within ring
        $subCycle = fmod($cycle, 0.6);
        if ($subCycle < 0.3) {
            $val = (sin(2 * M_PI * $f1 * $t) * 0.5) + (sin(2 * M_PI * $f2 * $t) * 0.5);
        } else {
            $val = (sin(2 * M_PI * $f2 * $t) * 0.5) + (sin(2 * M_PI * $f3 * $t) * 0.5);
        }
        
        $sample = $val * $env * 0.4;
    }
    
    // Convert float to 16-bit PCM integer (-32768 to 32767)
    $pcm = (int)max(-32768, min(32767, $sample * 32767));
    $data .= pack('v', $pcm); // 16-bit little-endian
}

$dataSize = strlen($data);
$header = '';

// RIFF header
$header .= 'RIFF';
$header .= pack('V', 36 + $dataSize); // ChunkSize
$header .= 'WAVE';

// fmt subchunk
$header .= 'fmt ';
$header .= pack('V', 16); // Subchunk1Size (16 for PCM)
$header .= pack('v', 1);  // AudioFormat (1 for PCM)
$header .= pack('v', 1);  // NumChannels (1 for Mono)
$header .= pack('V', $sampleRate); // SampleRate
$header .= pack('V', $sampleRate * 2); // ByteRate (SampleRate * NumChannels * BitsPerSample/8)
$header .= pack('v', 2);  // BlockAlign (NumChannels * BitsPerSample/8)
$header .= pack('v', 16); // BitsPerSample

// data subchunk
$header .= 'data';
$header .= pack('V', $dataSize);

file_put_contents($filePath, $header . $data);
echo "Successfully generated ringtone.wav at {$filePath} (Size: " . filesize($filePath) . " bytes)\n";
