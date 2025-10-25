<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// ------------------------------------------------------------
// 1️⃣ Función auxiliar para enviar alerta por WhatsApp
// ------------------------------------------------------------
function sendWhatsAppAlert($message)
{
    // 🔹 Configuración de Twilio (reemplazá con tus credenciales reales)
    $twilioSid = "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";  // SID de tu cuenta
    $twilioToken = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";   // Token de autenticación
    $twilioNumber = "whatsapp:+14155238886";             // Número de Twilio (sandbox o verificado)

    // 🔹 Números a los que se enviará la alerta (separados por comas)
    $alertNumbers = [
        "whatsapp:+573001112233",
        "whatsapp:+573154221133"
    ];

    foreach ($alertNumbers as $to) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/$twilioSid/Messages.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$twilioSid:$twilioToken");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "From" => $twilioNumber,
            "To" => $to,
            "Body" => "⚠️ Alerta: $message"
        ]));

        curl_exec($ch);
        curl_close($ch);
    }
}

// ------------------------------------------------------------
// 2️⃣ Intentar cargar la conexión a la base de datos
// ------------------------------------------------------------
try {
    require_once "db_config.php";
} catch (Exception $e) {
    // 🚨 Si falla la conexión, enviar alerta y responder error
    $errorMsg = "No se pudo conectar con la base de datos: " . $e->getMessage();

    // Enviar mensaje de alerta por WhatsApp
    sendWhatsAppAlert($errorMsg);

    // Devolver respuesta JSON de error
    echo json_encode([
        "error" => true,
        "message" => "Error de conexión con la base de datos"
    ]);
    exit;
}

// ------------------------------------------------------------
// 3️⃣ Procesamiento normal del endpoint (si la DB está disponible)
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
    // 🚨 Si hay error en la consulta o pérdida de conexión
    $errorMsg = "Error SQL o de conexión: " . $e->getMessage();

    // Enviar alerta
    sendWhatsAppAlert($errorMsg);

    // Devolver respuesta JSON de error
    echo json_encode([
        "error" => true,
        "message" => "No se pudo ejecutar la consulta en la base de datos"
    ]);
}

$conn = null;
?>
