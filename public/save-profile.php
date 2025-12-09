<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Conectar a MySQL
require_once __DIR__ . '/api/v7/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];

        // Recibir datos del formulario
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            throw new Exception('Datos inválidos');
        }

        $fullName = trim($data['fullName'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $paymentMethod = trim($data['paymentMethod'] ?? '');
        $paymentInfo = trim($data['paymentInfo'] ?? '');
        $currency = trim($data['currency'] ?? '');
        $digitalAssets = $data['digitalAssets'] ?? [];

        // Validaciones básicas
        if (empty($fullName)) {
            throw new Exception('El nombre es obligatorio');
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }

        // Verificar que el username no esté en uso por otro usuario
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('Este username ya está en uso');
            }
        }

        // Actualizar datos del usuario en MySQL
        $stmt = $pdo->prepare("
            UPDATE users SET
                full_name = ?,
                username = ?,
                email = ?,
                payment_method = ?,
                payment_info = ?,
                preferred_currency = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $fullName,
            $username,
            $email,
            $paymentMethod,
            $paymentInfo,
            $currency,
            $user_id
        ]);

        // Guardar activos digitales en tabla separada o JSON
        $digitalAssetsJson = json_encode($digitalAssets);
        $stmt = $pdo->prepare("UPDATE users SET digital_assets = ? WHERE user_id = ?");
        $stmt->execute([$digitalAssetsJson, $user_id]);

        // Actualizar sesión
        $_SESSION['user_name'] = $fullName;
        $_SESSION['username'] = $username;
        $_SESSION['user_email'] = $email;

        // Log
        $log = date('Y-m-d H:i:s') . " - PROFILE UPDATED - user_id: $user_id, name: $fullName\n";
        file_put_contents('profile_updates.log', $log, FILE_APPEND);

        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
