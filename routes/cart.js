// ==================================================
// CART ROUTES - CARRITO DE COMPRAS
// ==================================================

const express = require('express');
const router = express.Router();
const cartController = require('../controllers/cartController');
const { authenticateToken, requireSelfOrAdmin } = require('../middleware/auth');
const { validateAddToCart } = require('../middleware/validation');

// POST /api/cart/add - Agregar producto al carrito
router.post('/add', authenticateToken, validateAddToCart, cartController.addToCart);

// GET /api/cart/:user_id - Obtener carrito de un usuario
router.get('/:user_id', authenticateToken, requireSelfOrAdmin, cartController.getCart);

// PUT /api/cart/:cart_id - Actualizar cantidad
router.put('/:cart_id', authenticateToken, cartController.updateCartItem);

// DELETE /api/cart/:cart_id - Eliminar item
router.delete('/:cart_id', authenticateToken, cartController.removeFromCart);

// DELETE /api/cart/clear/:user_id - Vaciar carrito completo
router.delete('/clear/:user_id', authenticateToken, requireSelfOrAdmin, cartController.clearCart);

module.exports = router;
