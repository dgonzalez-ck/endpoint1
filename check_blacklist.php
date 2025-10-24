<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once "db_config.php";

// Obtiene el valor único desde el parámetro GET
$valorInput = isset($_GET['valor']) ? trim($_GET['valor']) : null;

// response genérico
$response = ["blocked" => false];

if ($valorInput) {

  // Determinar si el valor es un correo electrónico o un número
  if (filter_var($valorInput, FILTER_VALIDATE_EMAIL)) {
    //  Buscar en columna 'Correos'
    $sql = "SELECT 1 FROM Lista_negra WHERE FIND_IN_SET(:valor, Correos) AND Deleted = 0 LIMIT 1";
    $params = [":valor" => $valorInput];
  } else {
    //  Buscar en columna 'Telefonos'
    // Limpia el valor (quita +, espacios, guiones, etc.)
    $phoneClean = preg_replace('/\D/', '', $valorInput);
    $sql = "SELECT 1 FROM Lista_negra WHERE FIND_IN_SET(:valor, Telefonos) AND Deleted = 0 LIMIT 1";
    $params = [":valor" => $phoneClean];
  }

  // Ejecuta la consulta preparada
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);

  // Si hay resultados, marcar como bloqueado
  if ($stmt->rowCount() > 0) {
    $response["blocked"] = true;
  }
}

// Devuelve respuesta JSON
echo json_encode($response);

// Cierra conexión PDO
$conn = null;
?>
