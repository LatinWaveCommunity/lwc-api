// ==================================================
// DATABASE.JS - CONFIGURACIÓN MYSQL
// ==================================================
// Connection pooling para performance óptimo
// Reutiliza conexiones en lugar de crear nuevas
// ==================================================

const mysql = require('mysql2/promise');

// ==================================================
// CREAR CONNECTION POOL
// ==================================================
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  port: process.env.DB_PORT || 3306,
  
  // CONFIGURACIÓN POOL
  waitForConnections: true,    // Esperar si no hay conexiones disponibles
  connectionLimit: 10,          // Máximo 10 conexiones simultáneas
  queueLimit: 0,                // Sin límite de cola
  
  // KEEP-ALIVE
  enableKeepAlive: true,        // Mantener conexiones vivas
  keepAliveInitialDelay: 0,     // Sin delay inicial
  
  // TIMEZONE
  timezone: '+00:00',           // UTC
  
  // CHARSET
  charset: 'utf8mb4'            // Soporte completo Unicode
});

// ==================================================
// VERIFICAR CONEXIÓN AL INICIO
// ==================================================
pool.getConnection()
  .then(connection => {
    console.log('✅ Conexión MySQL establecida correctamente');
    console.log(`📊 Base de datos: ${process.env.DB_NAME}`);
    connection.release();
  })
  .catch(err => {
    console.error('❌ Error conectando a MySQL:', err.message);
    console.error('🔍 Verifica tu archivo .env y credenciales de base de datos');
  });

// ==================================================
// HELPER FUNCTION - EJECUTAR QUERY
// ==================================================
async function executeQuery(sql, params = []) {
  let connection;
  try {
    connection = await pool.getConnection();
    const [rows] = await connection.execute(sql, params);
    return rows;
  } catch (error) {
    console.error('❌ Error ejecutando query:', error.message);
    throw error;
  } finally {
    if (connection) connection.release();
  }
}

// ==================================================
// HELPER FUNCTION - TRANSACCIÓN
// ==================================================
async function executeTransaction(queries) {
  let connection;
  try {
    connection = await pool.getConnection();
    await connection.beginTransaction();
    
    const results = [];
    for (const { sql, params } of queries) {
      const [rows] = await connection.execute(sql, params);
      results.push(rows);
    }
    
    await connection.commit();
    return results;
  } catch (error) {
    if (connection) await connection.rollback();
    console.error('❌ Error en transacción:', error.message);
    throw error;
  } finally {
    if (connection) connection.release();
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  pool,
  executeQuery,
  executeTransaction
};
