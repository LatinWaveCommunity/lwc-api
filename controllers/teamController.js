// ==================================================
// TEAMCONTROLLER.JS - ESTRUCTURA DE EQUIPO
// ==================================================
// Consulta de equipo, downline, y estadísticas
// ==================================================

const { executeQuery } = require('../config/database');

// ==================================================
// OBTENER ESTRUCTURA DE EQUIPO
// ==================================================
async function getTeamStructure(req, res) {
  try {
    const { user_id } = req.params;
    
    // Obtener información del usuario
    const user = await executeQuery(
      'SELECT user_id, lwc_id, email, full_name, sponsor_id FROM lwc_users WHERE user_id = ?',
      [user_id]
    );
    
    if (user.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Usuario no encontrado'
      });
    }
    
    // Obtener sponsor
    let sponsor = null;
    if (user[0].sponsor_id) {
      const sponsorData = await executeQuery(
        'SELECT user_id, lwc_id, email, full_name FROM lwc_users WHERE user_id = ?',
        [user[0].sponsor_id]
      );
      sponsor = sponsorData[0] || null;
    }
    
    // Obtener referidos directos
    const directs = await executeQuery(`
      SELECT 
        user_id,
        lwc_id,
        email,
        full_name,
        user_type,
        registration_date
      FROM lwc_users
      WHERE sponsor_id = ?
      ORDER BY registration_date DESC
    `, [user_id]);
    
    // Obtener downline completo (todos los niveles)
    const downline = await getFullDownline(user_id);
    
    res.status(200).json({
      success: true,
      data: {
        user: {
          user_id: user[0].user_id,
          lwc_id: user[0].lwc_id,
          email: user[0].email,
          full_name: user[0].full_name
        },
        sponsor: sponsor,
        direct_referrals: directs,
        total_team: downline.length,
        team_by_level: calculateTeamByLevel(downline)
      }
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo estructura:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener estructura de equipo'
    });
  }
}

// ==================================================
// OBTENER ESTADÍSTICAS DEL EQUIPO
// ==================================================
async function getTeamStats(req, res) {
  try {
    const { user_id } = req.params;
    
    // Total de equipo
    const downline = await getFullDownline(user_id);
    
    // Ventas del equipo (último mes)
    const teamSales = await executeQuery(`
      SELECT COALESCE(SUM(t.total_amount_usd), 0) as total
      FROM lwc_transactions t
      INNER JOIN lwc_users u ON t.user_id = u.user_id
      WHERE u.sponsor_id = ? 
      AND t.payment_status = 'completed'
      AND t.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    `, [user_id]);
    
    // Usuarios activos (compraron en último mes)
    const activeUsers = await executeQuery(`
      SELECT COUNT(DISTINCT u.user_id) as count
      FROM lwc_users u
      INNER JOIN lwc_transactions t ON u.user_id = t.user_id
      WHERE u.sponsor_id = ?
      AND t.payment_status = 'completed'
      AND t.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    `, [user_id]);
    
    res.status(200).json({
      success: true,
      data: {
        total_team: downline.length,
        team_volume_30d: parseFloat(teamSales[0].total).toFixed(2),
        active_users_30d: activeUsers[0].count,
        team_by_level: calculateTeamByLevel(downline)
      }
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo stats:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener estadísticas'
    });
  }
}

// ==================================================
// HELPER: OBTENER DOWNLINE COMPLETO (RECURSIVO)
// ==================================================
async function getFullDownline(user_id, level = 1, visited = new Set()) {
  // Prevenir ciclos infinitos
  if (visited.has(user_id)) return [];
  visited.add(user_id);
  
  const directs = await executeQuery(
    'SELECT user_id, lwc_id, email, full_name FROM lwc_users WHERE sponsor_id = ?',
    [user_id]
  );
  
  let allDownline = directs.map(user => ({ ...user, level }));
  
  // Recursivamente obtener downline de cada directo
  for (const direct of directs) {
    const subDownline = await getFullDownline(direct.user_id, level + 1, visited);
    allDownline = allDownline.concat(subDownline);
  }
  
  return allDownline;
}

// ==================================================
// HELPER: CALCULAR EQUIPO POR NIVEL
// ==================================================
function calculateTeamByLevel(downline) {
  const byLevel = {};
  
  downline.forEach(member => {
    if (!byLevel[member.level]) {
      byLevel[member.level] = 0;
    }
    byLevel[member.level]++;
  });
  
  return byLevel;
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  getTeamStructure,
  getTeamStats
};
