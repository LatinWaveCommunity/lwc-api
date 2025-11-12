// ==================================================
// COMMISSION.JS - CÁLCULO DE COMISIONES LWC
// ==================================================
// Implementa el plan de compensación completo:
// - Comisiones directas (50% profit)
// - Overrides afiliados (25-50%)
// - Matriz bonus (2.5%)
// - Constructor bonus (5%)
// - World Wide bonus (1-5% pools)
// ==================================================

const { executeQuery, executeTransaction } = require('../config/database');

// ==================================================
// COMISIÓN DIRECTA (50% PROFIT)
// ==================================================
async function calculateDirectCommission(saleData) {
  try {
    const { product_id, product_type, price_usd, buyer_id, sponsor_id } = saleData;
    
    // Obtener costo del producto
    const product = await getProductCost(product_id, product_type);
    const cost = product.cost || (price_usd * 0.5); // Default 50% si no hay costo
    
    // Calcular profit y comisión
    const profit = price_usd - cost;
    const commission = profit * 0.50; // 50% del profit
    
    // Insertar comisión en BD
    await executeQuery(`
      INSERT INTO lwc_commissions 
      (user_id, commission_type, amount_usd, source_transaction_id, calculation_date)
      VALUES (?, 'direct', ?, ?, NOW())
    `, [sponsor_id, commission, saleData.transaction_id]);
    
    return {
      type: 'direct',
      amount: commission,
      recipient: sponsor_id,
      percentage: 50
    };
  } catch (error) {
    console.error('❌ Error calculando comisión directa:', error.message);
    throw error;
  }
}

// ==================================================
// OVERRIDE AFILIADO (25-50%)
// ==================================================
async function calculateOverride(saleData, sponsor_id) {
  try {
    // Obtener tipo de usuario del sponsor
    const sponsor = await executeQuery(
      'SELECT user_type, override_percentage FROM lwc_users WHERE user_id = ?',
      [sponsor_id]
    );
    
    if (!sponsor[0] || sponsor[0].user_type !== 'afiliado') {
      return { type: 'override', amount: 0 };
    }
    
    // Obtener comisión directa de la venta
    const directCommission = await executeQuery(`
      SELECT amount_usd FROM lwc_commissions 
      WHERE source_transaction_id = ? AND commission_type = 'direct'
      ORDER BY commission_id DESC LIMIT 1
    `, [saleData.transaction_id]);
    
    if (!directCommission[0]) return { type: 'override', amount: 0 };
    
    // Calcular override (25% default)
    const overridePercentage = sponsor[0].override_percentage || 25;
    const overrideAmount = directCommission[0].amount_usd * (overridePercentage / 100);
    
    // Insertar override en BD
    await executeQuery(`
      INSERT INTO lwc_commissions 
      (user_id, commission_type, amount_usd, source_transaction_id, calculation_date)
      VALUES (?, 'override', ?, ?, NOW())
    `, [sponsor_id, overrideAmount, saleData.transaction_id]);
    
    return {
      type: 'override',
      amount: overrideAmount,
      recipient: sponsor_id,
      percentage: overridePercentage
    };
  } catch (error) {
    console.error('❌ Error calculando override:', error.message);
    throw error;
  }
}

// ==================================================
// MATRIZ BONUS (2.5% VENTAS TOTALES)
// ==================================================
async function calculateMatrizBonus(month, year) {
  try {
    // Obtener ventas totales del mes
    const totalSales = await executeQuery(`
      SELECT SUM(total_amount_usd) as total 
      FROM lwc_transactions 
      WHERE MONTH(transaction_date) = ? 
      AND YEAR(transaction_date) = ?
      AND payment_status = 'completed'
    `, [month, year]);
    
    const salesAmount = totalSales[0]?.total || 0;
    const matrizPool = salesAmount * 0.025; // 2.5%
    
    // Obtener usuarios activos del mes
    const activeUsers = await executeQuery(`
      SELECT DISTINCT user_id 
      FROM lwc_transactions 
      WHERE MONTH(transaction_date) = ? 
      AND YEAR(transaction_date) = ?
      AND payment_status = 'completed'
    `, [month, year]);
    
    if (activeUsers.length === 0) return { distributed: 0, recipients: 0 };
    
    // Distribuir equitativamente
    const perUser = matrizPool / activeUsers.length;
    
    // Insertar comisiones
    const queries = activeUsers.map(user => ({
      sql: `INSERT INTO lwc_commissions 
            (user_id, commission_type, amount_usd, calculation_date)
            VALUES (?, 'matriz', ?, NOW())`,
      params: [user.user_id, perUser]
    }));
    
    await executeTransaction(queries);
    
    return {
      type: 'matriz',
      total_pool: matrizPool,
      per_user: perUser,
      recipients: activeUsers.length
    };
  } catch (error) {
    console.error('❌ Error calculando matriz bonus:', error.message);
    throw error;
  }
}

// ==================================================
// CONSTRUCTOR BONUS (5%)
// ==================================================
async function calculateConstructorBonus(saleData) {
  try {
    // Solo si el comprador es constructor
    const buyer = await executeQuery(
      'SELECT user_type FROM lwc_users WHERE user_id = ?',
      [saleData.buyer_id]
    );
    
    if (!buyer[0] || buyer[0].user_type !== 'constructor') {
      return { type: 'constructor', amount: 0 };
    }
    
    // 5% del total de la venta
    const bonus = saleData.price_usd * 0.05;
    
    // Insertar bonus
    await executeQuery(`
      INSERT INTO lwc_commissions 
      (user_id, commission_type, amount_usd, source_transaction_id, calculation_date)
      VALUES (?, 'constructor', ?, ?, NOW())
    `, [saleData.buyer_id, bonus, saleData.transaction_id]);
    
    return {
      type: 'constructor',
      amount: bonus,
      recipient: saleData.buyer_id,
      percentage: 5
    };
  } catch (error) {
    console.error('❌ Error calculando constructor bonus:', error.message);
    throw error;
  }
}

// ==================================================
// DISTRIBUIR TODAS LAS COMISIONES
// ==================================================
async function distributeAllCommissions(saleData) {
  try {
    const results = {
      direct: null,
      override: null,
      constructor: null,
      total_distributed: 0
    };
    
    // 1. Comisión directa al sponsor
    if (saleData.sponsor_id) {
      results.direct = await calculateDirectCommission(saleData);
      results.total_distributed += results.direct.amount;
    }
    
    // 2. Override si sponsor es afiliado
    if (saleData.sponsor_id) {
      results.override = await calculateOverride(saleData, saleData.sponsor_id);
      results.total_distributed += results.override.amount;
    }
    
    // 3. Constructor bonus si aplica
    results.constructor = await calculateConstructorBonus(saleData);
    results.total_distributed += results.constructor.amount;
    
    return results;
  } catch (error) {
    console.error('❌ Error distribuyendo comisiones:', error.message);
    throw error;
  }
}

// ==================================================
// HELPER: OBTENER COSTO PRODUCTO
// ==================================================
async function getProductCost(product_id, product_type) {
  try {
    let table;
    switch(product_type) {
      case 'agent':
        table = 'lwc_products_agents';
        break;
      case 'pack':
        table = 'lwc_products_packs';
        break;
      case 'vip':
        table = 'lwc_products_vip_agents';
        break;
      default:
        throw new Error('Tipo de producto inválido');
    }
    
    const product = await executeQuery(
      `SELECT price_usd, cost FROM ${table} WHERE agent_id = ? OR pack_id = ? OR vip_agent_id = ?`,
      [product_id, product_id, product_id]
    );
    
    return product[0] || {};
  } catch (error) {
    console.error('❌ Error obteniendo costo producto:', error.message);
    throw error;
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  calculateDirectCommission,
  calculateOverride,
  calculateMatrizBonus,
  calculateConstructorBonus,
  distributeAllCommissions
};
