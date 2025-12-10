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

        // Extraer todos los campos
        $fullName = trim($data['fullName'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $photo = $data['photo'] ?? null; // Base64
        $paymentMethod = trim($data['paymentMethod'] ?? '');
        $paymentInfo = trim($data['paymentInfo'] ?? '');
        $currency = trim($data['currency'] ?? '');
        $digitalAssets = $data['digitalAssets'] ?? [];
        $twoFactorEnabled = $data['twoFactorEnabled'] ?? false;
        $newPassword = $data['newPassword'] ?? null;

        // Log para debug de foto
        $photo_log = date('Y-m-d H:i:s') . " - user_id: $user_id - photo received: " . ($photo ? 'YES (' . strlen($photo) . ' bytes)' : 'NO') . "\n";
        file_put_contents('photo_debug.log', $photo_log, FILE_APPEND);

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

        // Guardar foto si viene en base64
        $photoPath = null;
        if ($photo && strpos($photo, 'data:image') === 0) {
            // Crear directorio de fotos si no existe
            $uploadDir = __DIR__ . '/uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Extraer extensión y datos
            preg_match('/data:image\/(\w+);base64,/', $photo, $matches);
            $extension = $matches[1] ?? 'png';
            $photoData = preg_replace('/data:image\/\w+;base64,/', '', $photo);
            $photoData = base64_decode($photoData);

            // Guardar archivo
            $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $photoPath = 'uploads/photos/' . $filename;
            $saved = file_put_contents($uploadDir . $filename, $photoData);

            // Log resultado
            $save_log = date('Y-m-d H:i:s') . " - user_id: $user_id - photo saved: " . ($saved ? 'YES (' . $saved . ' bytes) -> ' . $photoPath : 'FAILED') . "\n";
            file_put_contents('photo_debug.log', $save_log, FILE_APPEND);
        }

        // Construir query dinámico
        $updateFields = [
            'full_name = ?',
            'username = ?',
            'email = ?',
            'phone = ?',
            'payment_method = ?',
            'payment_info = ?',
            'preferred_currency = ?',
            'digital_assets = ?',
            'two_factor_enabled = ?'
        ];

        $params = [
            $fullName,
            $username,
            $email,
            $phone,
            $paymentMethod,
            $paymentInfo,
            $currency,
            json_encode($digitalAssets),
            $twoFactorEnabled ? 1 : 0
        ];

        // Agregar foto si se subió
        if ($photoPath) {
            $updateFields[] = 'profile_photo = ?';
            $params[] = $photoPath;
        }

        // Agregar contraseña si se cambió
        if (!empty($newPassword) && strlen($newPassword) >= 6) {
            $updateFields[] = 'password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Agregar user_id al final
        $params[] = $user_id;

        // Ejecutar actualización
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Actualizar sesión
        $_SESSION['user_name'] = $fullName;
        $_SESSION['username'] = $username;
        $_SESSION['user_email'] = $email;
        if ($photoPath) {
            $_SESSION['user_photo'] = $photoPath;
        }

        // Log
        $log = date('Y-m-d H:i:s') . " - PROFILE UPDATED - user_id: $user_id, name: $fullName, fields: " . count($updateFields) . "\n";
        file_put_contents('profile_updates.log', $log, FILE_APPEND);

        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'photo' => $photoPath
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
