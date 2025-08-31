<?php
// Enhanced transcription handler with business intelligence
$from = $_POST['From'];
$transcriptionText = $_POST['TranscriptionText'];
$recordingUrl = $_POST['RecordingUrl'];
$callSid = $_POST['CallSid'];

// Process the request through business handler
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

http_response_code(200);
echo "OK";
?>