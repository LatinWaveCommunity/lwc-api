const { Pool } = require('pg');

const pool = new Pool({
  host: 'aws-0-us-east-1.pooler.supabase.com',
  port: 5432,
  user: 'postgres.pqrrgftyfjirhshnuqzsp',
  password: 'Somos1latinwave',
  database: 'postgres',
  ssl: {
    rejectUnauthorized: false
  },
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 10000,
  statement_timeout: 30000,
  query_timeout: 30000,
});

pool.on('connect', () => {
  console.log('✅ Conexión PostgreSQL establecida correctamente');
  console.log(`📊 Base de datos: postgres`);
});

const executeQuery = async (query, params = [], retries = 3) => {
  for (let attempt = 1; attempt <= retries; attempt++) {
    const client = await pool.connect();
    try {
      const result = await client.query(query, params);
      return result.rows;
    } catch (error) {
      console.error(`❌ Intento ${attempt} falló:`, error.message);
      if (attempt === retries) throw error;
      await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
    } finally {
      client.release();
    }
  }
};

module.exports = { pool, executeQuery };
