<?php
/**
 * Módulo Central de Autenticación y Seguridad
 * Maneja las sesiones, validación de roles y protección CSRF.
 */

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const ROL_ADMIN = 'administrador';
const ROL_VISOR = 'visor';

// Limpieza de texto para prevenir XSS en las vistas
function e($texto): string { 
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); 
}

// Relación entre la App Móvil y el Blog Web (tip_usu 2 = admin)
function rol_desde_tipo($tip_usu): string { 
    return ((int)$tip_usu === 2) ? ROL_ADMIN : ROL_VISOR; 
}

function esta_autenticado(): bool { 
    return isset($_SESSION['id_usuario']); 
}

function usuario_id(): ?int { 
    return esta_autenticado() ? (int)$_SESSION['id_usuario'] : null; 
}

function usuario_rol(): string { 
    return $_SESSION['rol'] ?? ''; 
}

function destino_por_rol(string $rol, string $prefijo = ''): string {
    return $prefijo . ($rol === ROL_ADMIN ? 'admin/panel.php' : 'visor/dashboard.php');
}

// Bloqueo de rutas
function requerir_rol(array $roles, string $url_login, string $url_denegado): void {
    if (!esta_autenticado()) { 
        header("Location: $url_login"); 
        exit(); 
    }
    if (!in_array(usuario_rol(), $roles, true)) { 
        header("Location: $url_denegado"); 
        exit(); 
    }
}

// PROTECCIÓN CONTRA FALSIFICACIÓN (CSRF)
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valido(): bool {
    $t = $_POST['csrf'] ?? '';
    return is_string($t) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function csrf_validar(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido()) {
        http_response_code(419);
        die('Error de seguridad (Token CSRF no válido). Regresa, recarga la página y vuelve a intentar.');
    }
}