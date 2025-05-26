<?php
/**
 * Smart Meter P1 Port Reader for Raspberry Pi
 * 
 * This script reads data from a P1 port of a smart meter connected to a Raspberry Pi,
 * and sends it to the energy dashboard server API.
 * 
 * Requirements:
 * - PHP 7.4 or higher
 * - P1 cable connected to USB port on Raspberry Pi
 * - Correct permissions to access serial port
 * 
 * Usage: php p1_reader.php
 */

// Configuration
$config = [
    'api_url' => 'https://yourdomain.com/api/meter-data', // Replace with your server URL
    'api_key' => 'YOUR_API_KEY', // Replace with your API key if used
    'meter_id' => 'XMX5LGBBFG1009349343', // Replace with your meter ID
    'serial_port' => '/dev/ttyUSB0', // Default P1 port on Raspberry Pi (may need to be adjusted)
    'baud_rate' => 115200, // For DSMR 5.0
    'log_file' => __DIR__ . '/p1_reader.log',
    'sleep_between_readings' => 60, // Seconds between readings
];

// Set up logging
function logMessage($message, $level = 'INFO') {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($config['log_file'], $logMessage, FILE_APPEND);
    
    if ($level == 'ERROR' || $level == 'INFO') {
        echo $logMessage;
    }
}

logMessage("Starting P1 reader script");

// Verify we can write to the log file
if (!is_writable(dirname($config['log_file']))) {
    die("Cannot write to log directory: " . dirname($config['log_file']));
}

// Open serial port with the correct settings for DSMR 5.0
// (8N1, 115200 baud)
function openSerialPort($port, $baudRate) {
    logMessage("Opening serial port: $port at $baudRate baud");
    
    // Attempt to open the serial port
    $handle = @fopen($port, 'r');
    
    if (!$handle) {
        logMessage("Failed to open serial port: $port", 'ERROR');
        throw new Exception("Could not open serial port: $port");
    }
    
    // Set port settings
    $result = exec("stty -F $port $baudRate cs8 -cstopb -parenb");
    
    if ($result === false) {
        logMessage("Failed to configure serial port", 'ERROR');
        fclose($handle);
        throw new Exception("Could not configure serial port: $port");
    }
    
    logMessage("Serial port opened successfully");
    return $handle;
}

// Parse P1 telegram
function readP1Telegram($handle) {
    logMessage("Reading P1 telegram...", 'DEBUG');
    
    $telegram = '';
    $startFound = false;
    $endFound = false;
    
    // Read from serial port until we have a complete telegram
    while (!$endFound) {
        // Read a line from the serial port
        $line = fgets($handle);
        
        if ($line === false) {
            // Handle read error
            logMessage("Error reading from serial port", 'ERROR');
            return null;
        }
        
        // Check for telegram start (/) or end (!)
        if (strpos($line, '/') === 0) {
            $startFound = true;
            $telegram = '';
        }
        
        if ($startFound) {
            $telegram .= $line;
        }
        
        if (strpos($line, '!') === 0) {
            $endFound = true;
        }
    }
    
    logMessage("Complete telegram received (" . strlen($telegram) . " bytes)", 'DEBUG');
    return $telegram;
}

// Format data as JSON in the expected format
function formatJsonData($telegram, $meterId) {
    logMessage("Formatting telegram as JSON", 'DEBUG');
    
    // Create JSON structure similar to the example
    $data = [
        'datagram' => [
            'p1' => $telegram,
            'signature' => '2019-ETI- ',
            's0' => [
                'unit' => 'W',
                'label' => 'e-car charger',
                'value' => 0
            ],
            's1' => [
                'unit' => 'W',
                'label' => 'solar panels',
                'value' => 0
            ]
        ]
    ];
    
    return json_encode($data);
}

// Send data to API
function sendToApi($url, $meterId, $jsonData, $apiKey = null) {
    logMessage("Sending data to API: $url", 'DEBUG');
    
    $ch = curl_init();
    
    $postData = [
        'meter_id' => $meterId,
        'data' => $jsonData
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Add API key header if provided
    if ($apiKey) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        logMessage("API request successful: HTTP $httpCode");
        return true;
    } else {
        logMessage("API request failed: HTTP $httpCode, Response: $response", 'ERROR');
        return false;
    }
}

// Main execution loop
try {
    while (true) {
        try {
            // Open serial port
            $serialHandle = openSerialPort($config['serial_port'], $config['baud_rate']);
            
            // Read telegram
            $telegram = readP1Telegram($serialHandle);
            
            if ($telegram) {
                // Format as JSON
                $jsonData = formatJsonData($telegram, $config['meter_id']);
                
                // Send to API
                $success = sendToApi(
                    $config['api_url'], 
                    $config['meter_id'], 
                    $jsonData, 
                    $config['api_key']
                );
                
                if ($success) {
                    logMessage("Successfully processed and sent meter data");
                } else {
                    logMessage("Failed to send meter data to API", 'ERROR');
                }
            } else {
                logMessage("No valid telegram received", 'ERROR');
            }
            
            // Close serial port
            fclose($serialHandle);
            
        } catch (Exception $e) {
            logMessage("Error: " . $e->getMessage(), 'ERROR');
        }
        
        // Sleep before next reading
        logMessage("Sleeping for {$config['sleep_between_readings']} seconds", 'DEBUG');
        sleep($config['sleep_between_readings']);
    }
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage(), 'ERROR');
    exit(1);
}