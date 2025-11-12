// ==================================================
// PRODUCTS ROUTES - CATÁLOGO
// ==================================================

const express = require('express');
const router = express.Router();
const productController = require('../controllers/productController');
const { optionalAuth } = require('../middleware/auth');

// GET /api/products/catalog - Catálogo completo
router.get('/catalog', optionalAuth, productController.getCatalog);

// GET /api/products/agents - Solo agentes
router.get('/agents', optionalAuth, productController.getAgents);

// GET /api/products/packs - Solo packs
router.get('/packs', optionalAuth, productController.getPacks);

// GET /api/products/vip - Solo VIP agents
router.get('/vip', optionalAuth, productController.getVIPAgents);

// GET /api/products/:product_id - Detalles de un producto
// Query param: type (agent, pack, vip)
router.get('/:product_id', optionalAuth, productController.getProductDetails);

module.exports = router;
