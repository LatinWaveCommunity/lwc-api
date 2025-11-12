// ==================================================
// AUTH ROUTES - AUTENTICACIÓN
// ==================================================

const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');
const { validateRegister, validateLogin } = require('../middleware/validation');
const { authenticateToken } = require('../middleware/auth');

// POST /api/auth/register - Registrar nuevo usuario
router.post('/register', validateRegister, authController.register);

// POST /api/auth/login - Iniciar sesión
router.post('/login', validateLogin, authController.login);

// POST /api/auth/refresh - Renovar token
router.post('/refresh', authController.refreshAccessToken);

// GET /api/auth/profile - Obtener perfil (requiere auth)
router.get('/profile', authenticateToken, authController.getProfile);

module.exports = router;
