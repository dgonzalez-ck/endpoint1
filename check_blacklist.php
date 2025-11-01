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
// 2️⃣ Función para enviar alerta vía Botmaker
// ------------------------------------------------------------
function sendWhatsAppAlert($message)
{
    $apiUrl = getenv('BOTMAKER_API_URL');
    $token = getenv('BOTMAKER_TOKEN');
    $numbers = explode(',', getenv('BOTMAKER_ALERT_NUMBERS'));

    if (!$apiUrl || !$token || empty($numbers)) {
        error_log("⚠️ Botmaker no configurado correctamente en .env");
        return;
    }

    foreach ($numbers as $number) {
        $payload = json_encode([
            "platform" => "whatsapp",
            "message" => "⚠️ Alerta: $message",
            "to" => trim($number)
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log("❌ Error enviando alerta Botmaker: " . curl_error($ch));
        } else {
            error_log("✅ Alerta enviada a $number: $response");
        }

        curl_close($ch);
    }
}

// ------------------------------------------------------------
// 3️⃣ Intentar conexión a base de datos
// ------------------------------------------------------------
try {
    require_once "db_config.php";
} catch (Exception $e) {
    $errorMsg = "No se pudo conectar con la base de datos: " . $e->getMessage();
    sendWhatsAppAlert($errorMsg);

    echo json_encode([
        "error" => true,
        "message" => "Error de conexión con la base de datos"
    ]);
    exit;
}

// ------------------------------------------------------------
// 4️⃣ Procesar el endpoint (si la DB está disponible)
// ------------------------------------------------------------
$valorInput = isset($_GET['valor']) ? trim($_GET['valor']) : null;
$response = false;

try {
    if ($valorInput) {
        if (filter_var($valorInput, FILTER_VALIDATE_EMAIL)) {
            // 📧 Buscar en columna 'Correos'
            $sql = "SELECT 1 FROM Lista_negra WHERE FIND_IN_SET(:valor, Correos) AND Deleted = 0 LIMIT 1";
            $params = [":valor" => $valorInput];
        } else {
            // 📱 Buscar en columna 'Telefonos'
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
    $errorMsg = "Error SQL o de conexión: " . $e->getMessage();
    sendWhatsAppAlert($errorMsg);

    echo json_encode([
        "error" => true,
        "message" => "No se pudo ejecutar la consulta en la base de datos"
    ]);
}

$conn = null;
?>
