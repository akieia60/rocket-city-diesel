<?php
// Voicemail transcription handler
$from = $_POST['From'];
$transcriptionText = $_POST['TranscriptionText'];
$recordingUrl = $_POST['RecordingUrl'];
$callSid = $_POST['CallSid'];

// Send email notification
$to = 'akieiadavis@gmail.com';
$subject = '🚛 ROCKET CITY DIESEL - New Service Request';
$message = "
NEW EMERGENCY DIESEL SERVICE REQUEST:

Customer Phone: $from
Call Time: " . date('Y-m-d H:i:s') . "
Message: $transcriptionText

Recording: $recordingUrl
Call ID: $callSid

ACTION REQUIRED: Call customer back immediately at $from

This is an automated message from Rocket City Diesel.
";

$headers = 'From: noreply@rocket-city-diesel.vercel.app' . "\r\n" .
    'Reply-To: akieiadavis@gmail.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);

// Log the call
$log = date('Y-m-d H:i:s') . " | $from | $transcriptionText\n";
file_put_contents('call_log.txt', $log, FILE_APPEND);

http_response_code(200);
echo "OK";
?>