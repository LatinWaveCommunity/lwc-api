const { Pool } = require('pg');

const pool = new Pool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT || 5432,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  ssl: {
    rejectUnauthorized: false
  },
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});

pool.on('connect', () => {
  console.log('✅ Conexión PostgreSQL establecida correctamente');
  console.log(`📊 Base de datos: ${process.env.DB_NAME}`);
});

pool.on('error', (err) => {
  console.error('❌ Error inesperado en PostgreSQL pool:', err);
});

const executeQuery = async (query, params = []) => {
  const client = await pool.connect();
  try {
    const result = await client.query(query, params);
    return result.rows;
  } catch (error) {
    console.error('❌ Error ejecutando query:', error);
    throw error;
  } finally {
    client.release();
  }
};

module.exports = { pool, executeQuery };
