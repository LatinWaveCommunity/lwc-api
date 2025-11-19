// ==================================================
// SERVER.JS - API BACKEND LWC V7.1
// ==================================================
// Entry point principal del API
// Configuración Express + Middleware + Routes
// ==================================================

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

// Importar routes
const authRoutes = require('./routes/auth');
const productRoutes = require('./routes/products');
const cartRoutes = require('./routes/cart');
const checkoutRoutes = require('./routes/checkout');
const commissionRoutes = require('./routes/commissions');
const teamRoutes = require('./routes/team');

// Importar middleware
const errorHandler = require('./middleware/errorHandler');

// ==================================================
// INICIALIZAR EXPRESS
// ==================================================
const app = express();
app.set('trust proxy', 1);
const PORT = process.env.PORT || 3000;

// ==================================================
// SEGURIDAD - HELMET
// ==================================================
// Helmet protege la app estableciendo headers HTTP seguros
app.use(helmet());

// ==================================================
// CORS - PERMITIR FRONTEND
// ==================================================
const allowedOrigins = process.env.ALLOWED_ORIGINS 
  ? process.env.ALLOWED_ORIGINS.split(',') 
  : ['http://localhost:3000'];

app.use(cors({
  origin: function(origin, callback) {
    // Permitir requests sin origin (mobile apps, postman, etc)
    if (!origin) return callback(null, true);
    
    if (allowedOrigins.indexOf(origin) !== -1) {
      callback(null, true);
    } else {
      callback(new Error('CORS no permitido para este origen'));
    }
  },
  credentials: true
}));

// ==================================================
// RATE LIMITING - PREVENIR ABUSO
// ==================================================
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutos
  max: 100, // Máximo 100 requests por IP
  message: 'Demasiadas peticiones desde esta IP, intenta de nuevo más tarde'
});

app.use('/api/', limiter);

// ==================================================
// BODY PARSER
// ==================================================
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// ==================================================
// HEALTH CHECK ENDPOINT
// ==================================================
app.get('/api/health', (req, res) => {
  res.status(200).json({ 
    status: 'OK',
    message: 'LWC API Backend funcionando correctamente',
    timestamp: new Date().toISOString(),
    version: '7.1'
  });
});

// ==================================================
// ROUTES - ENDPOINTS API
// ==================================================
app.use('/api/auth', authRoutes);
app.use('/api/products', productRoutes);
app.use('/api/cart', cartRoutes);
app.use('/api/checkout', checkoutRoutes);
app.use('/api/commissions', commissionRoutes);
app.use('/api/team', teamRoutes);

// ==================================================
// ERROR HANDLING MIDDLEWARE
// ==================================================
app.use(errorHandler);

// ==================================================
// 404 HANDLER
// ==================================================
app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint no encontrado',
    path: req.path
  });
});

// ==================================================
// INICIAR SERVIDOR
// ==================================================
app.listen(PORT, () => {
  console.log('╔══════════════════════════════════════════════════════════╗');
  console.log('║  LWC API BACKEND - INICIADO CORRECTAMENTE ✅              ║');
  console.log('╚══════════════════════════════════════════════════════════╝');
  console.log(`🚀 Servidor corriendo en puerto: ${PORT}`);
  console.log(`🌍 Entorno: ${process.env.NODE_ENV || 'development'}`);
  console.log(`⚡ Health check: http://localhost:${PORT}/api/health`);
  console.log('');
  console.log('📌 Endpoints disponibles:');
  console.log('   - POST /api/auth/register');
  console.log('   - POST /api/auth/login');
  console.log('   - GET  /api/products/catalog');
  console.log('   - POST /api/cart/add');
  console.log('   - POST /api/checkout/crypto');
  console.log('   - GET  /api/commissions/:user_id');
  console.log('   - GET  /api/team/structure/:user_id');
  console.log('');
  console.log('💖 CODY V7.1 - Hopkins Precision Guaranteed');
  console.log('══════════════════════════════════════════════════════════');
});

// ==================================================
// MANEJO DE ERRORES NO CAPTURADOS
// ==================================================
process.on('unhandledRejection', (err) => {
  console.error('❌ ERROR NO MANEJADO:', err);
  // En producción, podrías querer cerrar el servidor
  // process.exit(1);
});

process.on('uncaughtException', (err) => {
  console.error('❌ EXCEPCIÓN NO CAPTURADA:', err);
  // En producción, cerrar el servidor
  // process.exit(1);
});

module.exports = app;
