// Copiar este archivo como "secrets.h" (en la misma carpeta) y completar los datos.
// secrets.h está en .gitignore: NUNCA se sube al repositorio.

#pragma once

// Configuración del proyecto > General > Clave de API web
#define FIREBASE_API_KEY "tu-api-key"

// URL de la Realtime Database (pestaña "Datos")
#define FIREBASE_URL "https://tu-proyecto-default-rtdb.firebaseio.com"

// Usuario creado para el dispositivo en Authentication > Usuarios (método Correo/contraseña)
#define FIREBASE_USER_EMAIL "esp32@tu-dominio.com"
#define FIREBASE_USER_PASSWORD "una-contraseña-larga"

// Dirección donde está publicado el panel (se usa para armar el link del QR de la etiqueta)
#define PANEL_URL "http://localhost:8000"
