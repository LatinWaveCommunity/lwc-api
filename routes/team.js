// ==================================================
// TEAM ROUTES - ESTRUCTURA DE EQUIPO
// ==================================================

const express = require('express');
const router = express.Router();
const teamController = require('../controllers/teamController');
const { authenticateToken, requireSelfOrAdmin } = require('../middleware/auth');
const { validateUserId } = require('../middleware/validation');

// GET /api/team/structure/:user_id - Estructura de equipo
router.get('/structure/:user_id', authenticateToken, requireSelfOrAdmin, validateUserId, teamController.getTeamStructure);

// GET /api/team/stats/:user_id - Estadísticas del equipo
router.get('/stats/:user_id', authenticateToken, requireSelfOrAdmin, validateUserId, teamController.getTeamStats);

module.exports = router;
