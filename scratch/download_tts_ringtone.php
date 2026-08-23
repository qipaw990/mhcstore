<?php
// Download AI Voice TTS for CicalengkaGO Ringtone
$outputDir = __DIR__ . '/../public/assets/audio';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$text = "Ada panggilan telepon masuk dari Cicalengka GO. Cicalengka GO!";
$url = "https://translate.google.com/translate_tts?ie=UTF-8&q=" . urlencode($text) . "&tl=id&client=tw-ob";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$audioData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && !empty($audioData)) {
    file_put_contents($outputDir . '/ringtone_voice.mp3', $audioData);
    echo "Successfully saved AI voice ringtone to {$outputDir}/ringtone_voice.mp3 (Size: " . strlen($audioData) . " bytes)\n";
} else {
    echo "Failed to fetch TTS audio, HTTP code: {$httpCode}\n";
}
