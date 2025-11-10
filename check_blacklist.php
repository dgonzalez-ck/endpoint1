<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// ------------------------------------------------------------
// 1️⃣ Cargar variables del entorno (.env)
// ------------------------------------------------------------
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// ------------------------------------------------------------
// 2️⃣ Función para enviar alerta vía Botmaker (INTENT)
// ------------------------------------------------------------
function sendWhatsAppAlert($errorMessage)
{
    $apiUrl     = getenv('BOTMAKER_API_URL');
    $token      = getenv('BOTMAKER_TOKEN');
    $channelId  = getenv('BOTMAKER_CHANNEL_ID');
    $numbers    = explode(',', getenv('BOTMAKER_ALERT_NUMBERS'));
    $intentName = getenv('BOTMAKER_INTENT_NAME');

    foreach ($numbers as $contactId) {
        $payload = json_encode([
            "chat" => [
                "channelId" => $channelId,
                "contactId" => trim($contactId)
            ],
            "intentIdOrName" => $intentName,
            "webhookPayload" => "alerta_bd_" . date('Y-m-d_H:i:s') . " | $errorMessage"
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "access-token: $token"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        // Log de debug
        file_put_contents('botmaker_log.txt',
            date('Y-m-d H:i:s') . "\n".
            "Payload: $payload\n".
            "HTTP: $httpCode\n".
            "Curl Error: $error\n".
            "Response: $response\n\n",
            FILE_APPEND
        );
    }
}

// ------------------------------------------------------------
// 3️⃣ Intentar conexión a base de datos
// ------------------------------------------------------------
try {
    require_once "db_config.php";
} catch (Exception $e) {
    $msg = "No se pudo conectar con la base de datos: " . $e->getMessage();
    sendWhatsAppAlert($msg);

    // Esperar 2 segundos para que cURL termine antes de salir
    sleep(2);

    echo json_encode([
        "error" => true,
        "message" => "Error de conexión con la base de datos"
    ]);
    exit;
}

// ------------------------------------------------------------
// 4️⃣ Tu código de consulta (sin tocar)
// ------------------------------------------------------------
$valorInput = isset($_GET['valor']) ? trim($_GET['valor']) : null;
$response = false;

try {
    if ($valorInput) {
        if (filter_var($valorInput, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT 1 FROM Lista_negra WHERE FIND_IN_SET(:valor, Correos) AND Deleted = 0 LIMIT 1";
            $params = [":valor" => $valorInput];
        } else {
            $phoneClean = preg_replace('/\D/', '', $valorInput);
            $sql = "SELECT 1 FROM Lista_negra WHERE FIND_IN_SET(:valor, Telefonos) AND Deleted = 0 LIMIT 1";
            $params = [":valor" => $phoneClean];
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $response = true;
        }
    }

    echo json_encode($response);
} catch (PDOException $e) {
    $msg = "Error SQL o de conexión: " . $e->getMessage();
    sendWhatsAppAlert($msg);
    sleep(2);
    echo json_encode([
        "error" => true,
        "message" => "No se pudo ejecutar la consulta en la base de datos"
    ]);
}

$conn = null;
?>
