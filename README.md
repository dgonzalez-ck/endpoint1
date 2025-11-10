# 📦 Monitor de Base de Datos con Alerta Automática vía Botmaker

Este proyecto ejecuta una verificación y consulta de registros en una base de datos MySQL.  
En caso de que la conexión falle o se produzca un error SQL, se envía automáticamente una **alerta por WhatsApp** a través de **Botmaker**.

---

## 🧠 Descripción general

El script tiene dos funciones principales:

1. **Verificación de conexión y consulta a MySQL**  
   - Comprueba que la base de datos esté accesible.  
   - Realiza una búsqueda en la tabla `Lista_negra` usando correo o número de teléfono.

2. **Notificación automática por WhatsApp (Botmaker)**  
   - Si falla la conexión o hay un error en la ejecución SQL, se dispara una alerta usando la API de Botmaker.  
   - Los destinatarios, canal y token se definen en el archivo `.env`.

---

## ⚙️ Requisitos técnicos

- PHP 7.4 o superior  
- Extensión **cURL** habilitada  
- Acceso a Internet (para comunicación con la API de Botmaker)  
- Servidor con permisos de escritura (para el archivo de logs)

---

## 🗂️ Estructura del proyecto

/project-root
│
├── consultor.php # Script principal: control de flujo, conexión y consulta
├── db_config.php # Configuración PDO y control de conexión a la BD
├── .env # Variables de entorno para Botmaker
└── botmaker_log.txt # Registro de eventos y respuestas del servicio Botmaker



---

## 🔧 Configuración inicial

### 1️⃣ Variables de entorno (`.env`)

Este archivo contiene las credenciales y parámetros necesarios para comunicarse con Botmaker:

| Variable | Descripción |
|-----------|-------------|
| `BOTMAKER_API_URL` | URL base del endpoint de Botmaker (`/v2.0/chats-actions/trigger-intent`) |
| `BOTMAKER_TOKEN` | Token de autenticación de la API |
| `BOTMAKER_CHANNEL_ID` | ID del canal WhatsApp configurado en Botmaker |
| `BOTMAKER_ALERT_NUMBERS` | Lista de números (separados por comas) a los que se enviará la alerta |
| `BOTMAKER_INTENT_NAME` | Nombre del **intent (template)** que Botmaker ejecutará para enviar el mensaje |

> ⚠️ Este archivo **no debe estar disponible públicamente** ni incluirse en control de versiones.

---

## 🧩 Flujo de ejecución

1. **Inicio del script (`consultor.php`):**  
   Carga las variables de entorno y define los encabezados de respuesta JSON.

2. **Intento de conexión a la base de datos:**  
   Incluye `db_config.php`.  
   - Si la conexión es exitosa, continúa a la consulta.  
   - Si falla, se ejecuta `sendWhatsAppAlert()` y se devuelve un error en formato JSON.

3. **Ejecución de consulta:**  
   - Recibe un parámetro `valor` vía `GET`.  
   - Detecta si es un correo o número de teléfono.  
   - Busca en la tabla `Lista_negra`.  
   - Devuelve `true` si existe, `false` si no.

4. **Manejo de errores SQL:**  
   Si se lanza una excepción durante la consulta, se genera una alerta a Botmaker con el mensaje del error.

5. **Registro en log:**  
   Todos los envíos realizados a Botmaker se guardan en `botmaker_log.txt` con el payload, código HTTP y respuesta recibida.

---

## 🔔 Envío de alertas

La función `sendWhatsAppAlert()` se ejecuta en dos casos:
- Falla de conexión con la base de datos.
- Error en la ejecución de una sentencia SQL.

**Funcionamiento:**
1. Lee los valores de `.env`.
2. Crea un `payload` con los datos del canal, contacto y mensaje.
3. Envía la solicitud POST a `BOTMAKER_API_URL`.
4. Registra el resultado en `botmaker_log.txt`.

> El sistema espera **2 segundos** después de enviar la alerta para garantizar que `curl_exec()` complete el envío antes de finalizar el proceso PHP.

---

## 📡 Ejemplo de comportamiento

| Escenario | Resultado esperado | Acción del sistema |
|------------|-------------------|--------------------|
| Conexión exitosa y registro encontrado | `true` | No se envía alerta |
| Conexión exitosa y sin coincidencia | `false` | No se envía alerta |
| Error de conexión a la BD | JSON con error + alerta por WhatsApp |
| Error SQL en ejecución | JSON con error + alerta por WhatsApp |

---
🚀 Uso rápido
Método: GET
Archivo: check_blacklist.php
Parámetro: valor — correo electrónico o número telefónico a validar.

🔹 Ejemplo de consulta
Verificar un correo:

GET /check_blacklist.php?valor=usuario@correo.com