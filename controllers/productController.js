// ==================================================
// PRODUCTCONTROLLER.JS - CATÁLOGO DE PRODUCTOS
// ==================================================
// Maneja obtención de agentes, packs, y VIP agents
// ==================================================

const { executeQuery } = require('../config/database');

// ==================================================
// OBTENER CATÁLOGO COMPLETO
// ==================================================
async function getCatalog(req, res) {
  try {
    // Obtener todos los agentes individuales
    const agents = await executeQuery(`
      SELECT 
        agent_id,
        agent_name,
        description,
        price_usd,
        features,
        is_active,
        created_at
      FROM lwc_products_agents
      WHERE is_active = 1
      ORDER BY price_usd ASC
    `);
    
    // Obtener todos los packs
    const packs = await executeQuery(`
      SELECT 
        pack_id,
        pack_name,
        description,
        price_usd,
        agents_included,
        features,
        is_active,
        created_at
      FROM lwc_products_packs
      WHERE is_active = 1
      ORDER BY price_usd ASC
    `);
    
    // Obtener VIP agents
    const vipAgents = await executeQuery(`
      SELECT 
        vip_agent_id,
        agent_name,
        description,
        price_usd,
        features,
        is_active,
        created_at
      FROM lwc_products_vip_agents
      WHERE is_active = 1
      ORDER BY price_usd ASC
    `);
    
    // Responder con catálogo completo
    res.status(200).json({
      success: true,
      data: {
        agents: agents.map(agent => ({
          ...agent,
          features: JSON.parse(agent.features || '[]')
        })),
        packs: packs.map(pack => ({
          ...pack,
          agents_included: JSON.parse(pack.agents_included || '[]'),
          features: JSON.parse(pack.features || '[]')
        })),
        vip_agents: vipAgents.map(vip => ({
          ...vip,
          features: JSON.parse(vip.features || '[]')
        }))
      },
      meta: {
        total_agents: agents.length,
        total_packs: packs.length,
        total_vip: vipAgents.length
      }
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo catálogo:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener catálogo',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
}

// ==================================================
// OBTENER AGENTES INDIVIDUALES
// ==================================================
async function getAgents(req, res) {
  try {
    const agents = await executeQuery(`
      SELECT 
        agent_id,
        agent_name,
        description,
        price_usd,
        features,
        is_active,
        created_at
      FROM lwc_products_agents
      WHERE is_active = 1
      ORDER BY price_usd ASC
    `);
    
    res.status(200).json({
      success: true,
      data: agents.map(agent => ({
        ...agent,
        features: JSON.parse(agent.features || '[]')
      }))
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo agentes:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener agentes'
    });
  }
}

// ==================================================
// OBTENER PACKS
// ==================================================
async function getPacks(req, res) {
  try {
    const packs = await executeQuery(`
      SELECT 
        pack_id,
        pack_name,
        description,
        price_usd,
        agents_included,
        features,
        is_active,
        created_at
      FROM lwc_products_packs
      WHERE is_active = 1
      ORDER BY price_usd ASC
    `);
    
    res.status(200).json({
      success: true,
      data: packs.map(pack => ({
        ...pack,
        agents_included: JSON.parse(pack.agents_included || '[]'),
        features: JSON.parse(pack.features || '[]')
      }))
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo packs:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener packs'
    });
  }
}

// ==================================================
// OBTENER VIP AGENTS
// ==================================================
async function getVIPAgents(req, res) {
  try {
    const vipAgents = await executeQuery(`
      SELECT 
        vip_agent_id,
        agent_name,
        description,
        price_usd,
        features,
        is_active,
        created_at
      FROM lwc_products_vip_agents
      WHERE is_active = 1
      ORDER BY price_usd ASC
    `);
    
    res.status(200).json({
      success: true,
      data: vipAgents.map(vip => ({
        ...vip,
        features: JSON.parse(vip.features || '[]')
      }))
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo VIP agents:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener VIP agents'
    });
  }
}

// ==================================================
// OBTENER DETALLES DE UN PRODUCTO
// ==================================================
async function getProductDetails(req, res) {
  try {
    const { product_id } = req.params;
    const { type } = req.query; // 'agent', 'pack', o 'vip'
    
    if (!type || !['agent', 'pack', 'vip'].includes(type)) {
      return res.status(400).json({
        success: false,
        message: 'Tipo de producto requerido (agent, pack, vip)'
      });
    }
    
    let table, idColumn;
    switch(type) {
      case 'agent':
        table = 'lwc_products_agents';
        idColumn = 'agent_id';
        break;
      case 'pack':
        table = 'lwc_products_packs';
        idColumn = 'pack_id';
        break;
      case 'vip':
        table = 'lwc_products_vip_agents';
        idColumn = 'vip_agent_id';
        break;
    }
    
    const products = await executeQuery(
      `SELECT * FROM ${table} WHERE ${idColumn} = ? AND is_active = 1`,
      [product_id]
    );
    
    if (products.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Producto no encontrado'
      });
    }
    
    const product = products[0];
    
    // Parsear JSON fields
    if (product.features) product.features = JSON.parse(product.features);
    if (product.agents_included) product.agents_included = JSON.parse(product.agents_included);
    
    res.status(200).json({
      success: true,
      data: product
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo detalles producto:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener detalles del producto'
    });
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  getCatalog,
  getAgents,
  getPacks,
  getVIPAgents,
  getProductDetails
};
