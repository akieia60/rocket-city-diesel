<?php
// Rocket City Diesel - Business Operations Handler
// Processes incoming diesel repair requests and manages the business flow

header('Content-Type: application/json');

$customerPhone = $_POST['From'] ?? '';
$callSid = $_POST['CallSid'] ?? '';
$transcription = $_POST['TranscriptionText'] ?? '';
$recordingUrl = $_POST['RecordingUrl'] ?? '';

// Extract key information from customer request
function analyzeRequest($transcription, $phone) {
    $urgency = 'normal';
    $serviceType = 'general repair';
    $location = 'unknown';
    
    // Analyze urgency
    if (preg_match('/broke down|stranded|emergency|stuck|won\'t start/i', $transcription)) {
        $urgency = 'emergency';
    }
    
    // Identify service type
    if (preg_match('/engine|motor/i', $transcription)) {
        $serviceType = 'engine repair';
    } elseif (preg_match('/tire|flat/i', $transcription)) {
        $serviceType = 'tire service';
    } elseif (preg_match('/battery|won\'t start/i', $transcription)) {
        $serviceType = 'electrical/battery';
    } elseif (preg_match('/hydraulic|lift/i', $transcription)) {
        $serviceType = 'hydraulic service';
    }
    
    // Extract location
    if (preg_match('/(I-\d+|Highway \d+|Route \d+|Mile \d+)/i', $transcription, $matches)) {
        $location = $matches[0];
    }
    if (preg_match('/(Huntsville|Madison|Decatur|Athens)/i', $transcription, $matches)) {
        $location .= ($location ? ', ' : '') . $matches[0];
    }
    
    return [
        'urgency' => $urgency,
        'service_type' => $serviceType,
        'location' => $location,
        'estimated_price' => calculatePrice($serviceType, $urgency)
    ];
}

function calculatePrice($serviceType, $urgency) {
    $basePrice = 150;
    
    switch($serviceType) {
        case 'engine repair': $basePrice = 250; break;
        case 'hydraulic service': $basePrice = 300; break;
        case 'tire service': $basePrice = 125; break;
        case 'electrical/battery': $basePrice = 175; break;
    }
    
    if ($urgency === 'emergency') {
        $basePrice *= 1.5; // Emergency surcharge
    }
    
    return $basePrice;
}

// Process the request
$analysis = analyzeRequest($transcription, $customerPhone);

// Send immediate text response to customer
$customerMessage = "🚛 ROCKET CITY DIESEL: We received your {$analysis['service_type']} request. ";
$customerMessage .= "Estimated cost: \${$analysis['estimated_price']}. ";
$customerMessage .= ($analysis['urgency'] === 'emergency') ? "EMERGENCY PRIORITY - Dispatching immediately!" : "Technician will call within 30 minutes.";

// Send notification to you (the owner)
$ownerNotification = "
🚨 NEW DIESEL SERVICE REQUEST

Customer: {$customerPhone}
Service: {$analysis['service_type']}
Urgency: {$analysis['urgency']}
Location: {$analysis['location']}
Estimated Price: \${$analysis['estimated_price']}

Customer Message: {$transcription}

ACTION REQUIRED:
1. Call customer immediately: {$customerPhone}
2. Confirm location and service needed
3. Provide arrival time estimate
4. Collect payment upon completion

Recording: {$recordingUrl}
";

// Send text to customer
$twilioSid = getenv('TWILIO_ACCOUNT_SID');
$twilioToken = getenv('TWILIO_AUTH_TOKEN');

file_get_contents("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Authorization: Basic ' . base64_encode("{$twilioSid}:{$twilioToken}"),
        'content' => http_build_query([
            'From' => '+12568702467',
            'To' => $customerPhone,
            'Body' => $customerMessage
        ])
    ]
]));

// Email you the details
mail('akieiadavis@gmail.com', '🚛 DIESEL SERVICE REQUEST - $' . $analysis['estimated_price'], $ownerNotification, 'From: dispatch@rocket-city-diesel.vercel.app');

// Log the request
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'customer' => $customerPhone,
    'service' => $analysis['service_type'],
    'urgency' => $analysis['urgency'],
    'price' => $analysis['estimated_price'],
    'location' => $analysis['location'],
    'transcription' => $transcription
];

file_put_contents('service_requests.json', json_encode($logEntry) . "\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'analysis' => $analysis,
    'customer_notified' => true,
    'owner_notified' => true
]);
?>