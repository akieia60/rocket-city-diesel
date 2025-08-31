<?php
// Rocket City Diesel - Service Network & Partner Management System
// Connects customers to qualified diesel mechanics and suppliers

header('Content-Type: application/json');

// Network of diesel mechanics and service providers in Huntsville area
$serviceProviders = [
    'emergency_diesel' => [
        [
            'name' => 'Mobile Diesel Solutions',
            'phone' => '+12564895432',
            'email' => 'dispatch@mobiledieselsolutions.com',
            'services' => ['engine_repair', 'hydraulic_service', 'emergency_roadside'],
            'coverage' => 'Huntsville Metro',
            'response_time' => '45-60 minutes',
            'pricing' => ['base' => 175, 'emergency_multiplier' => 1.6],
            'available_24_7' => true
        ],
        [
            'name' => 'Southern Truck Services',
            'phone' => '+12568734521',
            'email' => 'service@southerntruckservices.com',
            'services' => ['engine_repair', 'transmission', 'brake_service'],
            'coverage' => 'Madison County',
            'response_time' => '30-45 minutes',
            'pricing' => ['base' => 150, 'emergency_multiplier' => 1.4],
            'available_24_7' => false
        ]
    ],
    'tire_service' => [
        [
            'name' => 'Highway Tire Emergency',
            'phone' => '+12565678901',
            'email' => 'emergency@highwaytire.com',
            'services' => ['tire_repair', 'tire_replacement', 'roadside_tire'],
            'coverage' => 'I-65, I-565, US-72 corridors',
            'response_time' => '30-40 minutes',
            'pricing' => ['base' => 125, 'emergency_multiplier' => 1.3],
            'available_24_7' => true
        ]
    ],
    'parts_suppliers' => [
        [
            'name' => 'Diesel Parts Direct',
            'phone' => '+12567891234',
            'email' => 'orders@dieselpartsdirect.com',
            'services' => ['parts_delivery', 'emergency_parts'],
            'delivery_time' => '2-4 hours',
            'markup' => 1.35
        ]
    ]
];

function findBestProvider($serviceType, $urgency, $location) {
    global $serviceProviders;
    
    $category = '';
    switch($serviceType) {
        case 'tire service':
            $category = 'tire_service';
            break;
        case 'engine repair':
        case 'hydraulic service': 
        case 'electrical/battery':
            $category = 'emergency_diesel';
            break;
        default:
            $category = 'emergency_diesel';
    }
    
    if (!isset($serviceProviders[$category])) {
        return null;
    }
    
    // Find available provider
    foreach ($serviceProviders[$category] as $provider) {
        if ($urgency === 'emergency' && !$provider['available_24_7']) {
            continue;
        }
        return $provider;
    }
    
    return $serviceProviders[$category][0]; // Return first available
}

function dispatchService($customerPhone, $serviceType, $urgency, $location, $transcription) {
    $provider = findBestProvider($serviceType, $urgency, $location);
    
    if (!$provider) {
        return ['success' => false, 'error' => 'No providers available'];
    }
    
    // Calculate pricing
    $basePrice = $provider['pricing']['base'];
    if ($urgency === 'emergency') {
        $basePrice *= $provider['pricing']['emergency_multiplier'];
    }
    
    // Add your markup (25% commission)
    $customerPrice = round($basePrice * 1.25);
    $yourCommission = $customerPrice - $basePrice;
    
    // Create dispatch notification
    $dispatchMessage = "
🚛 ROCKET CITY DIESEL - SERVICE DISPATCH

CUSTOMER REQUEST:
Phone: {$customerPhone}
Service: {$serviceType}
Urgency: {$urgency}
Location: {$location}
Message: {$transcription}

PROVIDER ASSIGNED: {$provider['name']}
Contact: {$provider['phone']}
Response Time: {$provider['response_time']}

PRICING:
Customer Pays: \\${$customerPrice}
Provider Gets: \\${$basePrice}
Your Commission: \\${$yourCommission}

Please respond immediately to customer: {$customerPhone}
";

    // Notify the service provider
    $twilioSid = getenv('TWILIO_ACCOUNT_SID');
    $twilioToken = getenv('TWILIO_AUTH_TOKEN');
    
    if ($twilioSid && $twilioToken) {
        // Send SMS to provider
        file_get_contents("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Authorization: Basic ' . base64_encode("{$twilioSid}:{$twilioToken}"),
                'content' => http_build_query([
                    'From' => '+12568702467',
                    'To' => $provider['phone'],
                    'Body' => "🚛 URGENT DISPATCH: {$serviceType} needed at {$location}. Customer: {$customerPhone}. Rate: \\${$basePrice}. Respond ASAP!"
                ])
            ]
        ]));
    }
    
    // Email dispatch details
    mail('akieiadavis@gmail.com', '💰 SERVICE DISPATCHED - $' . $yourCommission . ' COMMISSION', $dispatchMessage, 'From: dispatch@rocket-city-diesel.vercel.app');
    
    return [
        'success' => true,
        'provider' => $provider['name'],
        'customer_price' => $customerPrice,
        'your_commission' => $yourCommission,
        'response_time' => $provider['response_time']
    ];
}

// Handle missed call follow-up
function handleMissedCall($customerPhone, $serviceType, $urgency, $location, $transcription) {
    // Dispatch to service provider
    $dispatch = dispatchService($customerPhone, $serviceType, $urgency, $location, $transcription);
    
    if ($dispatch['success']) {
        $customerMessage = "🚛 ROCKET CITY DIESEL: We've dispatched {$dispatch['provider']} for your {$serviceType}. ";
        $customerMessage .= "Total cost: \\${$dispatch['customer_price']}. ";
        $customerMessage .= "Technician will arrive in {$dispatch['response_time']}. ";
        $customerMessage .= "They'll call you shortly at this number.";
        
        // Send confirmation to customer
        $twilioSid = getenv('TWILIO_ACCOUNT_SID');
        $twilioToken = getenv('TWILIO_AUTH_TOKEN');
        
        if ($twilioSid && $twilioToken) {
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
        }
        
        // Log the successful dispatch
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'missed_call_dispatch',
            'customer' => $customerPhone,
            'service' => $serviceType,
            'provider' => $dispatch['provider'],
            'customer_price' => $dispatch['customer_price'],
            'commission' => $dispatch['your_commission'],
            'status' => 'dispatched'
        ];
        
        file_put_contents('dispatch_log.json', json_encode($logEntry) . "\n", FILE_APPEND);
    }
    
    return $dispatch;
}

// Process incoming request
if ($_POST) {
    $result = handleMissedCall(
        $_POST['From'],
        $_POST['service_type'] ?? 'general repair',
        $_POST['urgency'] ?? 'normal',
        $_POST['location'] ?? 'Huntsville area',
        $_POST['TranscriptionText'] ?? ''
    );
    
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'No data provided']);
}
?>