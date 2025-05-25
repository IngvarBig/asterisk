<?php
// === Configuration ===
// Move these settings to a separate .env or config file in production
$ipServer = "localhost";       // AMI host
$port = "5038";                // AMI port
$username = "your_ami_user";   // AMI username
$password = "your_ami_secret"; // AMI password
$logFile = "/var/log/click2call.log"; // Log file path
$context = "set-static-callerid";     // Asterisk context
$trunkPrefix = "PJSIP";               // Change to SIP/IAX2 if needed

// === Optional IP Whitelist ===
// $allowed_ips = ['127.0.0.1', 'x.x.x.x'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
//     handleError("Your IP address is not authorized.", 403);
// }

// === Input ===
$leg_a = $_GET['leg_a'] ?? null;
$leg_b = $_GET['leg_b'] ?? null;
$route_number = $_GET['route_number'] ?? 'default';

// === Normalize ===
$leg_b = preg_replace('/^00/', '', $leg_b);
$leg_b = ltrim($leg_b, '+');

// === Validate ===
if (!preg_match('/^\d+$/', $leg_a)) {
    handleError("Invalid format: leg_a must be digits only.", 400);
}
if (!preg_match('/^\d+$/', $leg_b)) {
    handleError("Invalid format: leg_b must be digits only.", 400);
}

// === CallerID ===
$callerid = "\"Click2Call\" <{$leg_a}>";

try {
    $socket = fsockopen($ipServer, $port, $errno, $errstr, 10);
    if (!$socket) {
        throw new Exception("Unable to connect to AMI: $errstr ($errno)");
    }

    // Login
    fputs($socket, "Action: Login\r\n");
    fputs($socket, "Username: $username\r\n");
    fputs($socket, "Secret: $password\r\n");
    fputs($socket, "Events: off\r\n\r\n");

    // Originate call
    fputs($socket, "Action: Originate\r\n");
    fputs($socket, "Channel: {$trunkPrefix}/{$leg_a}\r\n");
    fputs($socket, "Exten: {$leg_b}\r\n");
    fputs($socket, "Context: {$context}\r\n");
    fputs($socket, "Priority: 1\r\n");
    fputs($socket, "CallerID: {$callerid}\r\n");
    fputs($socket, "Variable: ROUTE={$route_number}\r\n");
    fputs($socket, "Async: yes\r\n\r\n");

    // Wait for events (up to 15s)
    $timeout = 15;
    $start = time();
    $response = "";

    while (!feof($socket) && (time() - $start) < $timeout) {
        $line = fgets($socket, 128);
        if (stripos($line, "Response: Success") !== false || stripos($line, "Message: Authentication accepted") !== false) {
            $response .= $line;
        }
        if (stripos($line, "Event: Hangup") !== false) {
            $response .= "Call hangup detected.\n";
            break;
        }
    }
} catch (Exception $e) {
    handleError("Exception: " . $e->getMessage(), 500);
} finally {
    if (isset($socket) && is_resource($socket)) {
        fclose($socket);
    }
}

// === Log the event ===
logEvent([
    'timestamp'    => date('Y-m-d H:i:s'),
    'leg_a'        => $leg_a,
    'leg_b'        => $leg_b,
    'route_number' => $route_number,
    'response'     => $response,
    'remote_ip'    => $_SERVER['REMOTE_ADDR']
]);

// === Output ===
echo nl2br(htmlspecialchars($response));

// === Helpers ===
function handleError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function logEvent($data) {
    global $logFile;
    file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
}
?>
