// ==================================================
// COMMISSIONCONTROLLER.JS - COMISIONES Y GANANCIAS
// ==================================================
// Consulta de comisiones, historial, y reportes
// ==================================================

const { executeQuery } = require('../config/database');

// ==================================================
// OBTENER RESUMEN DE COMISIONES
// ==================================================
async function getCommissionSummary(req, res) {
  try {
    const { user_id } = req.params;
    
    // Total ganado
    const totalEarned = await executeQuery(`
      SELECT COALESCE(SUM(amount_usd), 0) as total
      FROM lwc_commissions
      WHERE user_id = ? AND status = 'approved'
    `, [user_id]);
    
    // Pendiente de pago
    const pending = await executeQuery(`
      SELECT COALESCE(SUM(amount_usd), 0) as total
      FROM lwc_commissions
      WHERE user_id = ? AND status = 'approved' AND payout_status = 'pending'
    `, [user_id]);
    
    // Ya pagado
    const paid = await executeQuery(`
      SELECT COALESCE(SUM(amount_usd), 0) as total
      FROM lwc_commissions
      WHERE user_id = ? AND status = 'approved' AND payout_status = 'paid'
    `, [user_id]);
    
    // Por tipo de comisión
    const byType = await executeQuery(`
      SELECT 
        commission_type,
        COUNT(*) as count,
        COALESCE(SUM(amount_usd), 0) as total
      FROM lwc_commissions
      WHERE user_id = ? AND status = 'approved'
      GROUP BY commission_type
    `, [user_id]);
    
    res.status(200).json({
      success: true,
      data: {
        total_earned: parseFloat(totalEarned[0].total).toFixed(2),
        pending_payout: parseFloat(pending[0].total).toFixed(2),
        paid_out: parseFloat(paid[0].total).toFixed(2),
        by_type: byType.map(row => ({
          type: row.commission_type,
          count: row.count,
          total: parseFloat(row.total).toFixed(2)
        }))
      }
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo resumen:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener resumen de comisiones'
    });
  }
}

// ==================================================
// HISTORIAL DE COMISIONES
// ==================================================
async function getCommissionHistory(req, res) {
  try {
    const { user_id } = req.params;
    const { page = 1, limit = 20 } = req.query;
    
    const offset = (page - 1) * limit;
    
    const history = await executeQuery(`
      SELECT 
        c.commission_id,
        c.commission_type,
        c.amount_usd,
        c.calculation_date,
        c.status,
        c.payout_status,
        t.transaction_id,
        t.total_amount_usd as sale_amount
      FROM lwc_commissions c
      LEFT JOIN lwc_transactions t ON c.source_transaction_id = t.transaction_id
      WHERE c.user_id = ?
      ORDER BY c.calculation_date DESC
      LIMIT ? OFFSET ?
    `, [user_id, parseInt(limit), offset]);
    
    // Contar total de registros
    const total = await executeQuery(
      'SELECT COUNT(*) as count FROM lwc_commissions WHERE user_id = ?',
      [user_id]
    );
    
    res.status(200).json({
      success: true,
      data: history.map(row => ({
        ...row,
        amount_usd: parseFloat(row.amount_usd).toFixed(2),
        sale_amount: row.sale_amount ? parseFloat(row.sale_amount).toFixed(2) : null
      })),
      meta: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: total[0].count
      }
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo historial:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener historial'
    });
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  getCommissionSummary,
  getCommissionHistory
};
