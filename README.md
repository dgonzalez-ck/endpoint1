# 🛡️ Blacklist API

Un endpoint en **PHP** que permite verificar si un **correo electrónico** o **número de teléfono** se encuentra registrado en una lista negra.  
Devuelve una respuesta en formato **JSON** indicando si el valor está bloqueado o no.

---

## 🚀 Uso rápido

**Método:** `GET`  
**Archivo:** `check_blacklist.php`  
**Parámetro:** `valor` — correo electrónico o número telefónico a validar.

### 🔹 Ejemplo de consulta

**Verificar un correo:**
```bash
GET /check_blacklist.php?valor=usuario@correo.com
