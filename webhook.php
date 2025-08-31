<?php
// Twilio Call Forwarding Webhook for Rocket City Diesel
header('Content-Type: text/xml');

$from = $_POST['From'];
$to = $_POST['To'];

// Your personal phone number for forwarding
$forwardTo = '+13126623933';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Say voice="alice">Thank you for calling Rocket City Diesel Emergency Service. Your call is being connected to our dispatch team.</Say>
    <Dial callerId="<?php echo $to; ?>" timeout="30">
        <Number><?php echo $forwardTo; ?></Number>
    </Dial>
    <Say voice="alice">We're currently assisting other customers. Please leave your name, phone number, and location after the tone, and we'll call you back within 15 minutes.</Say>
    <Record timeout="60" transcribe="true" transcribeCallback="https://rocket-city-diesel.vercel.app/transcribe.php"/>
</Response>