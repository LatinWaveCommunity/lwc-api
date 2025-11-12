// ==================================================
// CHECKOUTCONTROLLER.JS - PAGOS CRYPTO
// ==================================================
// Integración con NOWPayments para procesar pagos
// Maneja creación de pagos y webhooks
// ==================================================

const { executeQuery, executeTransaction } = require('../config/database');
const { distributeAllCommissions } = require('../utils/commission');

// NOWPayments (descomentar cuando tengas API key)
// const NOWPayments = require('@nowpayments/nowpayments-api-js');
// const npClient = new NOWPayments({
//   apiKey: process.env.NOWPAYMENTS_API_KEY,
//   sandbox: process.env.NOWPAYMENTS_SANDBOX === 'true'
// });

// ==================================================
// CREAR PAGO CRYPTO
// ==================================================
async function createCryptoPayment(req, res) {
  try {
    const { user_id, crypto_type = 'USDT' } = req.body;
    
    // Obtener carrito del usuario
    const cartItems = await executeQuery(`
      SELECT 
        cart_id,
        product_id,
        product_type,
        quantity,
        price_usd_snapshot
      FROM lwc_cart 
      WHERE user_id = ?
    `, [user_id]);
    
    if (cartItems.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'El carrito está vacío'
      });
    }
    
    // Calcular total
    const total_amount = cartItems.reduce((sum, item) => {
      return sum + (item.price_usd_snapshot * item.quantity);
    }, 0);
    
    // Obtener sponsor del usuario
    const users = await executeQuery(
      'SELECT sponsor_id FROM lwc_users WHERE user_id = ?',
      [user_id]
    );
    
    const sponsor_id = users[0]?.sponsor_id || null;
    
    // Generar order ID único
    const order_id = `LWC-${Date.now()}-${user_id}`;
    
    // ===================================================
    // INTEGRACIÓN NOWPAYMENTS (descomentar cuando tengas API key)
    // ===================================================
    /*
    const payment = await npClient.createPayment({
      price_amount: total_amount,
      price_currency: 'USD',
      pay_currency: crypto_type,
      ipn_callback_url: `${process.env.API_BASE_URL}/api/checkout/callback`,
      order_id: order_id,
      order_description: 'LWC Product Purchase'
    });
    */
    
    // Por ahora, simulación de respuesta (REMOVER cuando integres NOWPayments)
    const payment = {
      payment_id: `SIMULATED-${Date.now()}`,
      pay_address: 'TSimulatedAddressForTesting123456789',
      pay_amount: total_amount,
      pay_currency: crypto_type,
      invoice_url: `https://nowpayments.io/payment/?invoice=${order_id}`,
      payment_status: 'waiting'
    };
    
    // Guardar transacción en BD
    const transactionResult = await executeQuery(`
      INSERT INTO lwc_transactions 
      (user_id, sponsor_id, total_amount_usd, payment_method, 
       blockchain_tx_hash, payment_status, transaction_date)
      VALUES (?, ?, ?, 'nowpayments', ?, 'pending', NOW())
    `, [user_id, sponsor_id, total_amount, payment.payment_id]);
    
    const transaction_id = transactionResult.insertId;
    
    // Guardar items de la transacción
    const itemQueries = cartItems.map(item => ({
      sql: `INSERT INTO lwc_transaction_items 
            (transaction_id, product_id, product_type, quantity, price_usd_snapshot)
            VALUES (?, ?, ?, ?, ?)`,
      params: [transaction_id, item.product_id, item.product_type, item.quantity, item.price_usd_snapshot]
    }));
    
    await executeTransaction(itemQueries);
    
    // Vaciar carrito
    await executeQuery('DELETE FROM lwc_cart WHERE user_id = ?', [user_id]);
    
    // Responder con datos de pago
    res.status(201).json({
      success: true,
      message: 'Pago creado exitosamente',
      data: {
        transaction_id,
        order_id,
        payment_url: payment.invoice_url,
        pay_address: payment.pay_address,
        pay_amount: payment.pay_amount,
        pay_currency: payment.pay_currency,
        total_usd: total_amount,
        status: 'waiting',
        note: 'Este es un pago simulado. Integra NOWPayments para pagos reales.'
      }
    });
    
  } catch (error) {
    console.error('❌ Error creando pago crypto:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al crear pago',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
}

// ==================================================
// WEBHOOK CALLBACK DE NOWPAYMENTS (IPN)
// ==================================================
async function handlePaymentCallback(req, res) {
  try {
    const ipn = req.body;
    
    console.log('📥 Webhook recibido de NOWPayments:', ipn);
    
    // IMPORTANTE: Verificar firma IPN para seguridad
    // const isValid = verifyIPNSignature(ipn);
    // if (!isValid) {
    //   return res.status(400).send('Invalid signature');
    // }
    
    // Obtener transacción por order_id
    const transactions = await executeQuery(
      'SELECT * FROM lwc_transactions WHERE blockchain_tx_hash = ?',
      [ipn.payment_id]
    );
    
    if (transactions.length === 0) {
      console.error('❌ Transacción no encontrada:', ipn.payment_id);
      return res.status(404).send('Transaction not found');
    }
    
    const transaction = transactions[0];
    
    // Mapear status de NOWPayments a nuestro sistema
    let payment_status = 'pending';
    if (ipn.payment_status === 'finished' || ipn.payment_status === 'confirmed') {
      payment_status = 'completed';
    } else if (ipn.payment_status === 'failed' || ipn.payment_status === 'expired') {
      payment_status = 'failed';
    }
    
    // Actualizar transacción
    await executeQuery(`
      UPDATE lwc_transactions 
      SET payment_status = ?,
          blockchain_tx_hash = ?,
          updated_at = NOW()
      WHERE transaction_id = ?
    `, [payment_status, ipn.outcome_hash || ipn.payment_id, transaction.transaction_id]);
    
    // Si el pago fue exitoso, calcular y distribuir comisiones
    if (payment_status === 'completed' && transaction.payment_status !== 'completed') {
      console.log('✅ Pago completado, distribuyendo comisiones...');
      
      // Obtener items de la transacción
      const items = await executeQuery(
        'SELECT * FROM lwc_transaction_items WHERE transaction_id = ?',
        [transaction.transaction_id]
      );
      
      // Distribuir comisiones por cada item
      for (const item of items) {
        await distributeAllCommissions({
          transaction_id: transaction.transaction_id,
          product_id: item.product_id,
          product_type: item.product_type,
          price_usd: item.price_usd_snapshot * item.quantity,
          buyer_id: transaction.user_id,
          sponsor_id: transaction.sponsor_id
        });
      }
      
      console.log('💰 Comisiones distribuidas exitosamente');
    }
    
    // Responder OK a NOWPayments
    res.status(200).send('OK');
    
  } catch (error) {
    console.error('❌ Error procesando webhook:', error.message);
    res.status(500).send('Internal server error');
  }
}

// ==================================================
// VERIFICAR STATUS DE PAGO
// ==================================================
async function checkPaymentStatus(req, res) {
  try {
    const { transaction_id } = req.params;
    
    const transactions = await executeQuery(
      'SELECT * FROM lwc_transactions WHERE transaction_id = ?',
      [transaction_id]
    );
    
    if (transactions.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Transacción no encontrada'
      });
    }
    
    const transaction = transactions[0];
    
    res.status(200).json({
      success: true,
      data: {
        transaction_id: transaction.transaction_id,
        status: transaction.payment_status,
        total_usd: transaction.total_amount_usd,
        payment_method: transaction.payment_method,
        transaction_date: transaction.transaction_date
      }
    });
    
  } catch (error) {
    console.error('❌ Error verificando status:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al verificar status de pago'
    });
  }
}

// ==================================================
// HELPER: VERIFICAR FIRMA IPN (para seguridad)
// ==================================================
function verifyIPNSignature(ipn) {
  // IMPLEMENTAR: Verificación de firma usando HMAC
  // Documentación: https://documenter.getpostman.com/view/7907941/S1a32n38
  // Por ahora retorna true (CAMBIAR en producción)
  return true;
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  createCryptoPayment,
  handlePaymentCallback,
  checkPaymentStatus
};
