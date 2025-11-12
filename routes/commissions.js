// ==================================================
// COMMISSIONS ROUTES - COMISIONES Y GANANCIAS
// ==================================================

const express = require('express');
const router = express.Router();
const commissionController = require('../controllers/commissionController');
const { authenticateToken, requireSelfOrAdmin } = require('../middleware/auth');
const { validateUserId, validatePagination } = require('../middleware/validation');

// GET /api/commissions/:user_id - Resumen de comisiones
router.get('/:user_id', authenticateToken, requireSelfOrAdmin, validateUserId, commissionController.getCommissionSummary);

// GET /api/commissions/:user_id/history - Historial de comisiones
router.get('/:user_id/history', authenticateToken, requireSelfOrAdmin, validateUserId, validatePagination, commissionController.getCommissionHistory);

module.exports = router;
