<?php
session_start();

// Verificar que viene de verify-email
if (!isset($_SESSION['pending_user_id'])) {
    header('Location: login.php');
    exit;
}

// Conectar a MySQL
require_once __DIR__ . '/api/v7/config.php';

$user_id = $_SESSION['pending_user_id'];
$user_email = $_SESSION['pending_email'] ?? '';
$user_name = $_SESSION['pending_name'] ?? '';
$error = '';
$success = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $user_type = $_POST['user_type'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones
    if (empty($username) || empty($user_type) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($username) < 3) {
        $error = 'El username debe tener al menos 3 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'El username solo puede contener letras, números y guiones bajos.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!in_array($user_type, ['cliente', 'afiliado', 'constructor'])) {
        $error = 'Tipo de perfil inválido.';
    } else {
        try {
            // Verificar que username no exista
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Este username ya está en uso. Elige otro.';
            } else {
                // Actualizar usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, user_type = ?, password_hash = ? WHERE user_id = ?");
                $stmt->execute([$username, $user_type, $password_hash, $user_id]);

                // Limpiar sesión temporal
                unset($_SESSION['pending_user_id']);
                unset($_SESSION['pending_email']);
                unset($_SESSION['pending_name']);

                $success = true;

                // Redirigir a login después de 2 segundos
                header("Refresh: 2; url=login.php?registered=1");
            }
        } catch (Exception $e) {
            $error = 'Error al guardar los datos. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Perfil - Latin Wave Community</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Source+Sans+Pro:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f2e6c9;
            position: relative;
            overflow-x: hidden;
        }

        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            opacity: 0.4;
            object-fit: cover;
        }

        .container {
            background: rgba(15, 15, 15, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 60px rgba(180, 138, 100, 0.1);
            overflow: hidden;
            max-width: 550px;
            width: 90%;
            text-align: center;
            border: 1px solid rgba(180, 138, 100, 0.2);
            position: relative;
            z-index: 1;
        }

        .header {
            background: linear-gradient(135deg, rgba(180, 138, 100, 0.3) 0%, rgba(166, 124, 82, 0.2) 100%);
            padding: 30px 20px;
            border-bottom: 1px solid rgba(180, 138, 100, 0.2);
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(180, 138, 100, 0.2) url('https://i.imgur.com/Om6tGeX.png') center center;
            background-size: contain;
            background-repeat: no-repeat;
            margin: 0 auto 15px;
            display: block;
            border: 2px solid rgba(180, 138, 100, 0.4);
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #f2e6c9;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .form-section {
            padding: 30px;
        }

        .welcome-box {
            background: rgba(180, 138, 100, 0.1);
            border: 1px solid rgba(180, 138, 100, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: left;
        }

        .welcome-box p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .welcome-box strong {
            color: #b48a64;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #b48a64;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid rgba(180, 138, 100, 0.3);
            border-radius: 6px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.95);
            color: #1e1e1e;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #b48a64;
            box-shadow: 0 0 0 3px rgba(180, 138, 100, 0.1);
        }

        .profile-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .profile-option {
            position: relative;
        }

        .profile-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .profile-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(180, 138, 100, 0.3);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .profile-option input:checked + label {
            background: rgba(180, 138, 100, 0.2);
            border-color: #b48a64;
        }

        .profile-option label:hover {
            background: rgba(180, 138, 100, 0.1);
        }

        .profile-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .profile-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #f2e6c9;
            text-transform: uppercase;
        }

        .profile-desc {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 4px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #b48a64 0%, #a67c52 100%);
            color: #0a0a0a;
            padding: 16px 40px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(180, 138, 100, 0.4);
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
            color: #6bff6b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .success-container {
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .loader {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #b48a64;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .profile-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <video class="video-background" autoplay muted loop playsinline>
        <source src="https://i.imgur.com/xmTbhn6.mp4" type="video/mp4">
    </video>

    <div class="container">
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">🎉</div>
                <h1 class="title">¡Registro Completado!</h1>
                <p style="color: rgba(255,255,255,0.8); margin-bottom: 20px;">Tu perfil ha sido configurado exitosamente.</p>
                <div class="loader"></div>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">Redirigiendo al login...</p>
            </div>
        <?php else: ?>
            <div class="header">
                <div class="logo"></div>
                <h1 class="title">Configura tu Perfil</h1>
                <p class="subtitle">Último paso para completar tu registro</p>
            </div>

            <div class="form-section">
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="welcome-box">
                    <p>Bienvenido <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                    <p>Email: <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Elige tu tipo de perfil</label>
                        <div class="profile-options">
                            <div class="profile-option">
                                <input type="radio" name="user_type" id="type_cliente" value="cliente" <?php echo (($_POST['user_type'] ?? '') === 'cliente') ? 'checked' : ''; ?>>
                                <label for="type_cliente">
                                    <span class="profile-icon">🛒</span>
                                    <span class="profile-name">Cliente</span>
                                    <span class="profile-desc">Solo consumo</span>
                                </label>
                            </div>
                            <div class="profile-option">
                                <input type="radio" name="user_type" id="type_afiliado" value="afiliado" <?php echo (($_POST['user_type'] ?? '') === 'afiliado') ? 'checked' : ''; ?>>
                                <label for="type_afiliado">
                                    <span class="profile-icon">👥</span>
                                    <span class="profile-name">Afiliado</span>
                                    <span class="profile-desc">6 niveles</span>
                                </label>
                            </div>
                            <div class="profile-option">
                                <input type="radio" name="user_type" id="type_constructor" value="constructor" <?php echo (($_POST['user_type'] ?? '') === 'constructor') ? 'checked' : ''; ?>>
                                <label for="type_constructor">
                                    <span class="profile-icon">👑</span>
                                    <span class="profile-name">Constructor</span>
                                    <span class="profile-desc">16 niveles + WWB</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required placeholder="Ej: juanperez" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmar Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repite tu contraseña">
                    </div>

                    <button type="submit" class="submit-btn">Completar Registro</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
