// ==================================================
// CHECKOUT ROUTES - PAGOS CRYPTO
// ==================================================

const express = require('express');
const router = express.Router();
const checkoutController = require('../controllers/checkoutController');
const { authenticateToken } = require('../middleware/auth');
const { validateCryptoPayment } = require('../middleware/validation');

// POST /api/checkout/crypto - Crear pago crypto
router.post('/crypto', authenticateToken, validateCryptoPayment, checkoutController.createCryptoPayment);

// POST /api/checkout/callback - Webhook de NOWPayments
// IMPORTANTE: Este endpoint NO requiere autenticación (viene de NOWPayments)
router.post('/callback', checkoutController.handlePaymentCallback);

// GET /api/checkout/status/:transaction_id - Verificar status de pago
router.get('/status/:transaction_id', authenticateToken, checkoutController.checkPaymentStatus);

module.exports = router;
