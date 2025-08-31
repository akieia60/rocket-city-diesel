<?php
// Enhanced transcription handler with business intelligence and missed call dispatch
$from = $_POST['From'];
$transcriptionText = $_POST['TranscriptionText'];
$recordingUrl = $_POST['RecordingUrl'];
$callSid = $_POST['CallSid'];

// Process the request through business handler first
$postData = http_build_query([
    'From' => $from,
    'TranscriptionText' => $transcriptionText,
    'RecordingUrl' => $recordingUrl,
    'CallSid' => $callSid
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

// Call the business handler
$result = file_get_contents('https://rocket-city-diesel.vercel.app/business-handler.php', false, $context);

// Also trigger the service network dispatch for missed calls
$serviceData = http_build_query([
    'From' => $from,
    'TranscriptionText' => $transcriptionText,
    'RecordingUrl' => $recordingUrl,
    'CallSid' => $callSid
]);

$serviceContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $serviceData
    ]
]);

// Dispatch to service network
file_get_contents('https://rocket-city-diesel.vercel.app/service-network.php', false, $serviceContext);

http_response_code(200);
echo "OK";
?>