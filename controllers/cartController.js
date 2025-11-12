// ==================================================
// CARTCONTROLLER.JS - CARRITO DE COMPRAS
// ==================================================
// Maneja agregar, ver, y eliminar items del carrito
// ==================================================

const { executeQuery } = require('../config/database');

// ==================================================
// AGREGAR AL CARRITO
// ==================================================
async function addToCart(req, res) {
  try {
    const { user_id, product_id, product_type, quantity = 1 } = req.body;
    
    // Verificar que el producto existe y obtener precio
    let table, idColumn, product;
    
    switch(product_type) {
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
      default:
        return res.status(400).json({
          success: false,
          message: 'Tipo de producto inválido'
        });
    }
    
    const products = await executeQuery(
      `SELECT * FROM ${table} WHERE ${idColumn} = ? AND is_active = 1`,
      [product_id]
    );
    
    if (products.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Producto no encontrado o no disponible'
      });
    }
    
    product = products[0];
    const price_usd = product.price_usd;
    
    // Verificar si ya existe en el carrito
    const existing = await executeQuery(
      'SELECT cart_id, quantity FROM lwc_cart WHERE user_id = ? AND product_id = ? AND product_type = ?',
      [user_id, product_id, product_type]
    );
    
    if (existing.length > 0) {
      // Actualizar cantidad
      const newQuantity = existing[0].quantity + quantity;
      await executeQuery(
        'UPDATE lwc_cart SET quantity = ?, updated_at = NOW() WHERE cart_id = ?',
        [newQuantity, existing[0].cart_id]
      );
      
      return res.status(200).json({
        success: true,
        message: 'Cantidad actualizada en el carrito',
        data: {
          cart_id: existing[0].cart_id,
          quantity: newQuantity
        }
      });
    }
    
    // Agregar nuevo item al carrito
    const result = await executeQuery(`
      INSERT INTO lwc_cart 
      (user_id, product_id, product_type, quantity, price_usd_snapshot, added_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    `, [user_id, product_id, product_type, quantity, price_usd]);
    
    res.status(201).json({
      success: true,
      message: 'Producto agregado al carrito',
      data: {
        cart_id: result.insertId,
        product_id,
        product_type,
        quantity,
        price_usd
      }
    });
    
  } catch (error) {
    console.error('❌ Error agregando al carrito:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al agregar producto al carrito'
    });
  }
}

// ==================================================
// OBTENER CARRITO DE UN USUARIO
// ==================================================
async function getCart(req, res) {
  try {
    const { user_id } = req.params;
    
    // Obtener items del carrito con detalles de productos
    const cartItems = await executeQuery(`
      SELECT 
        c.cart_id,
        c.product_id,
        c.product_type,
        c.quantity,
        c.price_usd_snapshot,
        c.added_at,
        CASE 
          WHEN c.product_type = 'agent' THEN a.agent_name
          WHEN c.product_type = 'pack' THEN p.pack_name
          WHEN c.product_type = 'vip' THEN v.agent_name
        END as product_name,
        CASE 
          WHEN c.product_type = 'agent' THEN a.description
          WHEN c.product_type = 'pack' THEN p.description
          WHEN c.product_type = 'vip' THEN v.description
        END as product_description
      FROM lwc_cart c
      LEFT JOIN lwc_products_agents a ON c.product_type = 'agent' AND c.product_id = a.agent_id
      LEFT JOIN lwc_products_packs p ON c.product_type = 'pack' AND c.product_id = p.pack_id
      LEFT JOIN lwc_products_vip_agents v ON c.product_type = 'vip' AND c.product_id = v.vip_agent_id
      WHERE c.user_id = ?
      ORDER BY c.added_at DESC
    `, [user_id]);
    
    // Calcular totales
    const subtotal = cartItems.reduce((sum, item) => {
      return sum + (item.price_usd_snapshot * item.quantity);
    }, 0);
    
    res.status(200).json({
      success: true,
      data: {
        items: cartItems,
        summary: {
          total_items: cartItems.length,
          subtotal_usd: subtotal.toFixed(2),
          total_usd: subtotal.toFixed(2) // Por ahora sin impuestos
        }
      }
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo carrito:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener carrito'
    });
  }
}

// ==================================================
// ACTUALIZAR CANTIDAD EN CARRITO
// ==================================================
async function updateCartItem(req, res) {
  try {
    const { cart_id } = req.params;
    const { quantity } = req.body;
    
    if (quantity < 1) {
      return res.status(400).json({
        success: false,
        message: 'La cantidad debe ser al menos 1'
      });
    }
    
    // Verificar que el item existe
    const items = await executeQuery(
      'SELECT cart_id FROM lwc_cart WHERE cart_id = ?',
      [cart_id]
    );
    
    if (items.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Item no encontrado en el carrito'
      });
    }
    
    // Actualizar cantidad
    await executeQuery(
      'UPDATE lwc_cart SET quantity = ?, updated_at = NOW() WHERE cart_id = ?',
      [quantity, cart_id]
    );
    
    res.status(200).json({
      success: true,
      message: 'Cantidad actualizada',
      data: {
        cart_id,
        quantity
      }
    });
    
  } catch (error) {
    console.error('❌ Error actualizando item:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al actualizar item'
    });
  }
}

// ==================================================
// ELIMINAR ITEM DEL CARRITO
// ==================================================
async function removeFromCart(req, res) {
  try {
    const { cart_id } = req.params;
    
    // Verificar que el item existe
    const items = await executeQuery(
      'SELECT cart_id FROM lwc_cart WHERE cart_id = ?',
      [cart_id]
    );
    
    if (items.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Item no encontrado en el carrito'
      });
    }
    
    // Eliminar item
    await executeQuery(
      'DELETE FROM lwc_cart WHERE cart_id = ?',
      [cart_id]
    );
    
    res.status(200).json({
      success: true,
      message: 'Producto eliminado del carrito'
    });
    
  } catch (error) {
    console.error('❌ Error eliminando item:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al eliminar item'
    });
  }
}

// ==================================================
// VACIAR CARRITO COMPLETO
// ==================================================
async function clearCart(req, res) {
  try {
    const { user_id } = req.params;
    
    await executeQuery(
      'DELETE FROM lwc_cart WHERE user_id = ?',
      [user_id]
    );
    
    res.status(200).json({
      success: true,
      message: 'Carrito vaciado exitosamente'
    });
    
  } catch (error) {
    console.error('❌ Error vaciando carrito:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al vaciar carrito'
    });
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  addToCart,
  getCart,
  updateCartItem,
  removeFromCart,
  clearCart
};
