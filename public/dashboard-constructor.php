<?php
// ================================================================
// DASHBOARD CONSTRUCTOR LWC - VERSIÓN LIMPIA CON MODALES COMPLETOS
// ================================================================

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? '';
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_profile = $_SESSION['user_type'] ?? 'constructor';
$username = $_SESSION['username'] ?? '';

if ($user_profile !== 'constructor') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Constructor - Latin Wave Community</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #000;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
            transition: color 0.5s ease;
        }

        body.diurno { color: #fff; background: rgba(0, 0, 0, 0.6); }
        body.nocturno { color: #fff; }

        .waves-background {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: -1;
            overflow: hidden;
        }

        .wave-video {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 2s ease-in-out;
        }

        .wave-video.active { opacity: 1; }

        .main-container { min-height: 100vh; position: relative; }

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
            width: 32px; height: 32px;
            background-image: url('https://i.imgur.com/Om6tGeX.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }

        .nav-menu { display: flex; gap: 40px; list-style: none; }

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
            text-shadow: 0 0 5px #ffc107, 0 0 10px #ffc107, 0 0 15px #ffc107, 0 0 20px #ffc107, 0 0 35px #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }

        .nav-item.active {
            color: #fff;
            text-shadow: 0 0 5px #ffc107, 0 0 10px #ffc107, 0 0 15px #ffc107, 0 0 20px #ffc107;
            background: rgba(255, 193, 7, 0.15);
        }

        .user-controls { display: flex; align-items: center; gap: 15px; position: relative; }

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
            width: 50px; height: 50px;
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

        .user-photo { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .user-placeholder { font-size: 20px; color: rgba(255, 255, 255, 0.7); }

        .user-dropdown {
            position: absolute;
            top: 60px; right: 0;
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

        .user-dropdown.active { opacity: 1; visibility: visible; transform: translateY(0); }
        .user-info { margin-bottom: 15px; }
        .user-name { font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 5px; }
        .user-id, .user-username { font-size: 12px; color: rgba(255, 255, 255, 0.7); margin-bottom: 3px; }
        .dropdown-divider { height: 1px; background: rgba(255, 193, 7, 0.3); margin: 15px 0; }

        .dropdown-link {
            display: block;
            padding: 10px 0;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .dropdown-link:hover { color: #ffc107; }

        .config-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .config-overlay.active { opacity: 1; visibility: visible; }

        .config-panel {
            position: absolute;
            top: 50%; left: 50%;
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

        .config-title { font-size: 20px; font-weight: 600; color: #ffc107; }

        .close-config {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
            width: 30px; height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-config:hover { color: #ffc107; }
        .config-section { margin-bottom: 25px; }

        .config-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffc107;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; color: rgba(255, 255, 255, 0.8); margin-bottom: 6px; font-weight: 500; }

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

        .form-input:focus, .form-select:focus { background: rgba(0, 0, 0, 0.9); border-color: #ffc107; }
        .form-input::placeholder { color: rgba(255, 255, 255, 0.6); }

        .photo-upload { display: flex; align-items: center; gap: 15px; }

        .current-photo {
            width: 60px; height: 60px;
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

        .upload-btn:hover { background: linear-gradient(135deg, #0051d5, #003d99); transform: translateY(-1px); }

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

        .asset-checkbox { margin-right: 8px; }
        .asset-details { flex: 1; }
        .asset-name { font-weight: 600; color: #ffc107; font-size: 14px; }

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

        .evidence-upload-container { display: flex; flex-direction: column; gap: 15px; }

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

        .evidence-upload-item:hover { background: rgba(0, 0, 0, 0.4); border-color: rgba(0, 122, 255, 0.5); }
        .evidence-upload-item.has-file { border-color: rgba(16, 185, 129, 0.5); background: rgba(16, 185, 129, 0.1); }
        .evidence-file-input { display: none; }

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

        .evidence-upload-btn:hover { background: linear-gradient(135deg, #0051d5, #003d99); transform: translateY(-1px); }
        .evidence-upload-btn.has-file { background: linear-gradient(135deg, #10B981, #059669); }
        .evidence-upload-btn.has-file:hover { background: linear-gradient(135deg, #059669, #047857); }
        .evidence-file-info { flex: 1; display: flex; flex-direction: column; gap: 3px; }
        .evidence-file-name { color: #fff; font-size: 13px; font-weight: 500; }
        .evidence-file-details { color: rgba(255, 255, 255, 0.6); font-size: 11px; }

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

        .evidence-remove-btn:hover { background: rgba(239, 68, 68, 0.3); border-color: rgba(239, 68, 68, 0.5); }

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

        .add-evidence-btn:hover { background: rgba(0, 122, 255, 0.3); border-color: rgba(0, 122, 255, 0.5); }

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

        .dashboard-content { padding: 60px 40px; max-width: 1400px; margin: 0 auto; }
        .welcome-section { text-align: center; margin-bottom: 60px; }

        .welcome-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #fff;
            text-shadow: 0 0 5px #ffc107, 0 0 10px #ffc107, 0 0 15px #ffc107, 0 0 20px #ffc107, 0 0 35px #ffc107;
        }

        .welcome-subtitle {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
            color: #fff;
            text-shadow: 0 0 5px #007aff, 0 0 10px #007aff, 0 0 15px #007aff, 0 0 20px #007aff;
        }

        .core-link-section { margin-bottom: 60px; }

        .core-link-container {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(0, 122, 255, 0.05));
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .core-link-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .core-link-title { font-size: 20px; font-weight: 700; color: #ffc107; }

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

        .edit-core-link:hover { background: linear-gradient(135deg, #0051d5, #003d99); transform: translateY(-1px); }

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

        .core-link-display { display: flex; gap: 10px; align-items: center; margin-bottom: 15px; }

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

        .copy-btn:hover { background: linear-gradient(135deg, #f59e0b, #d97706); }

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

        .core-link-stat-value { font-size: 24px; font-weight: bold; color: #007aff; margin-bottom: 5px; }
        .core-link-stat-label { font-size: 12px; opacity: 0.8; }

        .commissions-section { margin-bottom: 60px; }

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

        .commission-title { font-size: 24px; font-weight: 700; color: #ffc107; margin-bottom: 20px; }
        .commission-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }

        .commission-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .commission-amount { font-size: 28px; font-weight: 700; color: #ffc107; margin-bottom: 8px; }
        .commission-label { font-size: 14px; opacity: 0.8; }

        .products-section { margin-bottom: 60px; }
        .section-title { font-size: 28px; font-weight: 600; margin-bottom: 30px; text-align: left; color: #ffc107; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 40px; }

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

        .product-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }

        .product-icon {
            width: 50px; height: 50px;
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
            color: #000;
        }

        .product-status { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-active { background: rgba(0, 122, 255, 0.2); color: #007aff; border: 1px solid rgba(0, 122, 255, 0.3); }
        .status-inactive { background: rgba(150, 150, 150, 0.2); color: #999; border: 1px solid rgba(150, 150, 150, 0.3); }
        .product-name { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #fff; }
        .product-description { font-size: 14px; margin-bottom: 15px; line-height: 1.4; color: rgba(255, 255, 255, 0.7); }
        .product-stats { display: flex; justify-content: space-between; font-size: 12px; color: rgba(255, 255, 255, 0.6); }

        .stats-section { margin-bottom: 60px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; }

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

        .stat-card.clickeable:hover { border-color: rgba(255, 193, 7, 0.5); }
        .stat-value { font-size: 32px; font-weight: 700; color: #ffc107; margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: rgba(255, 255, 255, 0.8); }
        .upgrades-section { display: none; }

        .chat-widget {
            position: fixed;
            bottom: 30px; right: 30px;
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

        .chat-widget:hover { background: #ffc107; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255, 193, 7, 0.4); }

        @media (max-width: 768px) {
            .header { padding: 15px 20px; }
            .nav-menu { gap: 20px; }
            .dashboard-content { padding: 40px 20px; }
            .welcome-title { font-size: 36px; }
            .products-grid, .core-link-stats { grid-template-columns: 1fr; }
            .config-panel { padding: 30px 20px; width: 95%; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
        }
    </style>
</head>
<body id="bodyElement">
    <!-- PANEL DE CONFIGURACIÓN -->
    <div class="config-overlay" id="configOverlay">
        <div class="config-panel">
            <div class="config-header">
                <h2 class="config-title">Configuración de Perfil Constructor</h2>
                <button class="close-config" onclick="closeConfig()">×</button>
            </div>

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
            </div>

            <div class="config-section">
                <h3 class="config-section-title">📸 Foto de Perfil</h3>
                <div class="photo-upload">
                    <div class="current-photo" id="configPhoto"><span>👤</span></div>
                    <div>
                        <input type="file" id="photoInput" accept="image/*" style="display: none;" onchange="handlePhotoUpload(event)">
                        <button class="upload-btn" onclick="document.getElementById('photoInput').click()">Cambiar Foto</button>
                        <p style="font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 5px;">Máximo 2MB - JPG, PNG</p>
                    </div>
                </div>
            </div>

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

                    <div class="upload-evidence">
                        <h4 style="color: #007aff; margin-bottom: 15px;">📄 Evidencias de Actividad Mensual</h4>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 15px; font-size: 13px;">
                            Sube cualquier documento que demuestre actividad en tus activos digitales del mes actual.
                        </p>
                        <div class="evidence-upload-container" id="evidenceContainer"></div>
                        <button class="add-evidence-btn" onclick="addNewEvidence()">➕ Agregar Nueva Evidencia</button>
                        <div class="config-info" style="margin-top: 15px;">
                            💡 <strong>Sistema Flexible:</strong> Puedes subir cualquier tipo de archivo. Tamaño máximo: 10MB
                        </div>
                    </div>
                </div>
            </div>

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

            <div class="config-section">
                <h3 class="config-section-title">🔒 Seguridad</h3>
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-input" placeholder="Dejar vacío para no cambiar">
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

            <div style="margin-top: 30px;">
                <button class="config-button" onclick="saveProfile()">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <!-- FONDO DE OLAS -->
    <div class="waves-background">
        <video class="wave-video" id="morningWaves" muted loop><source src="https://i.imgur.com/0oSrU7z.mp4" type="video/mp4"></video>
        <video class="wave-video" id="eveningWaves" muted loop><source src="https://i.imgur.com/xGCH0j0.mp4" type="video/mp4"></video>
        <video class="wave-video" id="nightWaves" muted loop><source src="https://i.imgur.com/kDhMoFE.mp4" type="video/mp4"></video>
    </div>

    <!-- CONTAINER PRINCIPAL -->
    <div class="main-container">
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
                    <span class="user-placeholder" id="avatarPlaceholder">👤</span>
                    <img class="user-photo" id="userPhoto" style="display: none;" src="" alt="Foto de perfil">
                </div>

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

        <main class="dashboard-content">
            <section class="welcome-section">
                <h1 class="welcome-title">Dashboard Constructor</h1>
                <p class="welcome-subtitle">
                    Gestiona tu negocio LWC con acceso completo a 16 niveles, CORE LINK system y beneficios de Constructor.
                    Calificación de por vida: Aprovecha tus beneficios exclusivos y construye tu imperio digital.
                </p>
            </section>

            <section class="core-link-section">
                <h2 class="section-title">🔗 CORE LINK System</h2>
                <div class="core-link-container">
                    <div class="core-link-header">
                        <h3 class="core-link-title">Sistema de Enlace Inteligente</h3>
                        <button class="edit-core-link" onclick="editCoreLink()">⚙️ Configurar</button>
                    </div>
                    <div class="core-link-warning">
                        ⚠️ CONFIGURA TUS ACTIVOS: Tu CORE LINK fusiona <span id="assetsCount">0</span> activos digitales configurados
                    </div>
                    <div class="core-link-display">
                        <input type="text" class="core-link-input" value="https://latinwave.community/corelink?id=<?php echo htmlspecialchars($user_id); ?>" readonly id="coreLink">
                        <button class="copy-btn" onclick="copyCoreLink()">Copiar CORE LINK</button>
                    </div>
                    <div class="core-link-stats">
                        <div class="core-link-stat">
                            <div class="core-link-stat-value" id="statAssets">0</div>
                            <div class="core-link-stat-label">Activos Configurados</div>
                        </div>
                        <div class="core-link-stat">
                            <div class="core-link-stat-value" id="statRegistros">0</div>
                            <div class="core-link-stat-label">Registros CORE LINK</div>
                        </div>
                        <div class="core-link-stat">
                            <div class="core-link-stat-value" id="statComisiones">$0.00</div>
                            <div class="core-link-stat-label">Comisiones Generadas</div>
                        </div>
                        <div class="core-link-stat">
                            <div class="core-link-stat-value">—</div>
                            <div class="core-link-stat-label">Status Mensual</div>
                        </div>
                    </div>
                </div>
            </section>

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
                        <div class="stat-value" id="clientsCount">0</div>
                        <div class="stat-label">Clientes Directos</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showAffiliatesDetails()">
                        <div class="stat-value" id="affiliatesCount">0</div>
                        <div class="stat-label">Afiliados en mi Línea</div>
                    </div>
                    <div class="stat-card clickeable" onclick="showConstructorsDetails()">
                        <div class="stat-value" id="constructorsCount">0</div>
                        <div class="stat-label">Constructores en mi Línea</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">0</div>
                        <div class="stat-label">Frontales Totales (WWB)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">—</div>
                        <div class="stat-label">Calificación</div>
                    </div>
                </div>
            </section>
        </main>

        <div class="chat-widget" onclick="openChat()">💬 SOPORTE CONSTRUCTOR</div>
    </div>

    <script>
        // DATOS DE USUARIO - DINÁMICOS DESDE PHP
        const userData = {
            id: '<?php echo htmlspecialchars($user_id); ?>',
            username: '<?php echo htmlspecialchars($username); ?>',
            fullName: '<?php echo htmlspecialchars($user_name); ?>',
            email: '<?php echo htmlspecialchars($user_email); ?>',
            photo: null,
            twoFactorEnabled: false,
            status: 'constructor',
            monthlyVolume: 0,
            groupVolume: 0,
            totalVolume: 0,
            totalCommissions: 0,
            frontales: 0,
            wwbLevel: '',
            paymentMethod: '',
            paymentInfo: '',
            currency: '',
            coreLink: 'https://latinwave.community/corelink?id=<?php echo htmlspecialchars($user_id); ?>',
            digitalAssets: {
                leadlightning: { active: false, id: '', verified: false },
                notion: { active: false, id: '', verified: false },
                goe1ulife: { active: false, id: '', verified: false },
                comizion: { active: false, id: '', verified: false },
                fbleadgen: { active: false, id: '', verified: false },
                saveclub: { active: false, id: '', verified: false },
                livegood: { active: false, id: '', verified: false },
                vitalhealth: { active: false, id: '', verified: false }
            }
        };

        // DATOS RESETEADOS - ARRAYS VACÍOS (se llenarán desde API/BD)
        window.allClientsData = [];
        window.allAffiliatesData = [];
        window.allConstructorsData = [];

        // SISTEMA DE EVIDENCIAS
        let evidenceCounter = 0;

        function handleEvidenceUpload(evidenceId, event) {
            const file = event.target.files[0];
            if (!file) return;
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('Error: El archivo es demasiado grande. Tamaño máximo permitido: 10MB');
                event.target.value = '';
                return;
            }
            const evidenceItem = document.getElementById(`evidence_${evidenceId}`).closest('.evidence-upload-item');
            const button = evidenceItem.querySelector('.evidence-upload-btn');
            const fileInfo = evidenceItem.querySelector('.evidence-file-info');
            evidenceItem.classList.add('has-file');
            button.classList.add('has-file');
            button.textContent = '📎 Cambiar';
            fileInfo.innerHTML = `<div class="evidence-file-name">${file.name}</div><div class="evidence-file-details">${getFileExtension(file.name).toUpperCase()} • ${formatFileSize(file.size)} • Subido ahora</div>`;
        }

        function removeEvidence(evidenceId) {
            if (confirm('¿Estás seguro de que deseas eliminar esta evidencia?')) {
                document.getElementById(`evidence_${evidenceId}`).closest('.evidence-upload-item').remove();
            }
        }

        function addNewEvidence() {
            evidenceCounter++;
            const container = document.getElementById('evidenceContainer');
            container.insertAdjacentHTML('beforeend', `
                <div class="evidence-upload-item">
                    <input type="file" class="evidence-file-input" id="evidence_${evidenceCounter}" accept="*/*" onchange="handleEvidenceUpload(${evidenceCounter}, event)">
                    <button class="evidence-upload-btn" onclick="document.getElementById('evidence_${evidenceCounter}').click()">📎 Subir Archivo</button>
                    <div class="evidence-file-info">
                        <div class="evidence-file-name">Sin archivo seleccionado</div>
                        <div class="evidence-file-details">Selecciona cualquier tipo de archivo para subir</div>
                    </div>
                    <button class="evidence-remove-btn" onclick="removeEvidence(${evidenceCounter})">🗑️ Eliminar</button>
                </div>
            `);
        }

        function getFileExtension(filename) { return filename.split('.').pop() || 'archivo'; }
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // NAVEGACIÓN
        class NavigationManager {
            constructor() {
                this.currentSection = 'dashboard';
                this.sections = {
                    'home': { title: 'Inicio - Latin Wave Community', content: null, requiresAuth: false },
                    'dashboard': { title: 'Dashboard Constructor - Latin Wave Community', content: 'dashboard-content', requiresAuth: true },
                    'ai-tools-products': { title: 'AI Tools + Products - Latin Wave Community', content: null, requiresAuth: true },
                    'team-room': { title: 'Team Room - Latin Wave Community', content: null, requiresAuth: true }
                };
            }
            navigateTo(sectionId) {
                if (!this.sections[sectionId]) return;
                if (this.sections[sectionId].requiresAuth && !this.isUserAuthenticated()) { this.redirectToLogin(); return; }
                this.updateActiveNavItem(sectionId);
                this.showSectionContent(sectionId);
                document.title = this.sections[sectionId].title;
                this.currentSection = sectionId;
            }
            updateActiveNavItem(activeSectionId) {
                document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
                const sectionMap = { 'home': 0, 'dashboard': 1, 'ai-tools-products': 2, 'team-room': 3 };
                const navItems = document.querySelectorAll('.nav-item');
                if (navItems[sectionMap[activeSectionId]]) navItems[sectionMap[activeSectionId]].classList.add('active');
            }
            showSectionContent(sectionId) {
                const section = this.sections[sectionId];
                if (section.content === 'dashboard-content') {
                    document.querySelector('.main-container').style.display = 'block';
                    const placeholder = document.getElementById('section-placeholder');
                    if (placeholder) placeholder.remove();
                } else if (section.content === null) {
                    this.showPlaceholderContent(sectionId);
                }
            }
            showPlaceholderContent(sectionId) {
                const names = { 'home': 'Página Principal', 'ai-tools-products': 'AI Tools + Products', 'team-room': 'Team Room' };
                const descs = { 'home': 'Página principal con información general de Latin Wave Community', 'ai-tools-products': 'Herramientas de IA y catálogo completo de productos LWC', 'team-room': 'Sala de equipo con chat, recursos y herramientas colaborativas' };
                const existing = document.getElementById('section-placeholder');
                if (existing) existing.remove();
                document.body.insertAdjacentHTML('beforeend', `
                    <div id="section-placeholder" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);display:flex;align-items:center;justify-content:center;z-index:1500;">
                        <div style="text-align:center;max-width:600px;padding:40px;background:rgba(255,255,255,0.08);backdrop-filter:blur(15px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;">
                            <h2 style="color:#ffc107;font-size:32px;margin-bottom:20px;">🚧 ${names[sectionId]}</h2>
                            <p style="color:rgba(255,255,255,0.8);font-size:16px;margin-bottom:30px;line-height:1.6;">${descs[sectionId]}</p>
                            <div style="background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);color:#ffc107;padding:20px;border-radius:12px;margin-bottom:30px;"><strong>PRÓXIMAMENTE</strong><br>Esta sección se encuentra en desarrollo.</div>
                            <button onclick="navigationManager.navigateTo('dashboard')" style="background:linear-gradient(135deg,#ffc107,#f59e0b);color:#000;border:none;padding:12px 30px;border-radius:25px;font-weight:600;cursor:pointer;">← Volver al Dashboard</button>
                        </div>
                    </div>
                `);
            }
            isUserAuthenticated() { return userData && userData.id; }
            redirectToLogin() { alert('Sesión requerida. Redirigiendo al login...'); }
        }

        const navigationManager = new NavigationManager();
        function navigateToSection(sectionId) { navigationManager.navigateTo(sectionId); }

        // SISTEMA DE OLAS POR HORARIO
        function initWaveSystem() {
            const hour = new Date().getHours();
            const videos = [document.getElementById('morningWaves'), document.getElementById('eveningWaves'), document.getElementById('nightWaves')];
            videos.forEach(v => { v.classList.remove('active'); v.pause(); });
            let activeVideo;
            if (hour >= 6 && hour < 12) activeVideo = videos[0];
            else if (hour >= 12 && hour < 19) activeVideo = videos[1];
            else activeVideo = videos[2];
            document.getElementById('bodyElement').className = 'nocturno';
            activeVideo.classList.add('active');
            activeVideo.play().catch(e => console.log('Autoplay prevented:', e));
        }

        // FUNCIONES DE USUARIO
        function toggleUserDropdown() { document.getElementById('userDropdown').classList.toggle('active'); }
        function closeUserDropdown() { document.getElementById('userDropdown').classList.remove('active'); }
        function openConfig() { document.getElementById('configOverlay').classList.add('active'); closeUserDropdown(); loadUserData(); }
        function closeConfig() { document.getElementById('configOverlay').classList.remove('active'); }

        function loadUserData() {
            document.getElementById('fullName').value = userData.fullName || '';
            document.getElementById('username').value = userData.username || '';
            document.getElementById('email').value = userData.email || '';
            document.getElementById('paymentMethod').value = userData.paymentMethod || '';
            document.getElementById('currency').value = userData.currency || '';
            document.getElementById('paymentInfo').value = userData.paymentInfo || '';
        }

        function updatePaymentFields() {
            const method = document.getElementById('paymentMethod').value;
            const label = document.getElementById('paymentLabel');
            const input = document.getElementById('paymentInfo');
            switch(method) {
                case 'paypal': label.textContent = 'Email de PayPal'; input.placeholder = 'tu-email@paypal.com'; break;
                case 'mercadopago': label.textContent = 'Email de MercadoPago'; input.placeholder = 'tu-email@mercadopago.com'; break;
                case 'btc': label.textContent = 'Dirección BTC Wallet'; input.placeholder = 'bc1q...'; break;
                case 'binance': label.textContent = 'Binance ID'; input.placeholder = '123456789'; break;
                default: label.textContent = 'Información de Pago'; input.placeholder = 'Selecciona un método de pago primero';
            }
        }

        function handlePhotoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) { alert('El archivo es demasiado grande. Máximo 2MB.'); return; }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const photoData = e.target.result;
                    document.getElementById('configPhoto').innerHTML = `<img src="${photoData}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">`;
                    document.getElementById('userPhoto').src = photoData;
                    document.getElementById('userPhoto').style.display = 'block';
                    document.getElementById('avatarPlaceholder').style.display = 'none';
                    userData.photo = photoData;
                };
                reader.readAsDataURL(file);
            }
        }

        function toggle2FA() {
            const status = document.getElementById('twoFactorStatus');
            if (status.textContent === 'Habilitado') {
                status.textContent = 'No configurado';
                status.style.background = 'rgba(150,150,150,0.2)';
                status.style.color = '#999';
                userData.twoFactorEnabled = false;
                alert('2FA deshabilitado correctamente');
            } else {
                const code = prompt('Ingresa el código de Google Authenticator para activar 2FA:');
                if (code && code.length === 6) {
                    status.textContent = 'Habilitado';
                    status.style.background = 'rgba(0,122,255,0.2)';
                    status.style.color = '#007aff';
                    userData.twoFactorEnabled = true;
                    alert('2FA activado correctamente');
                } else {
                    alert('Código inválido. 2FA no activado.');
                }
            }
        }

        function saveProfile() {
            userData.fullName = document.getElementById('fullName').value;
            userData.username = document.getElementById('username').value;
            userData.email = document.getElementById('email').value;
            userData.paymentMethod = document.getElementById('paymentMethod').value;
            userData.currency = document.getElementById('currency').value;
            userData.paymentInfo = document.getElementById('paymentInfo').value;
            document.getElementById('dropdownName').textContent = userData.fullName || 'Sin nombre';
            document.getElementById('dropdownUsername').textContent = '@' + (userData.username || 'usuario');

            // Guardar activos digitales
            const assets = ['leadlightning', 'notion', 'goe1ulife', 'comizion', 'fbleadgen', 'saveclub', 'livegood', 'vitalhealth'];
            assets.forEach(asset => {
                const checkbox = document.getElementById(asset);
                const input = checkbox.closest('.asset-item').querySelector('.asset-input');
                userData.digitalAssets[asset] = {
                    active: checkbox.checked,
                    id: input.value,
                    verified: false
                };
            });

            // Actualizar conteo de activos
            const activeAssets = Object.values(userData.digitalAssets).filter(a => a.active && a.id).length;
            document.getElementById('assetsCount').textContent = activeAssets;
            document.getElementById('statAssets').textContent = activeAssets;

            // TODO: Enviar a API para guardar en BD
            alert(`Perfil actualizado correctamente\nActivos digitales configurados: ${activeAssets}`);
            closeConfig();
        }

        function logout() { if (confirm('¿Seguro que deseas cerrar sesión?')) window.location.href = 'logout.php'; }

        // FUNCIONES CORE LINK
        function editCoreLink() { openConfig(); }
        function copyCoreLink() {
            const input = document.getElementById('coreLink');
            input.select();
            document.execCommand('copy');
            alert('¡CORE LINK copiado al portapapeles!\nCualquier ID de activo digital asociado reconocerá tu patrocinio automáticamente.');
        }

        // FUNCIONES DE DETALLES
        function showVolumeDetails() {
            alert('DETALLE DE VOLUMEN PERSONAL CONSTRUCTOR\n\nVolumen del mes: $0.00 USDT\n\nSin datos de volumen registrados.\nConfigura tus productos y activos digitales para comenzar.');
        }

        function showGroupVolumeDetails() {
            alert('DETALLE DE VOLUMEN GRUPAL CONSTRUCTOR\n\nVolumen total 16 niveles: $0.00 USDT\n\nSin datos de volumen grupal.\nConstruye tu equipo para generar volumen grupal.');
        }

        function showTotalVolumeDetails() {
            alert('DETALLE DE VOLUMEN TOTAL CONSTRUCTOR\n\nVolumen total matriz completa: $0.00 USDT\n\nSin datos de volumen total.\nTu matriz completa se poblará a medida que crezcas.');
        }

        // MODAL DE CLIENTES
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

            document.body.insertAdjacentHTML('beforeend', `
                <div id="clients-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:900px;width:90%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:#ffc107;font-size:24px;font-weight:600;">🏆 Clientes Directos (${total} total)</h2>
                            <button onclick="removeExistingModal('clients-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>
                        <div style="margin-bottom:20px;">
                            <p style="color:rgba(255,255,255,0.8);margin-bottom:15px;">💰 Comisiones totales: <strong style="color:#ffc107;">$${totalCommission.toFixed(2)} USDT (50% directas)</strong></p>
                        </div>
                        <div id="clients-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:15px;margin-top:20px;">
                            ${generateClientsListHTML()}
                        </div>
                    </div>
                </div>
            `);
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

            document.body.insertAdjacentHTML('beforeend', `
                <div id="affiliates-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:900px;width:90%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:#ffc107;font-size:24px;font-weight:600;">👥 Afiliados en mi Línea (${total} total)</h2>
                            <button onclick="removeExistingModal('affiliates-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>
                        <div style="margin-bottom:20px;">
                            <p style="color:rgba(255,255,255,0.8);">💰 Override total: <strong style="color:#ffc107;">$${totalOverride.toFixed(2)} USDT (50%)</strong></p>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:15px;">
                            ${generateAffiliatesListHTML()}
                        </div>
                    </div>
                </div>
            `);
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

            document.body.insertAdjacentHTML('beforeend', `
                <div id="constructors-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:3000;display:flex;align-items:center;justify-content:center;">
                    <div style="background:rgba(0,0,0,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,193,7,0.3);border-radius:20px;padding:40px;max-width:900px;width:90%;max-height:85vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:#ffc107;font-size:24px;font-weight:600;">👑 Constructores en mi Línea (${total} total)</h2>
                            <button onclick="removeExistingModal('constructors-modal')" style="background:none;border:none;color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;">×</button>
                        </div>
                        <div style="margin-bottom:20px;">
                            <p style="color:rgba(255,255,255,0.8);">💰 Bono Constructor total: <strong style="color:#ffc107;">$${totalBonus.toFixed(2)} USDT (5%)</strong></p>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px;">
                            ${generateConstructorsListHTML()}
                        </div>
                    </div>
                </div>
            `);
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
            alert(`CONSTRUCTOR: ${constructor.name}\n\nNivel: ${constructor.level}\nGanancias mensuales: $${constructor.monthlyEarnings}\nTu bono: $${constructor.constructorBonus} (5%)\nTamaño de equipo: ${constructor.teamSize}\nVolumen Total: $${constructor.totalVolume}\nActivos digitales: ${constructor.digitalAssets}\nCalificación: ${constructor.qualification}\nIngreso: ${constructor.joinDate}\nWhatsApp: ${constructor.whatsapp}`);
        }

        // MODAL VACÍO GENÉRICO
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

        function removeExistingModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.remove();
        }

        function openChat() {
            alert('SOPORTE CONSTRUCTOR PREMIUM\n\nSoporte especializado para Constructores:\n• Consultas sobre CORE LINK system\n• Gestión de 16 niveles y matriz completa\n• Estrategias de activos digitales\n• Bono Constructor y WWB optimization\n• Soporte técnico prioritario 24/7\n\nConectando con especialista Constructor...');
        }

        function goToAITools() { window.location.href = 'ai-tools-products.html?profile=constructor'; }

        // EVENTOS
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar');
            const configOverlay = document.getElementById('configOverlay');
            if (!dropdown.contains(e.target) && !avatar.contains(e.target)) closeUserDropdown();
            if (e.target === configOverlay) closeConfig();
        });

        document.addEventListener('DOMContentLoaded', function() {
            initWaveSystem();
            setInterval(initWaveSystem, 3600000);

            // Actualizar contadores
            document.getElementById('clientsCount').textContent = window.allClientsData.length;
            document.getElementById('affiliatesCount').textContent = window.allAffiliatesData.length;
            document.getElementById('constructorsCount').textContent = window.allConstructorsData.length;
        });

        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openConfig') === 'true') setTimeout(openConfig, 100);
        });
    </script>
    <script src="dashboard-api-connector.js"></script>
</body>
</html>
