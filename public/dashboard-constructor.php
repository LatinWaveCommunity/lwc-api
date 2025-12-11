<?php
// ================================================================
// DASHBOARD CONSTRUCTOR LWC - CONVERSIÓN PHP COMPLETA
// Convertido de: index.html (dashboard constructor completo)
// Conversión 1:1 sin pérdida de funcionalidades
// ================================================================

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Conectar a base de datos
require_once __DIR__ . '/api/v7/config.php';

// Variables de sesión
$user_id = $_SESSION['user_id'] ?? '';
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_profile = $_SESSION['user_type'] ?? 'constructor';
$username = $_SESSION['username'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? '';

// Cargar datos del usuario desde la base de datos
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $userData_db = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData_db) {
        // Usar lwc_id de la base de datos (NO generar)
        $core_link_id = $userData_db['lwc_id'] ?? '';
        $user_name = $userData_db['full_name'] ?? $user_name;
        $user_email = $userData_db['email'] ?? $user_email;
        $username = $userData_db['username'] ?? $username;
        $user_phone = $userData_db['phone'] ?? '';
        $payment_method = $userData_db['payment_method'] ?? '';
        $payment_info = $userData_db['payment_info'] ?? '';
        $preferred_currency = $userData_db['preferred_currency'] ?? '';
        $digital_assets = $userData_db['digital_assets'] ?? '{}';
        $profile_photo = $userData_db['profile_photo'] ?? '';
        $two_factor_enabled = $userData_db['two_factor_enabled'] ?? 0;

        // Detectar si es fundador (calificados de por vida a TODOS los bonos)
        $founder_ids = ['LWC520000000', 'LWC520000001', 'LWC10000002', 'LWC520000003'];
        $is_founder = in_array($core_link_id, $founder_ids);
    } else {
        $core_link_id = '';
        $is_founder = false;
    }

    // Cargar referidos directos (frontales) - usuarios donde sponsor_id = mi user_id
    $stmt = $pdo->prepare("SELECT * FROM users WHERE sponsor_id = ? ORDER BY registration_date DESC");
    $stmt->execute([$user_id]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separar por tipo (case-insensitive para manejar Constructor/constructor)
    $frontales_constructores = [];
    $frontales_afiliados = [];
    $frontales_clientes = [];

    foreach ($referrals as $ref) {
        $tipo = strtolower(trim($ref['user_type'] ?? ''));
        if ($tipo === 'constructor') {
            $frontales_constructores[] = $ref;
        } elseif ($tipo === 'afiliado') {
            $frontales_afiliados[] = $ref;
        } elseif ($tipo === 'cliente') {
            $frontales_clientes[] = $ref;
        } else {
            // Si no tiene tipo definido, asumir cliente
            $frontales_clientes[] = $ref;
        }
    }

    $total_frontales = count($referrals);

} catch (Exception $e) {
    $core_link_id = '';
    $referrals = [];
    $total_frontales = 0;
    $frontales_constructores = [];
    $frontales_afiliados = [];
    $frontales_clientes = [];
}

// Verificar que el usuario sea constructor
if ($user_profile !== 'constructor') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>Dashboard Constructor - Latin Wave Community</title>


</head>
<body>
<!-- partial:index.partial.html -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Constructor - Latin Wave Community</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #000;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
            transition: color 0.5s ease;
        }

        /* SISTEMA DE COLORES POR HORARIO - CORREGIDO PARA LEGIBILIDAD */
        body.diurno {
            color: #fff;
            background: rgba(0, 0, 0, 0.6);
        }

        body.nocturno {
            color: #fff;
        }

        /* VIDEO BACKGROUND - OLAS */
        .waves-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
        }

        .wave-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 2s ease-in-out;
        }

        .wave-video.active {
            opacity: 1;
        }

        .main-container {
            min-height: 100vh;
            position: relative;
        }

        /* HEADER CON MENÚ NEÓN - COLORES AMARILLO/AZUL */
        .header {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 193, 7, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Cinzel', serif;
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background-image: url('https://i.imgur.com/Om6tGeX.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }

        .nav-menu {
            display: flex;
            gap: 40px;
            list-style: none;
        }

        .nav-item {
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
            position: relative;
        }

        .nav-item:hover {
            color: #fff;
            text-shadow:
                0 0 5px #ffc107,
                0 0 10px #ffc107,
                0 0 15px #ffc107,
                0 0 20px #ffc107,
                0 0 35px #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }

        .nav-item.active {
            color: #fff;
            text-shadow:
                0 0 5px #ffc107,
                0 0 10px #ffc107,
                0 0 15px #ffc107,
                0 0 20px #ffc107;
            background: rgba(255, 193, 7, 0.15);
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .user-badge {
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            color: #000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid rgba(255, 193, 7, 0.3);
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 193, 7, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .user-avatar:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: #ffc107;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
        }

        .user-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .user-placeholder {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* DROPDOWN DE USUARIO */
        .user-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 12px;
            padding: 20px;
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-info {
            margin-bottom: 15px;
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 5px;
        }

        .user-id, .user-username {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 3px;
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255, 193, 7, 0.3);
            margin: 15px 0;
        }

        .dropdown-link {
            display: block;
            padding: 10px 0;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .dropdown-link:hover {
            color: #ffc107;
        }

        /* PANEL DE CONFIGURACIÓN */
        .config-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .config-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .config-panel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .config-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 193, 7, 0.3);
            padding-bottom: 15px;
        }

        .config-title {
            font-size: 20px;
            font-weight: 600;
            color: #ffc107;
        }

        .close-config {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-config:hover {
            color: #ffc107;
        }

        .config-section {
            margin-bottom: 25px;
        }

        .config-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffc107;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus {
            background: rgba(0, 0, 0, 0.9);
            border-color: #ffc107;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* FOTO DE PERFIL - AGREGADA */
        .photo-upload {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .current-photo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: rgba(255, 255, 255, 0.6);
        }

        .upload-btn {
            background: linear-gradient(135deg, #007aff, #0051d5);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            background: linear-gradient(135deg, #0051d5, #003d99);
            transform: translateY(-1px);
        }

        /* SECCIÓN DE ACTIVOS DIGITALES PARA CONSTRUCTOR - CORREGIDA */
        .digital-assets-section {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .assets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .asset-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }

        .asset-checkbox {
            margin-right: 8px;
        }

        .asset-details {
            flex: 1;
        }

        .asset-name {
            font-weight: 600;
            color: #ffc107;
            font-size: 14px;
        }

        .asset-input {
            margin-top: 5px;
            padding: 8px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 4px;
            color: #fff;
            font-size: 12px;
            width: 100%;
        }

        .upload-evidence {
            background: rgba(0, 122, 255, 0.1);
            border: 1px solid rgba(0, 122, 255, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        /* SISTEMA DE EVIDENCIAS FLEXIBLE - NUEVOS ESTILOS */
        .evidence-upload-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .evidence-upload-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 122, 255, 0.3);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .evidence-upload-item:hover {
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(0, 122, 255, 0.5);
        }

        .evidence-upload-item.has-file {
            border-color: rgba(16, 185, 129, 0.5);
            background: rgba(16, 185, 129, 0.1);
        }

        .evidence-file-input {
            display: none;
        }

        .evidence-upload-btn {
            background: linear-gradient(135deg, #007aff, #0051d5);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .evidence-upload-btn:hover {
            background: linear-gradient(135deg, #0051d5, #003d99);
            transform: translateY(-1px);
        }

        .evidence-upload-btn.has-file {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .evidence-upload-btn.has-file:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .evidence-file-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .evidence-file-name {
            color: #fff;
            font-size: 13px;
            font-weight: 500;
        }

        .evidence-file-details {
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
        }

        .evidence-remove-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .evidence-remove-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .add-evidence-btn {
            background: rgba(0, 122, 255, 0.2);
            color: #007aff;
            border: 2px dashed rgba(0, 122, 255, 0.3);
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .add-evidence-btn:hover {
            background: rgba(0, 122, 255, 0.3);
            border-color: rgba(0, 122, 255, 0.5);
        }

        .config-button {
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 14px;
        }

        .config-button:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        .config-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffc107;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 10px;
            line-height: 1.4;
        }

        /* CONTENIDO PRINCIPAL */
        .dashboard-content {
            padding: 60px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 60px;
        }

        .welcome-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #fff;
            text-shadow:
                0 0 5px #ffc107,
                0 0 10px #ffc107,
                0 0 15px #ffc107,
                0 0 20px #ffc107,
                0 0 35px #ffc107;
        }

        .welcome-subtitle {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
            color: #fff;
            text-shadow:
                0 0 5px #007aff,
                0 0 10px #007aff,
                0 0 15px #007aff,
                0 0 20px #007aff;
        }

        /* CORE LINK SYSTEM - SISTEMA INTELIGENTE */
        .core-link-section {
            margin-bottom: 60px;
        }

        .core-link-container {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(0, 122, 255, 0.05));
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .core-link-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .core-link-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffc107;
        }

        .edit-core-link {
            background: linear-gradient(135deg, #007aff, #0051d5);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-core-link:hover {
            background: linear-gradient(135deg, #0051d5, #003d99);
            transform: translateY(-1px);
        }

        .core-link-warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.4);
            color: #ffc107;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .core-link-display {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }

        .core-link-input {
            flex: 1;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
        }

        .copy-btn {
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            color: #000;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .copy-btn:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .core-link-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .core-link-stat {
            text-align: center;
            padding: 15px;
            background: rgba(0, 122, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(0, 122, 255, 0.3);
        }

        .core-link-stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007aff;
            margin-bottom: 5px;
        }

        .core-link-stat-label {
            font-size: 12px;
            opacity: 0.8;
        }

        /* CENTRO DE COMISIONES - ACTUALIZADO PARA CONSTRUCTOR */
        .commissions-section {
            margin-bottom: 60px;
        }

        .commission-card {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(0, 122, 255, 0.05));
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(255, 193, 7, 0.1);
        }

        .commission-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffc107;
            margin-bottom: 20px;
        }

        .commission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .commission-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .commission-amount {
            font-size: 28px;
            font-weight: 700;
            color: #ffc107;
            margin-bottom: 8px;
        }

        .commission-label {
            font-size: 14px;
            opacity: 0.8;
        }

        /* PRODUCTOS ADQUIRIDOS */
        .products-section {
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: left;
            color: #ffc107;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.3);
            border-color: rgba(255, 193, 7, 0.5);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .product-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
            color: #000;
        }

        .product-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(0, 122, 255, 0.2);
            color: #007aff;
            border: 1px solid rgba(0, 122, 255, 0.3);
        }

        .status-inactive {
            background: rgba(150, 150, 150, 0.2);
            color: #999;
            border: 1px solid rgba(150, 150, 150, 0.3);
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
        }

        .product-description {
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.4;
            color: rgba(255, 255, 255, 0.7);
        }

        .product-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ESTADÍSTICAS CONSOLIDADAS - ACTUALIZADO PARA CONSTRUCTOR */
        .stats-section {
            margin-bottom: 60px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.3);
        }

        .stat-card.clickeable:hover {
            border-color: rgba(255, 193, 7, 0.5);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #ffc107;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* PRÓXIMO NIVEL - OCULTO PARA CONSTRUCTOR */
        .upgrades-section {
            display: none;
        }

        /* CHAT FLOTANTE */
        .chat-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(255, 193, 7, 0.9);
            color: #000;
            padding: 15px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 193, 7, 0.3);
            z-index: 1000;
            font-weight: 600;
        }

        .chat-widget:hover {
            background: #ffc107;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.4);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .nav-menu {
                gap: 20px;
            }

            .dashboard-content {
                padding: 40px 20px;
            }

            .welcome-title {
                font-size: 36px;
            }

            .products-grid, .core-link-stats {
                grid-template-columns: 1fr;
            }

            .config-panel {
                padding: 30px 20px;
                width: 95%;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body id="bodyElement">
    <!-- PANEL DE CONFIGURACIÓN EXTENDIDO PARA CONSTRUCTOR -->
    <div class="config-overlay" id="configOverlay">
        <div class="config-panel">
            <div class="config-header">
                <h2 class="config-title">Configuración de Perfil Constructor</h2>
                <button class="close-config" onclick="closeConfig()">×</button>
            </div>

            <!-- INFORMACIÓN PERSONAL -->
            <div class="config-section">
                <h3 class="config-section-title">👤 Información Personal</h3>
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user_name); ?>" id="fullName" placeholder="Ingresa tu nombre completo">
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($username); ?>" id="username" placeholder="Ingresa tu username">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($user_email); ?>" id="email" placeholder="Ingresa tu email">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono / WhatsApp</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" id="phone" placeholder="+52 123 456 7890">
                </div>
            </div>

            <!-- FOTO DE PERFIL - AGREGADA -->
            <div class="config-section">
                <h3 class="config-section-title">📸 Foto de Perfil</h3>
                <div class="photo-upload">
                    <div class="current-photo" id="configPhoto">
                        <span>👤</span>
                    </div>
                    <div>
                        <input type="file" id="photoInput" accept="image/*" style="display: none;" onchange="handlePhotoUpload(event)">
                        <button class="upload-btn" onclick="document.getElementById('photoInput').click()">Cambiar Foto</button>
                        <p style="font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 5px;">Máximo 2MB - JPG, PNG</p>
                    </div>
                </div>
            </div>

            <!-- ACTIVOS DIGITALES - CORREGIDO -->
            <div class="config-section">
                <h3 class="config-section-title">🔗 Activos Digitales Configurados</h3>
                <div class="digital-assets-section">
                    <p style="color: rgba(255,255,255,0.8); margin-bottom: 15px;">
                        Configura tus activos digitales externos para el CORE LINK system
                    </p>
                    <div class="assets-grid">
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="leadlightning">
                            <div class="asset-details">
                                <div class="asset-name">LeadLightning</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="notion">
                            <div class="asset-details">
                                <div class="asset-name">Notion</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="goe1ulife">
                            <div class="asset-details">
                                <div class="asset-name">GoE1Ulife</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="comizion">
                            <div class="asset-details">
                                <div class="asset-name">Comizion System</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="fbleadgen">
                            <div class="asset-details">
                                <div class="asset-name">FBleadGen</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="saveclub">
                            <div class="asset-details">
                                <div class="asset-name">SaveClub</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="livegood">
                            <div class="asset-details">
                                <div class="asset-name">LiveGood</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                        <div class="asset-item">
                            <input type="checkbox" class="asset-checkbox" id="vitalhealth">
                            <div class="asset-details">
                                <div class="asset-name">Vitalhealth</div>
                                <input type="text" class="asset-input" placeholder="ID de Usuario" value="">
                            </div>
                        </div>
                    </div>

                    <!-- SISTEMA DE EVIDENCIAS FLEXIBLE - SIN DATOS PRECARGADOS -->
                    <div class="upload-evidence">
                        <h4 style="color: #007aff; margin-bottom: 15px;">📄 Evidencias de Actividad Mensual</h4>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 15px; font-size: 13px;">
                            Sube cualquier documento que demuestre actividad en tus activos digitales del mes actual. Acepta todos los formatos de archivo.
                        </p>

                        <div class="evidence-upload-container" id="evidenceContainer">
                            <!-- Sin evidencias precargadas -->
                        </div>

                        <button class="add-evidence-btn" onclick="addNewEvidence()">
                            ➕ Agregar Nueva Evidencia
                        </button>

                        <div class="config-info" style="margin-top: 15px;">
                            💡 <strong>Sistema Flexible:</strong> Puedes subir cualquier tipo de archivo (PDF, Word, Excel, imágenes, videos, etc.) para demostrar tu actividad mensual. Tamaño máximo por archivo: 10MB
                        </div>
                    </div>
                </div>
            </div>

            <!-- MÉTODOS DE PAGO PARA COMISIONES -->
            <div class="config-section">
                <h3 class="config-section-title">💳 Configuración de Pagos para Comisiones</h3>
                <div class="form-group">
                    <label class="form-label">Método para Recibir Comisiones/Bonos</label>
                    <select class="form-select" id="paymentMethod" onchange="updatePaymentFields()">
                        <option value="">Seleccionar método de pago</option>
                        <option value="paypal">PayPal</option>
                        <option value="mercadopago">MercadoPago</option>
                        <option value="btc">BTC Wallet</option>
                        <option value="binance">Binance ID</option>
                    </select>
                </div>
                <div class="form-group" id="paymentDetails">
                    <label class="form-label" id="paymentLabel">Información de Pago</label>
                    <input type="text" class="form-input" id="paymentInfo" value="" placeholder="Ingresa tu información de pago">
                </div>
                <div class="form-group">
                    <label class="form-label">Moneda Preferida</label>
                    <select class="form-select" id="currency">
                        <option value="">Seleccionar moneda</option>
                        <option value="usdt">USDT</option>
                        <option value="btc">BTC</option>
                    </select>
                </div>
                <div class="config-info">
                    💰 Comisiones Constructor: Directas (50%) + Override frontales (50%) + Bono Constructor (5% sobre constructores WWB)
                </div>
            </div>

            <!-- SEGURIDAD -->
            <div class="config-section">
                <h3 class="config-section-title">🔒 Seguridad</h3>
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-input" id="newPassword" placeholder="Dejar vacío para no cambiar">
                </div>
                <div class="security-option" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,193,7,0.3);">
                    <div>
                        <div style="font-size: 14px; color: #fff; font-weight: 500;">Autenticación de Dos Factores (2FA)</div>
                        <div style="font-size: 12px; color: rgba(255,255,255,0.6);">Google Authenticator</div>
                    </div>
                    <div style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; background: rgba(150, 150, 150, 0.2); color: #999; border: 1px solid rgba(150, 150, 150, 0.3);" id="twoFactorStatus">No configurado</div>
                </div>
                <button class="config-button" onclick="toggle2FA()" style="margin-top: 10px;">Configurar 2FA</button>
            </div>

            <!-- GUARDAR CAMBIOS -->
            <div style="margin-top: 30px;">
                <button class="config-button" onclick="saveProfile()">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <!-- FONDO DE OLAS -->
    <div class="waves-background">
        <video class="wave-video" id="morningWaves" muted loop>
            <source src="https://i.imgur.com/0oSrU7z.mp4" type="video/mp4">
        </video>
        <video class="wave-video" id="eveningWaves" muted loop>
            <source src="https://i.imgur.com/xGCH0j0.mp4" type="video/mp4">
        </video>
        <video class="wave-video" id="nightWaves" muted loop>
            <source src="https://i.imgur.com/kDhMoFE.mp4" type="video/mp4">
        </video>
    </div>

    <!-- CONTAINER PRINCIPAL -->
    <div class="main-container">
        <!-- HEADER CON MENÚ NEÓN -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon"></div>
                <span>@LatinWaveCommunity</span>
            </div>

            <nav>
                <ul class="nav-menu">
                    <li class="nav-item" onclick="navigateToSection('home')">HOME</li>
                    <li class="nav-item active" onclick="navigateToSection('dashboard')">DASHBOARD</li>
                    <li class="nav-item" onclick="goToAITools()">AI TOOLS + PRODUCTS</li>
                    <li class="nav-item" onclick="navigateToSection('team-room')">TEAM ROOM</li>
                </ul>
            </nav>

            <div class="user-controls">
                <div class="user-badge">CONSTRUCTOR</div>
                <div class="user-avatar" onclick="toggleUserDropdown()">
                    <span class="user-placeholder" id="avatarPlaceholder" <?php if (!empty($profile_photo)): ?>style="display: none;"<?php endif; ?>>👤</span>
                    <img class="user-photo" id="userPhoto" <?php if (!empty($profile_photo)): ?>style="display: block;" src="<?php echo htmlspecialchars($profile_photo); ?>"<?php else: ?>style="display: none;" src=""<?php endif; ?> alt="Foto de perfil">
                </div>

                <!-- DROPDOWN DE USUARIO -->
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-info">
                        <div class="user-name" id="dropdownName"><?php echo htmlspecialchars($user_name ?: 'Sin nombre'); ?></div>
                        <div class="user-username" id="dropdownUsername">@<?php echo htmlspecialchars($username ?: 'usuario'); ?></div>
                        <div class="user-id" id="dropdownId">ID: <?php echo htmlspecialchars($user_id ?: 'Sin ID'); ?></div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-link" onclick="openConfig()">⚙️ Configurar Perfil</a>
                    <a class="dropdown-link" onclick="logout()">🚪 Cerrar Sesión</a>
                </div>
            </div>
        </header>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="dashboard-content">
            <!-- BIENVENIDA -->
            <section class="welcome-section">
                <h1 class="welcome-title">Dashboard Constructor</h1>
                <p class="welcome-subtitle">
                    Gestiona tu negocio LWC con acceso completo a 16 niveles, CORE LINK system y beneficios de Constructor.
                    Calificación de por vida: Aprovecha tus beneficios exclusivos y construye tu imperio digital.
                </p>
            </section>

            <!-- CORE LINK SYSTEM - REEMPLAZA SECCIÓN DE REFERIDOS -->
            <section class="core-link-section">
                <h2 class="section-title">🔗 CORE LINK System</h2>
                <div class="core-link-container">
                    <div class="core-link-header">
                        <h3 class="core-link-title">Sistema de Enlace Inteligente</h3>
                        <button class="edit-core-link" onclick="editCoreLink()">⚙️ Configurar</button>
                    </div>
                    <div class="core-link-warning">
                        ⚠️ CONFIGURA TUS ACTIVOS: Tu CORE LINK fusiona 0 activos digitales configurados
                    </div>
                    <div class="core-link-display">
                        <input type="text" class="core-link-input" value="https://latinwave.org/index.php?master=<?php echo htmlspecialchars($core_link_id); ?>" readonly id="coreLink">
                        <button class="copy-btn" onclick="copyCoreLink()">Copiar CORE LINK</button>
                    </div>
                    <div class="core-link-stats">
                        <div class="core-link-stat">
                            <div class="core-link-stat-value">0</div>
                            <div class="core-link-stat-label">Activos Configurados</div>
                        </div>
                        <div class="core-link-stat">
                            <div class="core-link-stat-value">0</div>
                            <div class="core-link-stat-label">Registros CORE LINK</div>
                        </div>
                        <div class="core-link-stat">
                            <div class="core-link-stat-value">$0.00</div>
                            <div class="core-link-stat-label">Comisiones Generadas</div>
                        </div>
                        <div class="core-link-stat">
                            <div class="core-link-stat-value">—</div>
                            <div class="core-link-stat-label">Status Mensual</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CENTRO DE COMISIONES CONSTRUCTOR -->
            <section class="commissions-section">
                <h2 class="section-title">💰 Centro de Comisiones Constructor</h2>
                <div class="commission-card">
                    <h3 class="commission-title">Resumen de Ingresos</h3>
                    <div class="commission-grid">
                        <div class="commission-item">
                            <div class="commission-amount">$0.00</div>
                            <div class="commission-label">Comisiones Directas (50%)</div>
                        </div>
                        <div class="commission-item">
                            <div class="commission-amount">$0.00</div>
                            <div class="commission-label">Override Frontales (50%)</div>
                        </div>
                        <div class="commission-item">
                            <div class="commission-amount">$0.00</div>
                            <div class="commission-label">Bono Constructor (5%)</div>
                        </div>
                        <div class="commission-item">
                            <div class="commission-amount">$0.00</div>
                            <div class="commission-label">Bono Matriz (2.5%)</div>
                        </div>
                        <div class="commission-item">
                            <div class="commission-amount">$0.00</div>
                            <div class="commission-label">WWB (Frontales)</div>
                        </div>
                        <div class="commission-item">
                            <div class="commission-amount">$0.00</div>
                            <div class="commission-label">Total del Mes</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PRODUCTOS ADQUIRIDOS -->
            <section class="products-section">
                <h2 class="section-title">📦 Mis Productos</h2>
                <div class="products-grid">
                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-icon">📦</div>
                            <div class="product-status status-inactive">Sin producto</div>
                        </div>
                        <div class="product-name">Agent Pack</div>
                        <div class="product-description">Pack completo base para calificación de Constructor</div>
                        <div class="product-stats">
                            <span>Valor: $0.00 USDT</span>
                            <span>Requerido para mantener estatus</span>
                        </div>
                    </div>

                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-icon">⭐</div>
                            <div class="product-status status-inactive">Sin suscripción</div>
                        </div>
                        <div class="product-name">VIP Agent</div>
                        <div class="product-description">Agente premium con características avanzadas</div>
                        <div class="product-stats">
                            <span>$0.00 USDT/mes</span>
                            <span>Sin renovación</span>
                        </div>
                    </div>

                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-icon">🔗</div>
                            <div class="product-status status-inactive">Sin configurar</div>
                        </div>
                        <div class="product-name">Activos Digitales</div>
                        <div class="product-description">Configura tus activos digitales para el CORE LINK system</div>
                        <div class="product-stats">
                            <span>Status: Sin verificar</span>
                            <span>Requerido para Constructor</span>
                        </div>
                    </div>

                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-icon">🎯</div>
                            <div class="product-status status-inactive">Sin agentes</div>
                        </div>
                        <div class="product-name">Agentes Adicionales</div>
                        <div class="product-description">Agentes individuales adquiridos</div>
                        <div class="product-stats">
                            <span>0 agentes</span>
                            <span>$0.00 USDT total</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MI ORGANIZACIÓN - MÉTRICAS CONSOLIDADAS CONSTRUCTOR -->
            <section class="stats-section">
                <h2 class="section-title">📊 Mi Organización - Métricas Constructor</h2>
                <div class="stats-grid">
                    <div class="stat-card clickeable" onclick="showVolumeDetails()">
                        <div class="stat-value">$0.00</div>
                        <div class="stat-label">Volumen Personal (Este Mes)</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showGroupVolumeDetails()">
                        <div class="stat-value">$0.00</div>
                        <div class="stat-label">Volumen Grupal (16 Niveles)</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showTotalVolumeDetails()">
                        <div class="stat-value">$0.00</div>
                        <div class="stat-label">Volumen Total (Matriz Completa)</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showClientsDetails()">
                        <div class="stat-value"><?php echo count($frontales_clientes); ?></div>
                        <div class="stat-label">Clientes Directos</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showAffiliatesDetails()">
                        <div class="stat-value"><?php echo count($frontales_afiliados); ?></div>
                        <div class="stat-label">Afiliados en mi Línea</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showConstructorsDetails()">
                        <div class="stat-value"><?php echo count($frontales_constructores); ?></div>
                        <div class="stat-label">Constructores en mi Línea</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showWWBDetails()">
                        <div class="stat-value"><?php echo $total_frontales; ?></div>
                        <div class="stat-label">Frontales Totales (WWB)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">—</div>
                        <div class="stat-label">Calificación</div>
                    </div>
                </div>
            </section>
        </main>

        <!-- LINK - AI Agent -->
        <div class="chat-widget" onclick="openLINK()">
            🤖 LINK
        </div>
    </div>

    <script>
        // DATOS DE USUARIO CONSTRUCTOR - CARGADOS DESDE BASE DE DATOS
        var userData = {
            id: '<?php echo htmlspecialchars($user_id); ?>',
            lwcId: '<?php echo htmlspecialchars($core_link_id); ?>',
            username: '<?php echo htmlspecialchars($username); ?>',
            fullName: '<?php echo htmlspecialchars($user_name); ?>',
            email: '<?php echo htmlspecialchars($user_email); ?>',
            phone: '<?php echo htmlspecialchars($user_phone ?? ""); ?>',
            photo: <?php echo $profile_photo ? "'" . htmlspecialchars($profile_photo) . "'" : 'null'; ?>,
            twoFactorEnabled: <?php echo $two_factor_enabled ? 'true' : 'false'; ?>,
            status: 'constructor',
            isFounder: <?php echo $is_founder ? 'true' : 'false'; ?>,
            monthlyVolume: 0,
            groupVolume: 0,
            totalVolume: 0,
            totalCommissions: 0,
            frontales: <?php echo $total_frontales; ?>,
            wwbLevel: <?php echo $is_founder ? "'WWB5'" : "''"; ?>,
            paymentMethod: '<?php echo htmlspecialchars($payment_method ?? ""); ?>',
            paymentInfo: '<?php echo htmlspecialchars($payment_info ?? ""); ?>',
            currency: '<?php echo htmlspecialchars($preferred_currency ?? ""); ?>',
            coreLink: 'https://latinwave.org/index.php?master=<?php echo htmlspecialchars($core_link_id); ?>',
            digitalAssets: <?php echo $digital_assets ?: '{}'; ?>
        };

        // DATOS DE REFERIDOS CARGADOS DESDE BASE DE DATOS
        // Clientes: campos para modal original
        window.allClientsData = <?php echo json_encode(array_map(function($ref) {
            return [
                'name' => $ref['full_name'] ?? $ref['username'] ?? 'Sin nombre',
                'lwcId' => $ref['lwc_id'] ?? '',
                'email' => $ref['email'] ?? '',
                'phone' => $ref['phone'] ?? '',
                'whatsapp' => $ref['phone'] ?? 'No disponible',
                'status' => ($ref['status'] === 'active') ? 'Activo' : 'Pendiente',
                'consumption' => 0,
                'commission' => 0,
                'coreSource' => 'Directo',
                'lastPurchase' => $ref['registration_date'] ?? 'N/A',
                'joinDate' => $ref['registration_date'] ?? 'N/A'
            ];
        }, $frontales_clientes)); ?>;

        // Afiliados: campos para modal original
        window.allAffiliatesData = <?php echo json_encode(array_map(function($ref) {
            return [
                'name' => $ref['full_name'] ?? $ref['username'] ?? 'Sin nombre',
                'lwcId' => $ref['lwc_id'] ?? '',
                'email' => $ref['email'] ?? '',
                'phone' => $ref['phone'] ?? '',
                'whatsapp' => $ref['phone'] ?? 'No disponible',
                'level' => 1,
                'monthlyEarnings' => 0,
                'overrideGenerated' => 0,
                'teamSize' => 0,
                'qualification' => 'Activo',
                'joinDate' => $ref['registration_date'] ?? 'N/A'
            ];
        }, $frontales_afiliados)); ?>;

        // Constructores: campos para modal original
        window.allConstructorsData = <?php echo json_encode(array_map(function($ref) {
            return [
                'name' => $ref['full_name'] ?? $ref['username'] ?? 'Sin nombre',
                'lwcId' => $ref['lwc_id'] ?? '',
                'email' => $ref['email'] ?? '',
                'phone' => $ref['phone'] ?? '',
                'whatsapp' => $ref['phone'] ?? 'No disponible',
                'level' => 1,
                'monthlyEarnings' => 0,
                'constructorBonus' => 0,
                'teamSize' => 0,
                'totalVolume' => 0,
                'digitalAssets' => 0,
                'qualification' => 'Constructor Activo',
                'joinDate' => $ref['registration_date'] ?? 'N/A'
            ];
        }, $frontales_constructores)); ?>;

        // WWB Frontales: todos los referidos directos
        window.allWWBFrontalesData = <?php echo json_encode(array_map(function($ref) {
            return [
                'name' => $ref['full_name'] ?? $ref['username'] ?? 'Sin nombre',
                'lwcId' => $ref['lwc_id'] ?? '',
                'userType' => $ref['user_type'] ?? 'cliente',
                'monthlyVolume' => 0,
                'qualified' => ($ref['status'] === 'active')
            ];
        }, $referrals)); ?>;

        // SISTEMA DE EVIDENCIAS FLEXIBLE
        var evidenceCounter = 0;

        function handleEvidenceUpload(evidenceId, event) {
            var file = event.target.files[0];
            if (!file) return;

            // Validar tamaño (10MB max)
            var maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('Error: El archivo es demasiado grande. Tamaño máximo permitido: 10MB');
                event.target.value = '';
                return;
            }

            var evidenceItem = document.getElementById('evidence_' + evidenceId).closest('.evidence-upload-item');
            var button = evidenceItem.querySelector('.evidence-upload-btn');
            var fileInfo = evidenceItem.querySelector('.evidence-file-info');

            evidenceItem.classList.add('has-file');
            button.classList.add('has-file');
            button.textContent = '📎 Cambiar';

            fileInfo.innerHTML = '<div class="evidence-file-name">' + file.name + '</div><div class="evidence-file-details">' + getFileExtension(file.name).toUpperCase() + ' - ' + formatFileSize(file.size) + ' - Subido ahora</div>';

            console.log('Archivo subido: ' + file.name + ' (' + file.size + ' bytes)');
        }

        function removeEvidence(evidenceId) {
            if (confirm('¿Estás seguro de que deseas eliminar esta evidencia?')) {
                var evidenceItem = document.getElementById('evidence_' + evidenceId).closest('.evidence-upload-item');
                evidenceItem.remove();
            }
        }

        function addNewEvidence() {
            evidenceCounter++;
            var container = document.getElementById('evidenceContainer');

            var newEvidenceHTML = '<div class="evidence-upload-item">' +
                '<input type="file" class="evidence-file-input" id="evidence_' + evidenceCounter + '" accept="*/*" onchange="handleEvidenceUpload(' + evidenceCounter + ', event)">' +
                '<button class="evidence-upload-btn" onclick="document.getElementById(\'evidence_' + evidenceCounter + '\').click()">📎 Subir Archivo</button>' +
                '<div class="evidence-file-info">' +
                '<div class="evidence-file-name">Sin archivo seleccionado</div>' +
                '<div class="evidence-file-details">Selecciona cualquier tipo de archivo para subir</div>' +
                '</div>' +
                '<button class="evidence-remove-btn" onclick="removeEvidence(' + evidenceCounter + ')">🗑️ Eliminar</button>' +
                '</div>';

            container.insertAdjacentHTML('beforeend', newEvidenceHTML);
        }

        function getFileExtension(filename) {
            return filename.split('.').pop() || 'archivo';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Los datos de referidos ya están cargados desde la base de datos (líneas 1550-1591)

        // SISTEMA DE NAVEGACIÓN
        class NavigationManager {
            constructor() {
                this.currentSection = 'dashboard';
                this.sections = {
                    'home': {
                        title: 'Inicio - Latin Wave Community',
                        content: null,
                        requiresAuth: false
                    },
                    'dashboard': {
                        title: 'Dashboard Constructor - Latin Wave Community',
                        content: 'dashboard-content',
                        requiresAuth: true
                    },
                    'ai-tools-products': {
                        title: 'AI Tools + Products - Latin Wave Community',
                        content: null,
                        requiresAuth: true
                    },
                    'team-room': {
                        title: 'Team Room - Latin Wave Community',
                        content: null,
                        requiresAuth: true
                    }
                };
            }

            navigateTo(sectionId) {
                if (!this.sections[sectionId]) {
                    console.error('Seccion ' + sectionId + ' no encontrada');
                    return;
                }

                if (this.sections[sectionId].requiresAuth && !this.isUserAuthenticated()) {
                    this.redirectToLogin();
                    return;
                }

                this.updateActiveNavItem(sectionId);
                this.showSectionContent(sectionId);
                document.title = this.sections[sectionId].title;
                this.currentSection = sectionId;
                this.updateURL(sectionId);
            }

            updateActiveNavItem(activeSectionId) {
                document.querySelectorAll('.nav-item').forEach(function(item) {
                    item.classList.remove('active');
                });

                var sectionMap = {
                    'home': 0,
                    'dashboard': 1,
                    'ai-tools-products': 2,
                    'team-room': 3
                };

                var activeIndex = sectionMap[activeSectionId];
                if (activeIndex !== undefined) {
                    var navItems = document.querySelectorAll('.nav-item');
                    if (navItems[activeIndex]) {
                        navItems[activeIndex].classList.add('active');
                    }
                }
            }

            showSectionContent(sectionId) {
                var section = this.sections[sectionId];

                if (section.content === 'dashboard-content') {
                    document.querySelector('.main-container').style.display = 'block';
                } else if (section.content === null) {
                    this.showPlaceholderContent(sectionId);
                } else {
                    this.loadSectionContent(sectionId);
                }
            }

            showPlaceholderContent(sectionId) {
                var sectionNames = {
                    'home': 'Página Principal',
                    'ai-tools-products': 'AI Tools + Products',
                    'team-room': 'Team Room'
                };

                var sectionDescriptions = {
                    'home': 'Página principal con información general de Latin Wave Community',
                    'ai-tools-products': 'Herramientas de IA y catálogo completo de productos LWC',
                    'team-room': 'Sala de equipo con chat, recursos y herramientas colaborativas'
                };

                var dashboardContent = document.querySelector('.dashboard-content');
                if (dashboardContent) {
                    dashboardContent.style.display = 'none';
                }

                this.createPlaceholderHTML(sectionId, sectionNames[sectionId], sectionDescriptions[sectionId]);
            }

            createPlaceholderHTML(sectionId, sectionName, sectionDescription) {
                var existingPlaceholder = document.getElementById('section-placeholder');
                if (existingPlaceholder) {
                    existingPlaceholder.remove();
                }

                // Actualizar clase activa en el menu original
                var navItems = document.querySelectorAll('.nav-item');
                navItems.forEach(function(item) {
                    item.classList.remove('active');
                });
                if (sectionId === 'home') {
                    navItems[0].classList.add('active');
                } else if (sectionId === 'ai-tools-products') {
                    navItems[2].classList.add('active');
                } else if (sectionId === 'team-room') {
                    navItems[3].classList.add('active');
                }

                var placeholderHTML = '<div id="section-placeholder" style="position:relative;width:100%;min-height:calc(100vh - 100px);overflow:hidden;">' +
                    '<video autoplay muted loop playsinline style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:1;"><source src="https://i.imgur.com/Kjy6i5a.mp4" type="video/mp4"></video>' +
                    '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:2;"></div>' +
                    '<div style="position:relative;z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:calc(100vh - 100px);text-align:center;padding:40px;">' +
                    '<h2 style="color:#ffc107;font-size:48px;margin-bottom:20px;text-shadow:0 0 30px rgba(255,193,7,0.6);font-family:Cinzel,serif;">' + sectionName + '</h2>' +
                    '<p style="color:#fff;font-size:18px;margin-bottom:40px;line-height:1.6;max-width:600px;text-shadow:0 2px 10px rgba(0,0,0,0.8);">' + sectionDescription + '</p>' +
                    '<div style="background:rgba(255,193,7,0.15);border:2px solid rgba(255,193,7,0.5);color:#ffc107;padding:30px 60px;border-radius:16px;margin-bottom:40px;font-size:24px;font-weight:700;text-shadow:0 0 20px rgba(255,193,7,0.4);">PRÓXIMAMENTE</div>' +
                    '<button onclick="navigationManager.navigateTo(\'dashboard\')" style="background:linear-gradient(135deg,#ffc107,#f59e0b);color:#000;border:none;padding:18px 50px;border-radius:30px;font-weight:700;font-size:16px;cursor:pointer;box-shadow:0 4px 20px rgba(255,193,7,0.4);transition:transform 0.2s;">Volver al Dashboard</button>' +
                    '</div></div>';

                var dashboardContent = document.querySelector('.dashboard-content');
                if (dashboardContent) {
                    dashboardContent.insertAdjacentHTML('afterend', placeholderHTML);
                }
            }

            async loadSectionContent(sectionId) {
                console.log('Cargando contenido para seccion: ' + sectionId);
            }

            isUserAuthenticated() {
                return userData && userData.id;
            }

            redirectToLogin() {
                alert('Sesión requerida. Redirigiendo al login...');
            }

            updateURL(sectionId) {
                console.log('URL actualizada para seccion: ' + sectionId);
            }
        }

        var navigationManager = new NavigationManager();

        function navigateToSection(sectionId) {
            navigationManager.navigateTo(sectionId);
        }

        // SISTEMA DE OLAS POR HORARIO
        function initWaveSystem() {
            var now = new Date();
            var hour = now.getHours();

            var morningVideo = document.getElementById('morningWaves');
            var eveningVideo = document.getElementById('eveningWaves');
            var nightVideo = document.getElementById('nightWaves');
            var body = document.getElementById('bodyElement');

            [morningVideo, eveningVideo, nightVideo].forEach(function(video) {
                video.classList.remove('active');
                video.pause();
            });

            var activeVideo;
            if (hour >= 6 && hour < 12) {
                activeVideo = morningVideo;
                body.className = 'nocturno';
            } else if (hour >= 12 && hour < 19) {
                activeVideo = eveningVideo;
                body.className = 'nocturno';
            } else {
                activeVideo = nightVideo;
                body.className = 'nocturno';
            }

            activeVideo.classList.add('active');
            activeVideo.play().catch(e => console.log('Autoplay prevented:', e));
        }

        // FUNCIONES DE USUARIO
        function toggleUserDropdown() {
            var dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }

        function openConfig() {
            var overlay = document.getElementById('configOverlay');
            overlay.classList.add('active');
            closeUserDropdown();
            loadUserData();
        }

        function closeConfig() {
            var overlay = document.getElementById('configOverlay');
            overlay.classList.remove('active');
        }

        function closeUserDropdown() {
            var dropdown = document.getElementById('userDropdown');
            dropdown.classList.remove('active');
        }

        function loadUserData() {
            document.getElementById('fullName').value = userData.fullName || '';
            document.getElementById('username').value = userData.username || '';
            document.getElementById('email').value = userData.email || '';
            document.getElementById('phone').value = userData.phone || '';
            document.getElementById('paymentMethod').value = userData.paymentMethod || '';
            document.getElementById('currency').value = userData.currency || '';
            document.getElementById('paymentInfo').value = userData.paymentInfo || '';

            // Cargar foto de perfil si existe
            if (userData.photo) {
                var userPhoto = document.getElementById('userPhoto');
                var avatarPlaceholder = document.getElementById('avatarPlaceholder');
                var configPhoto = document.getElementById('configPhoto');

                if (userPhoto) {
                    userPhoto.src = userData.photo;
                    userPhoto.style.display = 'block';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                }
                if (configPhoto) {
                    configPhoto.innerHTML = '<img src="' + userData.photo + '" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">';
                }
            }

            // Cargar activos digitales si existen
            if (userData.digitalAssets && typeof userData.digitalAssets === 'object') {
                Object.keys(userData.digitalAssets).forEach(function(assetId) {
                    var asset = userData.digitalAssets[assetId];
                    var checkbox = document.getElementById(assetId);
                    var input = checkbox ? checkbox.closest('.asset-item').querySelector('.asset-input') : null;
                    if (checkbox && asset) {
                        checkbox.checked = asset.active || false;
                        if (input && asset.id) {
                            input.value = asset.id;
                        }
                    }
                });
            }

            // Actualizar campos de pago según método seleccionado
            updatePaymentFields();
        }

        function updatePaymentFields() {
            var method = document.getElementById('paymentMethod').value;
            var label = document.getElementById('paymentLabel');
            var input = document.getElementById('paymentInfo');

            switch(method) {
                case 'paypal':
                    label.textContent = 'Email de PayPal';
                    input.placeholder = 'tu-email@paypal.com';
                    break;
                case 'mercadopago':
                    label.textContent = 'Email de MercadoPago';
                    input.placeholder = 'tu-email@mercadopago.com';
                    break;
                case 'btc':
                    label.textContent = 'Dirección BTC Wallet';
                    input.placeholder = 'bc1q...';
                    break;
                case 'binance':
                    label.textContent = 'Binance ID';
                    input.placeholder = '123456789';
                    break;
                default:
                    label.textContent = 'Información de Pago';
                    input.placeholder = 'Selecciona un método de pago primero';
            }
        }

        function handlePhotoUpload(event) {
            var file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('El archivo es demasiado grande. Máximo 2MB.');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    var photoData = e.target.result;

                    var configPhoto = document.getElementById('configPhoto');
                    configPhoto.innerHTML = '<img src="' + photoData + '" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">';

                    var userPhoto = document.getElementById('userPhoto');
                    var avatarPlaceholder = document.getElementById('avatarPlaceholder');
                    userPhoto.src = photoData;
                    userPhoto.style.display = 'block';
                    avatarPlaceholder.style.display = 'none';

                    userData.photo = photoData;
                };
                reader.readAsDataURL(file);
            }
        }

        function toggle2FA() {
            var status = document.getElementById('twoFactorStatus');
            var isEnabled = status.textContent === 'Habilitado';

            if (isEnabled) {
                status.textContent = 'No configurado';
                status.style.background = 'rgba(150, 150, 150, 0.2)';
                status.style.color = '#999';
                status.style.borderColor = 'rgba(150, 150, 150, 0.3)';
                userData.twoFactorEnabled = false;
                alert('2FA deshabilitado correctamente');
            } else {
                var code = prompt('Ingresa el código de Google Authenticator para activar 2FA:');
                if (code && code.length === 6) {
                    status.textContent = 'Habilitado';
                    status.style.background = 'rgba(0, 122, 255, 0.2)';
                    status.style.color = '#007aff';
                    status.style.borderColor = 'rgba(0, 122, 255, 0.3)';
                    userData.twoFactorEnabled = true;
                    alert('2FA activado correctamente');
                } else {
                    alert('Código inválido. 2FA no activado.');
                }
            }
        }

        function saveProfile() {
            // Recopilar TODOS los datos del formulario
            var profileData = {
                fullName: document.getElementById('fullName').value,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                photo: userData.photo || null,
                paymentMethod: document.getElementById('paymentMethod').value,
                paymentInfo: document.getElementById('paymentInfo').value,
                currency: document.getElementById('currency').value,
                twoFactorEnabled: userData.twoFactorEnabled || false,
                newPassword: document.getElementById('newPassword').value || null,
                digitalAssets: {}
            };

            // Recopilar activos digitales
            var assetIds = ['leadlightning', 'notion', 'goe1ulife', 'comizion', 'fbleadgen', 'saveclub', 'livegood', 'vitalhealth'];
            assetIds.forEach(function(assetId) {
                var checkbox = document.getElementById(assetId);
                var input = checkbox ? checkbox.closest('.asset-item').querySelector('.asset-input') : null;
                if (checkbox && input) {
                    profileData.digitalAssets[assetId] = {
                        active: checkbox.checked,
                        id: input.value,
                        verified: false
                    };
                }
            });

            // Enviar a servidor
            fetch('save-profile.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(profileData)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Actualizar datos locales
                    userData.fullName = profileData.fullName;
                    userData.username = profileData.username;
                    userData.email = profileData.email;
                    userData.phone = profileData.phone;
                    userData.paymentMethod = profileData.paymentMethod;
                    userData.currency = profileData.currency;
                    userData.paymentInfo = profileData.paymentInfo;
                    userData.digitalAssets = profileData.digitalAssets;
                    userData.twoFactorEnabled = profileData.twoFactorEnabled;
                    if (data.photo) {
                        userData.photo = data.photo;
                    }

                    // Actualizar UI
                    document.getElementById('dropdownName').textContent = profileData.fullName || 'Sin nombre';
                    document.getElementById('dropdownUsername').textContent = '@' + (profileData.username || 'usuario');

                    var activeAssets = Object.keys(profileData.digitalAssets).filter(function(k) {
                        return profileData.digitalAssets[k].active;
                    }).length;

                    // Limpiar campo de contraseña
                    document.getElementById('newPassword').value = '';

                    alert('¡Perfil guardado en base de datos!\n\n' +
                          'Nombre: ' + profileData.fullName + '\n' +
                          'Username: ' + profileData.username + '\n' +
                          'Email: ' + profileData.email + '\n' +
                          'Teléfono: ' + (profileData.phone || 'No configurado') + '\n' +
                          'Método de pago: ' + (profileData.paymentMethod || 'No configurado').toUpperCase() + '\n' +
                          'Moneda: ' + (profileData.currency || 'No configurada').toUpperCase() + '\n' +
                          '2FA: ' + (profileData.twoFactorEnabled ? 'Habilitado' : 'Deshabilitado') + '\n' +
                          'Foto: ' + (data.photo ? 'Actualizada' : 'Sin cambios') + '\n' +
                          'Activos digitales: ' + activeAssets);
                    closeConfig();
                } else {
                    alert('Error al guardar: ' + data.message);
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Error de conexión al guardar el perfil');
            });
        }

        function logout() {
            if (confirm('¿Seguro que deseas cerrar sesión?')) {
                window.location.href = 'logout.php';
            }
        }

        // FUNCIONES CORE LINK SYSTEM
        function editCoreLink() {
            alert('CONFIGURACIÓN CORE LINK\n\n' +
                  'Sistema inteligente que fusiona múltiples activos digitales:\n' +
                  '• Configura IDs de referidos de exchanges\n' +
                  '• Un solo link para todos tus activos\n' +
                  '• Reconocimiento automático de patrocinador\n' +
                  '• Evidencias mensuales requeridas\n\n' +
                  'Accede a Configuración de Perfil para gestionar.');
        }

        function copyCoreLink() {
            var input = document.getElementById('coreLink');
            input.select();
            document.execCommand('copy');
            alert('¡CORE LINK copiado al portapapeles!\n\n' +
                  'Este link fusiona todos tus activos digitales configurados.\n' +
                  'Cualquier ID asociado reconocerá tu patrocinio automáticamente.');
        }

        // FUNCIONES DE DETALLES (VACÍAS - SIN DATOS)
        function showVolumeDetails() {
            alert('DETALLE DE VOLUMEN PERSONAL CONSTRUCTOR\n\nVolumen del mes: $0.00 USDT\n\nSin datos de volumen registrados.\nConfigura tus productos y activos digitales para comenzar.');
        }

        function showGroupVolumeDetails() {
            alert('DETALLE DE VOLUMEN GRUPAL CONSTRUCTOR\n\nVolumen total 16 niveles: $0.00 USDT\n\nSin datos de volumen grupal.\nConstruye tu equipo para generar volumen grupal.');
        }

        function showTotalVolumeDetails() {
            alert('DETALLE DE VOLUMEN TOTAL CONSTRUCTOR\n\nVolumen total matriz completa: $0.00 USDT\n\nSin datos de volumen total.\nTu matriz completa se poblará a medida que crezcas.');
        }

        function removeExistingModal(id) {
            var modal = document.getElementById(id);
            if (modal) modal.remove();
        }

        function createEmptyModal(type, title, message, hint) {
            removeExistingModal(`${type}-modal`);
            document.body.insertAdjacentHTML('beforeend', `
                <div id="${type}-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:500px;width:90%;text-align:center;">
                        <h2 style="color:#ffc107;font-size:24px;margin-bottom:20px;">${title}</h2>
                        <p style="color:rgba(255,255,255,0.8);font-size:16px;margin-bottom:15px;">Total: 0</p>
                        <p style="color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:10px;">${message}</p>
                        <p style="color:#007aff;font-size:13px;margin-bottom:25px;">${hint}</p>
                        <button onclick="removeExistingModal('${type}-modal')" style="background:linear-gradient(135deg,#ffc107,#f59e0b);color:#000;border:none;padding:12px 30px;border-radius:25px;font-weight:600;cursor:pointer;">Cerrar</button>
                    </div>
                </div>
            `);
        }

        function showClientsDetails() {
            const total = window.allClientsData.length;
            if (total === 0) {
                createEmptyModal('clients', 'Clientes Directos', 'Aún no tienes clientes registrados.', 'Comparte tu CORE LINK para comenzar a atraer clientes.');
                return;
            }
            createClientsModal();
        }

        function createClientsModal() {
            removeExistingModal('clients-modal');
            const total = window.allClientsData.length;
            const totalCommission = window.allClientsData.reduce((sum, c) => sum + (c.commission || 0), 0);
            const totalConsumption = window.allClientsData.reduce((sum, c) => sum + (c.consumption || 0), 0);

            // Calcular estadísticas para gráfica
            const activeClients = window.allClientsData.filter(c => c.status === 'Activo').length;
            const inactiveClients = total - activeClients;

            document.body.insertAdjacentHTML('beforeend', `
                <div id="clients-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:1000px;width:95%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:#ffc107;font-size:24px;font-weight:600;">🏆 Clientes Directos (${total} total)</h2>
                            <button onclick="removeExistingModal('clients-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>

                        <!-- Estadísticas con Gráfica -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">📊 Distribución de Clientes</h3>
                                <div style="height:180px;display:flex;justify-content:center;align-items:center;">
                                    <canvas id="clientsChart" width="180" height="180"></canvas>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">💰 Resumen Financiero</h3>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                                    <div style="background:rgba(16,185,129,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Consumo Total</div>
                                        <div style="color:#10b981;font-size:22px;font-weight:700;">$${totalConsumption.toFixed(0)}</div>
                                    </div>
                                    <div style="background:rgba(255,193,7,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Comisiones</div>
                                        <div style="color:#ffc107;font-size:22px;font-weight:700;">$${totalCommission.toFixed(0)}</div>
                                    </div>
                                    <div style="background:rgba(59,130,246,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Activos</div>
                                        <div style="color:#3b82f6;font-size:22px;font-weight:700;">${activeClients}</div>
                                    </div>
                                    <div style="background:rgba(239,68,68,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Inactivos</div>
                                        <div style="color:#ef4444;font-size:22px;font-weight:700;">${inactiveClients}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="clients-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:15px;margin-top:20px;">
                            ${generateClientsListHTML()}
                        </div>
                    </div>
                </div>
            `);

            // Crear gráfica de clientes
            setTimeout(() => {
                const ctx = document.getElementById('clientsChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Activos', 'Inactivos'],
                            datasets: [{
                                data: [activeClients, inactiveClients],
                                backgroundColor: ['#10b981', '#ef4444'],
                                borderColor: ['#059669', '#dc2626'],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: '#fff', font: { size: 11 } }
                                }
                            }
                        }
                    });
                }
            }, 100);
        }

        function generateClientsListHTML() {
            if (window.allClientsData.length === 0) return '<p style="color:rgba(255,255,255,0.6);text-align:center;grid-column:1/-1;">No hay clientes registrados</p>';
            return window.allClientsData.slice(0, 20).map((client, index) => `
                <div onclick="showClientDetails('${client.name}')" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:15px;cursor:pointer;transition:all 0.3s;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                        <div style="color:#fff;font-weight:600;font-size:14px;">${index < 10 ? '⭐' : ''} ${client.name}</div>
                        <div style="background:rgba(255,193,7,0.2);color:#ffc107;font-size:10px;padding:2px 6px;border-radius:8px;">${client.status}</div>
                    </div>
                    <div style="color:#ffc107;font-size:16px;font-weight:700;margin-bottom:5px;">$${client.consumption} USDT</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:11px;">Tu comisión: $${client.commission} • Via: ${client.coreSource || 'Directo'}</div>
                </div>
            `).join('');
        }

        function showClientDetails(clientName) {
            const client = window.allClientsData.find(c => c.name === clientName);
            if (!client) return;
            alert(`CLIENTE: ${client.name}\n\nConsumo: $${client.consumption} USDT\nTu comisión: $${client.commission}\nEstatus: ${client.status}\nÚltima compra: ${client.lastPurchase}\nOrigen: ${client.coreSource || 'Directo'}\nWhatsApp: ${client.whatsapp}`);
        }

        // MODAL DE AFILIADOS
        function showAffiliatesDetails() {
            const total = window.allAffiliatesData.length;
            if (total === 0) {
                createEmptyModal('affiliates', 'Afiliados en mi Línea', 'Aún no tienes afiliados en tu línea.', 'Recluta nuevos miembros para construir tu organización.');
                return;
            }
            createAffiliatesModal();
        }

        function createAffiliatesModal() {
            removeExistingModal('affiliates-modal');
            const total = window.allAffiliatesData.length;
            const totalOverride = window.allAffiliatesData.reduce((sum, a) => sum + (a.overrideGenerated || 0), 0);
            const totalMonthlyEarnings = window.allAffiliatesData.reduce((sum, a) => sum + (a.monthlyEarnings || 0), 0);
            const totalTeamSize = window.allAffiliatesData.reduce((sum, a) => sum + (a.teamSize || 0), 0);

            // Preparar datos para gráfica de barras (top 5 afiliados)
            const topAffiliates = window.allAffiliatesData.slice(0, 5);
            const affiliateNames = topAffiliates.map(a => a.name.substring(0, 10));
            const affiliateEarnings = topAffiliates.map(a => a.monthlyEarnings || 0);

            document.body.insertAdjacentHTML('beforeend', `
                <div id="affiliates-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:1000px;width:95%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:#ffc107;font-size:24px;font-weight:600;">👥 Afiliados en mi Línea (${total} total)</h2>
                            <button onclick="removeExistingModal('affiliates-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>

                        <!-- Estadísticas con Gráfica -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">📊 Top 5 Afiliados por Ganancias</h3>
                                <div style="height:180px;">
                                    <canvas id="affiliatesChart"></canvas>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">💰 Resumen de Equipo</h3>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                                    <div style="background:rgba(139,92,246,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Total Afiliados</div>
                                        <div style="color:#8b5cf6;font-size:22px;font-weight:700;">${total}</div>
                                    </div>
                                    <div style="background:rgba(255,193,7,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Override Total</div>
                                        <div style="color:#ffc107;font-size:22px;font-weight:700;">$${totalOverride.toFixed(0)}</div>
                                    </div>
                                    <div style="background:rgba(16,185,129,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Ganancias Totales</div>
                                        <div style="color:#10b981;font-size:22px;font-weight:700;">$${totalMonthlyEarnings.toFixed(0)}</div>
                                    </div>
                                    <div style="background:rgba(59,130,246,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Tamaño Red</div>
                                        <div style="color:#3b82f6;font-size:22px;font-weight:700;">${totalTeamSize}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:15px;">
                            ${generateAffiliatesListHTML()}
                        </div>
                    </div>
                </div>
            `);

            // Crear gráfica de afiliados
            setTimeout(() => {
                const ctx = document.getElementById('affiliatesChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: affiliateNames,
                            datasets: [{
                                label: 'Ganancias Mensuales ($)',
                                data: affiliateEarnings,
                                backgroundColor: ['#ffc107', '#8b5cf6', '#10b981', '#3b82f6', '#f59e0b'],
                                borderColor: ['#d97706', '#7c3aed', '#059669', '#2563eb', '#d97706'],
                                borderWidth: 2,
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { color: '#fff' },
                                    grid: { color: 'rgba(255,255,255,0.1)' }
                                },
                                x: {
                                    ticks: { color: '#fff', font: { size: 10 } },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
            }, 100);
        }

        function generateAffiliatesListHTML() {
            if (window.allAffiliatesData.length === 0) return '<p style="color:rgba(255,255,255,0.6);text-align:center;grid-column:1/-1;">No hay afiliados registrados</p>';
            return window.allAffiliatesData.slice(0, 15).map((affiliate, index) => `
                <div onclick="showAffiliateDetails('${affiliate.name}')" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:15px;cursor:pointer;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <div style="color:#fff;font-weight:600;">${index < 5 ? '⭐' : ''} ${affiliate.name}</div>
                        <div style="background:rgba(255,193,7,0.2);color:#ffc107;font-size:10px;padding:2px 6px;border-radius:8px;">Nivel ${affiliate.level}</div>
                    </div>
                    <div style="color:#ffc107;font-size:16px;font-weight:700;">$${affiliate.monthlyEarnings}/mes</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:11px;">Tu override: $${affiliate.overrideGenerated} • Equipo: ${affiliate.teamSize}</div>
                </div>
            `).join('');
        }

        function showAffiliateDetails(affiliateName) {
            const affiliate = window.allAffiliatesData.find(a => a.name === affiliateName);
            if (!affiliate) return;
            alert(`AFILIADO: ${affiliate.name}\n\nNivel: ${affiliate.level}\nGanancias mensuales: $${affiliate.monthlyEarnings}\nTu override: $${affiliate.overrideGenerated}\nTamaño de equipo: ${affiliate.teamSize}\nCalificación: ${affiliate.qualification}\nIngreso: ${affiliate.joinDate}\nWhatsApp: ${affiliate.whatsapp}`);
        }

        // MODAL DE CONSTRUCTORES
        function showConstructorsDetails() {
            const total = window.allConstructorsData.length;
            if (total === 0) {
                createEmptyModal('constructors', 'Constructores en mi Línea', 'Aún no tienes constructores en tu línea.', 'Ayuda a tus afiliados a convertirse en Constructores.');
                return;
            }
            createConstructorsModal();
        }

        function createConstructorsModal() {
            removeExistingModal('constructors-modal');
            const total = window.allConstructorsData.length;
            const totalBonus = window.allConstructorsData.reduce((sum, c) => sum + (c.constructorBonus || 0), 0);
            const totalVolume = window.allConstructorsData.reduce((sum, c) => sum + (parseFloat(c.totalVolume) || 0), 0);
            const totalTeamSize = window.allConstructorsData.reduce((sum, c) => sum + (c.teamSize || 0), 0);

            // Datos para gráfica de niveles
            const levelCounts = {};
            window.allConstructorsData.forEach(c => {
                const level = c.level || 1;
                levelCounts[level] = (levelCounts[level] || 0) + 1;
            });
            const levelLabels = Object.keys(levelCounts).map(l => 'Nivel ' + l);
            const levelData = Object.values(levelCounts);

            document.body.insertAdjacentHTML('beforeend', `
                <div id="constructors-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:1000px;width:95%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:#ffc107;font-size:24px;font-weight:600;">👑 Constructores en mi Línea (${total} total)</h2>
                            <button onclick="removeExistingModal('constructors-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>

                        <!-- Estadísticas con Gráfica -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">📊 Distribución por Nivel</h3>
                                <div style="height:180px;display:flex;justify-content:center;align-items:center;">
                                    <canvas id="constructorsChart" width="180" height="180"></canvas>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">💰 Métricas de Constructores</h3>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                                    <div style="background:rgba(255,193,7,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Total Constructores</div>
                                        <div style="color:#ffc107;font-size:22px;font-weight:700;">${total}</div>
                                    </div>
                                    <div style="background:rgba(16,185,129,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Bono Total</div>
                                        <div style="color:#10b981;font-size:22px;font-weight:700;">$${totalBonus.toFixed(0)}</div>
                                    </div>
                                    <div style="background:rgba(59,130,246,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Volumen Total</div>
                                        <div style="color:#3b82f6;font-size:22px;font-weight:700;">$${totalVolume.toFixed(0)}</div>
                                    </div>
                                    <div style="background:rgba(139,92,246,0.15);border-radius:10px;padding:15px;text-align:center;">
                                        <div style="color:rgba(255,255,255,0.6);font-size:11px;text-transform:uppercase;">Red Total</div>
                                        <div style="color:#8b5cf6;font-size:22px;font-weight:700;">${totalTeamSize}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px;">
                            ${generateConstructorsListHTML()}
                        </div>
                    </div>
                </div>
            `);

            // Crear gráfica de constructores por nivel
            setTimeout(() => {
                const ctx = document.getElementById('constructorsChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'polarArea',
                        data: {
                            labels: levelLabels,
                            datasets: [{
                                data: levelData,
                                backgroundColor: [
                                    'rgba(255,193,7,0.7)',
                                    'rgba(139,92,246,0.7)',
                                    'rgba(16,185,129,0.7)',
                                    'rgba(59,130,246,0.7)',
                                    'rgba(245,158,11,0.7)'
                                ],
                                borderColor: [
                                    '#ffc107',
                                    '#8b5cf6',
                                    '#10b981',
                                    '#3b82f6',
                                    '#f59e0b'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: { color: '#fff', font: { size: 10 } }
                                }
                            },
                            scales: {
                                r: {
                                    ticks: { display: false },
                                    grid: { color: 'rgba(255,255,255,0.1)' }
                                }
                            }
                        }
                    });
                }
            }, 100);
        }

        function generateConstructorsListHTML() {
            if (window.allConstructorsData.length === 0) return '<p style="color:rgba(255,255,255,0.6);text-align:center;grid-column:1/-1;">No hay constructores registrados</p>';
            return window.allConstructorsData.map((constructor, index) => `
                <div onclick="showConstructorDetails('${constructor.name}')" style="background:linear-gradient(135deg,rgba(255,193,7,0.1),rgba(0,122,255,0.05));border:2px solid rgba(255,193,7,0.3);border-radius:12px;padding:20px;cursor:pointer;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <div style="color:#fff;font-weight:700;font-size:16px;">${index < 3 ? '👑' : ''} ${constructor.name}</div>
                        <div style="background:rgba(255,193,7,0.3);color:#ffc107;font-size:11px;padding:3px 8px;border-radius:8px;">Nivel ${constructor.level}</div>
                    </div>
                    <div style="color:#ffc107;font-size:18px;font-weight:700;margin-bottom:8px;">$${constructor.monthlyEarnings}/mes</div>
                    <div style="color:#007aff;font-size:14px;font-weight:600;margin-bottom:8px;">Tu bono: $${constructor.constructorBonus} (5%)</div>
                    <div style="color:rgba(255,255,255,0.7);font-size:12px;">Equipo: ${constructor.teamSize} • Vol. Total: $${constructor.totalVolume}</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:11px;">${constructor.qualification} • Activos: ${constructor.digitalAssets}</div>
                </div>
            `).join('');
        }

        function showConstructorDetails(constructorName) {
            const constructor = window.allConstructorsData.find(c => c.name === constructorName);
            if (!constructor) return;
            removeExistingModal('constructor-detail-modal');

            document.body.insertAdjacentHTML('beforeend', `
                <div id="constructor-detail-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3001;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:30px;max-width:800px;width:90%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
                            <h2 style="color:#ffc107;font-size:20px;font-weight:600;">👑 ${constructor.email || constructor.name} - Constructor Nivel ${constructor.level}</h2>
                            <button onclick="removeExistingModal('constructor-detail-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">💰 Métricas de Negocio</h3>
                                <div style="color:#fff;font-size:24px;font-weight:700;margin-bottom:5px;">${constructor.monthlyEarnings || 0}/mes</div>
                                <div style="color:rgba(255,255,255,0.6);font-size:12px;">Tu bono: ${constructor.constructorBonus || 0} (5%)</div>
                            </div>

                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">🎯 Productos Activos</h3>
                                <div style="color:rgba(255,255,255,0.7);font-size:13px;">${constructor.digitalAssets || 0} activos configurados</div>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">👥 Información de Equipo</h3>
                                <div style="color:rgba(255,255,255,0.7);font-size:13px;line-height:1.8;">
                                    <div>⚡ Nivel en línea: ${constructor.level}</div>
                                    <div>👥 Tamaño de equipo: ${constructor.teamSize} personas</div>
                                    <div>📅 Ingreso: ${constructor.joinDate}</div>
                                    <div>📱 WhatsApp: ${constructor.whatsapp || 'No registrado'}</div>
                                </div>
                            </div>

                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">
                                <h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">📊 Volúmenes</h3>
                                <div style="color:rgba(255,255,255,0.7);font-size:13px;line-height:1.8;">
                                    <div>Personal: ${constructor.personalVolume || 0}</div>
                                    <div>Grupal: ${constructor.groupVolume || 0}</div>
                                    <div>Total: ${constructor.totalVolume || 0}</div>
                                    <div>Calificación: ${constructor.qualification || 'Activo'}</div>
                                </div>
                            </div>
                        </div>

                        <div style="text-align:center;margin-top:20px;">
                            <h3 style="color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:15px;">🚀 Herramientas de Mentoring Constructor</h3>
                            <div style="display:flex;justify-content:center;gap:15px;flex-wrap:wrap;">
                                <button onclick="contactarConstructor('${constructor.whatsapp || ''}')" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                                    📱 Contactar Constructor
                                </button>
                                <button onclick="showAnalisisAvanzado('${constructor.name}')" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;border:none;padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                                    📊 Análisis Avanzado
                                </button>
                                <button onclick="showEstrategiaExpansion('${constructor.name}')" style="background:linear-gradient(135deg,#ffc107,#f59e0b);color:#000;border:none;padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                                    🚀 Estrategia Expansión
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }

        function contactarConstructor(whatsapp) {
            if (whatsapp) {
                window.open('https://wa.me/' + whatsapp.replace(/[^0-9]/g, ''), '_blank');
            } else {
                alert('Este constructor no tiene WhatsApp registrado.');
            }
        }

        function showAnalisisAvanzado(constructorName) {
            const constructor = window.allConstructorsData.find(c => c.name === constructorName);
            if (!constructor) return;
            removeExistingModal('analisis-modal');

            document.body.insertAdjacentHTML('beforeend', `
                <div id="analisis-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.9);backdrop-filter:blur(10px);z-index:3002;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:30px;max-width:750px;width:90%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
                            <h2 style="color:#ffc107;font-size:20px;font-weight:600;">👥 Análisis Avanzado - ${constructor.email || constructor.name}</h2>
                            <button onclick="removeExistingModal('analisis-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin-bottom:25px;">
                            <div style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:12px;padding:20px;text-align:center;">
                                <div style="color:rgba(255,255,255,0.7);font-size:11px;text-transform:uppercase;margin-bottom:8px;">TAMAÑO DE EQUIPO</div>
                                <div style="color:#fff;font-size:32px;font-weight:700;">${constructor.teamSize || 1}</div>
                                <div style="color:rgba(255,255,255,0.6);font-size:11px;">personas activas</div>
                            </div>
                            <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;padding:20px;text-align:center;">
                                <div style="color:rgba(255,255,255,0.7);font-size:11px;text-transform:uppercase;margin-bottom:8px;">VOLUMEN TOTAL</div>
                                <div style="color:#fff;font-size:32px;font-weight:700;">${constructor.totalVolume || 0}</div>
                                <div style="color:rgba(255,255,255,0.6);font-size:11px;">matriz completa</div>
                            </div>
                            <div style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);border-radius:12px;padding:20px;text-align:center;">
                                <div style="color:rgba(255,255,255,0.7);font-size:11px;text-transform:uppercase;margin-bottom:8px;">ACTIVOS DIGITALES</div>
                                <div style="color:#fff;font-size:32px;font-weight:700;">${constructor.digitalAssets || 0}</div>
                                <div style="color:rgba(255,255,255,0.6);font-size:11px;">configurados</div>
                            </div>
                            <div style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;padding:20px;text-align:center;">
                                <div style="color:rgba(0,0,0,0.7);font-size:11px;text-transform:uppercase;margin-bottom:8px;">BONO GENERADO</div>
                                <div style="color:#000;font-size:32px;font-weight:700;">${constructor.constructorBonus || 0}</div>
                                <div style="color:rgba(0,0,0,0.6);font-size:11px;">este mes</div>
                            </div>
                        </div>

                        <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;margin-bottom:20px;">
                            <h3 style="color:#3b82f6;font-size:16px;margin-bottom:15px;">📊 Diagnóstico de Expansión</h3>
                            <div style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.8;">
                                <p><span style="color:#10b981;font-weight:600;">Fortalezas Constructor:</span> Equipo sólido con ${constructor.digitalAssets || 0} activos digitales y volumen total de ${constructor.totalVolume || 0}.</p>
                                <p><span style="color:#ffc107;font-weight:600;">Oportunidades:</span> Potencial de expansión a nuevos mercados y optimización de CORE LINK system.</p>
                                <p><span style="color:#3b82f6;font-weight:600;">Recomendación:</span> Enfoque en desarrollo de Constructores Nivel 2 para multiplicar bonos y expansión a 16 niveles completos.</p>
                            </div>
                        </div>

                        <div style="display:flex;justify-content:center;gap:15px;">
                            <button onclick="contactarConstructor('${constructor.whatsapp || ''}')" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;">
                                📱 Contactar para Estrategia
                            </button>
                            <button onclick="removeExistingModal('analisis-modal')" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;">
                                ← Volver a Detalles
                            </button>
                        </div>
                    </div>
                </div>
            `);
        }

        function showEstrategiaExpansion(constructorName) {
            const constructor = window.allConstructorsData.find(c => c.name === constructorName);
            if (!constructor) return;
            removeExistingModal('estrategia-modal');

            document.body.insertAdjacentHTML('beforeend', `
                <div id="estrategia-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.9);backdrop-filter:blur(10px);z-index:3002;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:30px;max-width:750px;width:90%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
                            <h2 style="color:#f59e0b;font-size:20px;font-weight:600;">🚀 Estrategia de Expansión - ${constructor.email || constructor.name}</h2>
                            <button onclick="removeExistingModal('estrategia-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>

                        <div style="background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:20px;margin-bottom:20px;">
                            <h3 style="color:#ffc107;font-size:16px;margin-bottom:15px;">🎯 Plan Expansión 16 Niveles</h3>
                            <div style="color:rgba(255,255,255,0.8);font-size:13px;line-height:2;">
                                <p><strong>1. Objetivo Inmediato (90 días):</strong> Expandir equipo a 15+ miembros</p>
                                <p><strong>2. Meta de Volumen:</strong> Incrementar volumen total a ${(constructor.totalVolume || 0) * 1.5} (+50%)</p>
                                <p><strong>3. Desarrollo de Constructores:</strong> Mentoring intensivo para desarrollar 2-3 Constructores Nivel 2</p>
                                <p><strong>4. Optimización CORE LINK:</strong> Maximizar activos digitales configurados y evidencias mensuales para calificación completa</p>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:15px;">
                                <h4 style="color:#3b82f6;font-size:14px;margin-bottom:10px;">📈 Métricas de Expansión</h4>
                                <div style="color:rgba(255,255,255,0.7);font-size:12px;line-height:1.8;">
                                    <div>• Equipo objetivo: 15 personas</div>
                                    <div>• Constructores Nivel 2: 2-3 nuevos</div>
                                    <div>• Volumen total: ${constructor.totalVolume || 0}</div>
                                    <div>• Bono meta: ${(constructor.constructorBonus || 0) * 2}</div>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:15px;">
                                <h4 style="color:#10b981;font-size:14px;margin-bottom:10px;">⚡ Acciones Estratégicas</h4>
                                <div style="color:rgba(255,255,255,0.7);font-size:12px;line-height:1.8;">
                                    <div>• Reclutamiento enfocado en Constructores potenciales</div>
                                    <div>• Mentoring semanal con alto rendimiento</div>
                                    <div>• Optimización de CORE LINK system</div>
                                    <div>• Desarrollo de mercados internacionales</div>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;justify-content:center;gap:15px;">
                            <button onclick="contactarConstructor('${constructor.whatsapp || ''}')" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;">
                                📱 Coordinar Expansión
                            </button>
                            <button onclick="removeExistingModal('estrategia-modal')" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);padding:12px 25px;border-radius:25px;font-weight:600;cursor:pointer;">
                                ← Volver a Detalles
                            </button>
                        </div>
                    </div>
                </div>
            `);
        }

        // Función para calcular nivel WWB (calificación de por vida)
        // Fundadores están calificados de por vida a WWB5 (todos los pools)
        function calcularNivelWWB(frontalesTotal) {
            // Fundadores siempre son WWB5
            if (userData.isFounder) {
                return {
                    nivel: 'WWB5',
                    porcentaje: '5 pools',
                    descripcion: 'FUNDADOR - Calificado de por vida a todos los pools',
                    siguiente: null,
                    faltantes: 0,
                    isFounder: true
                };
            }
            if (frontalesTotal >= 2500) return { nivel: 'WWB5', porcentaje: '5 pools', descripcion: 'Participas en los 5 pools', siguiente: null, faltantes: 0 };
            if (frontalesTotal >= 500) return { nivel: 'WWB4', porcentaje: '4%', descripcion: 'Pool WWB4 exclusivo', siguiente: 'WWB5', faltantes: 2500 - frontalesTotal };
            if (frontalesTotal >= 150) return { nivel: 'WWB3', porcentaje: '3%', descripcion: 'Pool WWB3 exclusivo', siguiente: 'WWB4', faltantes: 500 - frontalesTotal };
            if (frontalesTotal >= 50) return { nivel: 'WWB2', porcentaje: '2%', descripcion: 'Pool WWB2 exclusivo', siguiente: 'WWB3', faltantes: 150 - frontalesTotal };
            if (frontalesTotal >= 20) return { nivel: 'WWB1', porcentaje: '1%', descripcion: 'Pool WWB1 exclusivo', siguiente: 'WWB2', faltantes: 50 - frontalesTotal };
            return { nivel: 'Sin nivel', porcentaje: '0%', descripcion: 'Aún no calificas', siguiente: 'WWB1', faltantes: 20 - frontalesTotal };
        }

        function calcularProgresoWWB(frontalesTotal) {
            // Fundadores siempre tienen 100%
            if (userData.isFounder) return 100;
            if (frontalesTotal >= 2500) return 100;
            if (frontalesTotal >= 500) return Math.round((frontalesTotal / 2500) * 100);
            if (frontalesTotal >= 150) return Math.round((frontalesTotal / 500) * 100);
            if (frontalesTotal >= 50) return Math.round((frontalesTotal / 150) * 100);
            if (frontalesTotal >= 20) return Math.round((frontalesTotal / 50) * 100);
            return Math.round((frontalesTotal / 20) * 100);
        }

        function showWWBDetails() {
            removeExistingModal('wwb-modal');
            var totalFrontales = window.allWWBFrontalesData.length;
            var wwbInfo = calcularNivelWWB(totalFrontales);
            var progreso = calcularProgresoWWB(totalFrontales);

            // Calcular datos para gráfica de barras de niveles WWB
            var wwbLevels = [20, 50, 150, 500, 2500];
            var wwbLabels = ['WWB1', 'WWB2', 'WWB3', 'WWB4', 'WWB5'];
            var userProgress = wwbLevels.map(function(lvl) { return Math.min(totalFrontales, lvl); });

            var html = '<div id="wwb-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">' +
                '<div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:1000px;width:95%;max-height:85vh;overflow-y:auto;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">' +
                '<h2 style="color:#ffc107;font-size:24px;font-weight:600;">🌍 WWB - World Wide Bonus</h2>' +
                '<button onclick="removeExistingModal(\'wwb-modal\')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>' +
                '</div>' +

                // Gráfica de progreso WWB
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">' +
                '<div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">' +
                '<h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">📊 Tu Progreso hacia cada Nivel</h3>' +
                '<div style="height:180px;"><canvas id="wwbChart"></canvas></div>' +
                '</div>' +
                '<div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:12px;padding:20px;">' +
                '<h3 style="color:#ffc107;font-size:14px;margin-bottom:15px;">🎯 Resumen WWB</h3>' +
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">' +
                '<div style="background:rgba(255,193,7,0.15);border-radius:10px;padding:12px;text-align:center;">' +
                '<div style="color:rgba(255,255,255,0.6);font-size:10px;text-transform:uppercase;">Nivel Actual</div>' +
                '<div style="color:#ffc107;font-size:20px;font-weight:700;">' + wwbInfo.nivel + '</div>' +
                '</div>' +
                '<div style="background:rgba(16,185,129,0.15);border-radius:10px;padding:12px;text-align:center;">' +
                '<div style="color:rgba(255,255,255,0.6);font-size:10px;text-transform:uppercase;">Frontales</div>' +
                '<div style="color:#10b981;font-size:20px;font-weight:700;">' + totalFrontales + '</div>' +
                '</div>' +
                '<div style="background:rgba(59,130,246,0.15);border-radius:10px;padding:12px;text-align:center;">' +
                '<div style="color:rgba(255,255,255,0.6);font-size:10px;text-transform:uppercase;">Progreso</div>' +
                '<div style="color:#3b82f6;font-size:20px;font-weight:700;">' + progreso + '%</div>' +
                '</div>' +
                '<div style="background:rgba(139,92,246,0.15);border-radius:10px;padding:12px;text-align:center;">' +
                '<div style="color:rgba(255,255,255,0.6);font-size:10px;text-transform:uppercase;">Siguiente</div>' +
                '<div style="color:#8b5cf6;font-size:20px;font-weight:700;">' + (wwbInfo.siguiente || 'MAX') + '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +

                // Explicación del WWB
                '<div style="background:linear-gradient(135deg,rgba(255,193,7,0.1),rgba(0,122,255,0.05));border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:20px;margin-bottom:20px;">' +
                '<h3 style="color:#ffc107;font-size:16px;margin-bottom:12px;">📋 ¿Qué es el WWB?</h3>' +
                '<p style="color:rgba(255,255,255,0.8);font-size:14px;line-height:1.6;margin-bottom:10px;">' +
                'El <strong style="color:#ffc107;">World Wide Bonus (WWB)</strong> es un bono de participación en un <strong>pool global</strong> calculado sobre las ventas totales de toda la organización LWC cada mes.' +
                '</p>' +
                '<p style="color:rgba(255,255,255,0.8);font-size:14px;line-height:1.6;">' +
                '<strong style="color:#10B981;">✓ La calificación al nivel WWB es DE POR VIDA</strong> una vez alcanzada. Sin embargo, para ser elegible a cobrar cada mes debes cumplir los requisitos mensuales.' +
                '</p>' +
                '</div>' +

                // Tu nivel actual
                '<div style="background:rgba(0,122,255,0.1);border:1px solid rgba(0,122,255,0.3);border-radius:12px;padding:20px;margin-bottom:20px;">' +
                '<h3 style="color:#007aff;font-size:16px;margin-bottom:15px;">📊 Tu Nivel WWB Actual</h3>' +
                '<div style="display:flex;align-items:center;gap:20px;margin-bottom:15px;">' +
                '<div style="background:linear-gradient(135deg,#ffc107,#f59e0b);color:#000;padding:15px 25px;border-radius:12px;font-weight:700;font-size:24px;">' + wwbInfo.nivel + '</div>' +
                '<div>' +
                '<div style="color:#fff;font-size:16px;font-weight:600;">' + wwbInfo.descripcion + '</div>' +
                '<div style="color:rgba(255,255,255,0.7);font-size:13px;">Frontales totales: ' + totalFrontales + '</div>' +
                '</div>' +
                '</div>' +
                (wwbInfo.siguiente ?
                    '<div style="margin-bottom:10px;">' +
                    '<div style="display:flex;justify-content:space-between;margin-bottom:5px;">' +
                    '<span style="color:rgba(255,255,255,0.8);font-size:13px;">Progreso hacia ' + wwbInfo.siguiente + '</span>' +
                    '<span style="color:#ffc107;font-size:13px;">' + progreso + '%</span>' +
                    '</div>' +
                    '<div style="background:rgba(255,255,255,0.1);border-radius:10px;height:12px;overflow:hidden;">' +
                    '<div style="background:linear-gradient(90deg,#ffc107,#f59e0b);height:100%;width:' + progreso + '%;border-radius:10px;transition:width 0.3s;"></div>' +
                    '</div>' +
                    '<div style="color:rgba(255,255,255,0.6);font-size:12px;margin-top:5px;">Faltan <strong style="color:#ffc107;">' + wwbInfo.faltantes + ' frontales</strong> para ' + wwbInfo.siguiente + '</div>' +
                    '</div>'
                : '<div style="color:#10B981;font-size:14px;font-weight:600;">🏆 ¡Máximo nivel alcanzado!</div>') +
                '</div>' +

                // Tabla de niveles
                '<div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:20px;margin-bottom:20px;">' +
                '<h3 style="color:#ffc107;font-size:16px;margin-bottom:15px;">📈 Niveles y Calificaciones</h3>' +
                '<table style="width:100%;border-collapse:collapse;font-size:14px;">' +
                '<tr style="border-bottom:1px solid rgba(255,193,7,0.3);">' +
                '<th style="text-align:left;padding:10px;color:#ffc107;">Nivel</th>' +
                '<th style="text-align:center;padding:10px;color:#ffc107;">Frontales</th>' +
                '<th style="text-align:center;padding:10px;color:#ffc107;">Pool</th>' +
                '<th style="text-align:left;padding:10px;color:#ffc107;">¿Qué cobra?</th>' +
                '</tr>' +
                '<tr style="border-bottom:1px solid rgba(255,255,255,0.1);' + (wwbInfo.nivel === 'WWB1' ? 'background:rgba(255,193,7,0.15);' : '') + '">' +
                '<td style="padding:10px;color:#fff;">WWB1</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.8);text-align:center;">20</td>' +
                '<td style="padding:10px;color:#007aff;text-align:center;font-weight:600;">1%</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.7);font-size:12px;">Solo pool WWB1</td>' +
                '</tr>' +
                '<tr style="border-bottom:1px solid rgba(255,255,255,0.1);' + (wwbInfo.nivel === 'WWB2' ? 'background:rgba(255,193,7,0.15);' : '') + '">' +
                '<td style="padding:10px;color:#fff;">WWB2</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.8);text-align:center;">50</td>' +
                '<td style="padding:10px;color:#007aff;text-align:center;font-weight:600;">2%</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.7);font-size:12px;">Solo pool WWB2</td>' +
                '</tr>' +
                '<tr style="border-bottom:1px solid rgba(255,255,255,0.1);' + (wwbInfo.nivel === 'WWB3' ? 'background:rgba(255,193,7,0.15);' : '') + '">' +
                '<td style="padding:10px;color:#fff;">WWB3</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.8);text-align:center;">150</td>' +
                '<td style="padding:10px;color:#007aff;text-align:center;font-weight:600;">3%</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.7);font-size:12px;">Solo pool WWB3</td>' +
                '</tr>' +
                '<tr style="border-bottom:1px solid rgba(255,255,255,0.1);' + (wwbInfo.nivel === 'WWB4' ? 'background:rgba(255,193,7,0.15);' : '') + '">' +
                '<td style="padding:10px;color:#fff;">WWB4</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.8);text-align:center;">500</td>' +
                '<td style="padding:10px;color:#007aff;text-align:center;font-weight:600;">4%</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.7);font-size:12px;">Solo pool WWB4</td>' +
                '</tr>' +
                '<tr style="' + (wwbInfo.nivel === 'WWB5' ? 'background:rgba(255,193,7,0.15);' : '') + '">' +
                '<td style="padding:10px;color:#ffc107;font-weight:600;">WWB5</td>' +
                '<td style="padding:10px;color:rgba(255,255,255,0.8);text-align:center;">2,500</td>' +
                '<td style="padding:10px;color:#10B981;text-align:center;font-weight:600;">5 pools</td>' +
                '<td style="padding:10px;color:#10B981;font-size:12px;font-weight:500;">Todos los pools</td>' +
                '</tr>' +
                '</table>' +
                '</div>' +

                // Explicación WWB5
                '<div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:20px;margin-bottom:20px;">' +
                '<h3 style="color:#10B981;font-size:16px;margin-bottom:12px;">💎 ¿Cómo funciona WWB5?</h3>' +
                '<p style="color:rgba(255,255,255,0.8);font-size:14px;line-height:1.6;margin-bottom:10px;">' +
                'Al calificar a <strong style="color:#ffc107;">WWB5</strong>, participas en <strong>cada pool por separado</strong>:' +
                '</p>' +
                '<ul style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.8;padding-left:20px;margin:0;">' +
                '<li>Cobras del <strong style="color:#007aff;">pool WWB1 (1%)</strong> junto con todos los calificados WWB1+</li>' +
                '<li>Cobras del <strong style="color:#007aff;">pool WWB2 (2%)</strong> junto con todos los calificados WWB2+</li>' +
                '<li>Cobras del <strong style="color:#007aff;">pool WWB3 (3%)</strong> junto con todos los calificados WWB3+</li>' +
                '<li>Cobras del <strong style="color:#007aff;">pool WWB4 (4%)</strong> junto con todos los calificados WWB4+</li>' +
                '<li>Cobras del <strong style="color:#007aff;">pool WWB5 (5%)</strong> junto con otros WWB5</li>' +
                '</ul>' +
                '<p style="color:#10B981;font-size:13px;margin-top:10px;"><strong>La suma de los 5 pools = 15%</strong>, pero cada uno se calcula independientemente.</p>' +
                '</div>' +

                // Requisitos para cobrar MENSUALMENTE
                '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:20px;margin-bottom:20px;">' +
                '<h3 style="color:#ef4444;font-size:16px;margin-bottom:12px;">⚠️ Requisitos MENSUALES para Cobrar</h3>' +
                '<p style="color:rgba(255,255,255,0.8);font-size:13px;margin-bottom:10px;">Aunque la calificación es de por vida, para cobrar tu nivel WWB completo cada mes necesitas <strong>AMBOS</strong> requisitos:</p>' +
                '<ul style="color:rgba(255,255,255,0.8);font-size:14px;line-height:1.8;padding-left:20px;margin:0;">' +
                '<li><strong style="color:#ffc107;">50% de tus frontales activos</strong> (no el 100%)</li>' +
                '<li><strong style="color:#ffc107;">1 nuevo miembro frontal</strong> (Afiliado o Constructor) ese mes</li>' +
                '</ul>' +
                '<div style="margin-top:12px;padding:10px;background:rgba(239,68,68,0.15);border-radius:8px;">' +
                '<p style="color:#ef4444;font-size:13px;margin:0;"><strong>⚠️ Si no cumples AMBOS requisitos:</strong> No cobras tu nivel calificado. Solo cobras el WWB al que califiques por defecto según tus frontales activos ese mes.</p>' +
                '</div>' +
                '</div>' +

                // Fecha de pago
                '<div style="background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:15px;margin-bottom:20px;text-align:center;">' +
                '<p style="color:#ffc107;font-size:14px;margin:0;">📅 <strong>Fecha de Pago:</strong> Día 15 de cada mes (calculado sobre ventas del mes anterior)</p>' +
                '</div>' +

                // Nota importante
                '<div style="background:rgba(0,122,255,0.1);border:1px solid rgba(0,122,255,0.3);border-radius:12px;padding:15px;">' +
                '<p style="color:#007aff;font-size:13px;margin:0;"><strong>📌 IMPORTANTE:</strong> Los frontales para WWB se cuentan del <strong>árbol de PATROCINIO</strong> (directos ilimitados), NO de la matriz forzada 2x16.</p>' +
                '</div>' +

                '</div></div>';

            document.body.insertAdjacentHTML('beforeend', html);

            // Crear gráfica de progreso WWB
            setTimeout(function() {
                var ctx = document.getElementById('wwbChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: wwbLabels,
                            datasets: [
                                {
                                    label: 'Tu Progreso',
                                    data: userProgress,
                                    backgroundColor: 'rgba(255,193,7,0.8)',
                                    borderColor: '#ffc107',
                                    borderWidth: 2,
                                    borderRadius: 5
                                },
                                {
                                    label: 'Requisito',
                                    data: wwbLevels,
                                    backgroundColor: 'rgba(255,255,255,0.15)',
                                    borderColor: 'rgba(255,255,255,0.3)',
                                    borderWidth: 1,
                                    borderRadius: 5
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: '#fff', font: { size: 10 } }
                                }
                            },
                            scales: {
                                y: {
                                    type: 'logarithmic',
                                    beginAtZero: false,
                                    ticks: { color: '#fff' },
                                    grid: { color: 'rgba(255,255,255,0.1)' }
                                },
                                x: {
                                    ticks: { color: '#fff' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
            }, 100);
        }

        function openLINK() {
            removeExistingModal('link-modal');
            var html = '<div id="link-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.9);backdrop-filter:blur(15px);z-index:3000;display:flex;align-items:center;justify-content:center;">' +
                '<div style="background:linear-gradient(135deg,rgba(0,122,255,0.1),rgba(255,193,7,0.05));backdrop-filter:blur(20px);border:2px solid rgba(0,122,255,0.5);border-radius:24px;padding:50px;max-width:600px;width:90%;text-align:center;box-shadow:0 0 60px rgba(0,122,255,0.3);">' +
                '<div style="width:100px;height:100px;background:linear-gradient(135deg,#007aff,#0051d5);border-radius:50%;margin:0 auto 30px;display:flex;align-items:center;justify-content:center;font-size:50px;box-shadow:0 0 40px rgba(0,122,255,0.5);">🤖</div>' +
                '<h2 style="color:#007aff;font-size:36px;margin-bottom:15px;font-weight:700;text-shadow:0 0 20px rgba(0,122,255,0.5);">LINK</h2>' +
                '<p style="color:#ffc107;font-size:18px;margin-bottom:25px;font-weight:600;">Latin Intelligence Network Keeper</p>' +
                '<p style="color:rgba(255,255,255,0.8);font-size:16px;margin-bottom:30px;line-height:1.7;">Tu asistente AI personal para Latin Wave Community.<br>Como JARVIS, pero para constructores de imperios digitales.</p>' +
                '<div style="background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:12px;padding:20px;margin-bottom:30px;text-align:left;">' +
                '<p style="color:#ffc107;font-size:14px;font-weight:600;margin-bottom:10px;">Capacidades de LINK:</p>' +
                '<ul style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.8;padding-left:20px;margin:0;">' +
                '<li>Analisis inteligente de tu organizacion</li>' +
                '<li>Estrategias personalizadas de crecimiento</li>' +
                '<li>Optimizacion de CORE LINK y activos digitales</li>' +
                '<li>Proyecciones de ingresos y comisiones</li>' +
                '<li>Soporte tecnico 24/7 con IA avanzada</li>' +
                '</ul></div>' +
                '<div style="background:rgba(0,122,255,0.2);border:1px solid rgba(0,122,255,0.4);border-radius:12px;padding:15px;margin-bottom:25px;">' +
                '<p style="color:#007aff;font-size:16px;font-weight:600;margin:0;">PROXIMAMENTE</p>' +
                '<p style="color:rgba(255,255,255,0.7);font-size:13px;margin:5px 0 0;">LINK esta en desarrollo activo - Powered by MML + n8n</p></div>' +
                '<button onclick="removeExistingModal(\'link-modal\')" style="background:linear-gradient(135deg,#007aff,#0051d5);color:#fff;border:none;padding:15px 40px;border-radius:30px;font-weight:700;font-size:16px;cursor:pointer;box-shadow:0 4px 20px rgba(0,122,255,0.4);">Cerrar</button>' +
                '</div></div>';
            document.body.insertAdjacentHTML('beforeend', html);
        }

        // EVENTOS Y INICIALIZACIÓN
        document.addEventListener('click', function(e) {
            var dropdown = document.getElementById('userDropdown');
            var avatar = document.querySelector('.user-avatar');
            var configOverlay = document.getElementById('configOverlay');

            if (!dropdown.contains(e.target) && !avatar.contains(e.target)) {
                closeUserDropdown();
            }

            if (e.target === configOverlay) {
                closeConfig();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initWaveSystem();
            setInterval(initWaveSystem, 3600000);
        });

        // Auto-abrir modal de configuración si viene desde página Beta
        window.addEventListener('DOMContentLoaded', function() {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openConfig') === 'true') {
                setTimeout(function() {
                    openConfig();
                }, 100);
            }
        });

        function goToAITools() {
            window.location.href = 'ai-tools-products.html?profile=constructor';
        }
    </script>
    
</body>
</html>
