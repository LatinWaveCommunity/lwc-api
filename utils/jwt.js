// ==================================================
// JWT.JS - MANEJO DE TOKENS
// ==================================================
// Generación y verificación de JSON Web Tokens
// Para autenticación segura sin sesiones
// ==================================================

const jwt = require('jsonwebtoken');

const JWT_SECRET = process.env.JWT_SECRET;
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '24h';

// ==================================================
// GENERAR TOKEN
// ==================================================
function generateToken(payload) {
  try {
    // Crear token con payload + secret + expiración
    const token = jwt.sign(
      payload,
      JWT_SECRET,
      { 
        expiresIn: JWT_EXPIRES_IN,
        algorithm: 'HS256'
      }
    );
    
    return token;
  } catch (error) {
    console.error('❌ Error generando token:', error.message);
    throw new Error('No se pudo generar token de autenticación');
  }
}

// ==================================================
// VERIFICAR TOKEN
// ==================================================
function verifyToken(token) {
  try {
    // Verificar y decodificar token
    const decoded = jwt.verify(token, JWT_SECRET);
    return decoded;
  } catch (error) {
    if (error.name === 'TokenExpiredError') {
      throw new Error('Token expirado');
    } else if (error.name === 'JsonWebTokenError') {
      throw new Error('Token inválido');
    } else {
      throw new Error('Error verificando token');
    }
  }
}

// ==================================================
// GENERAR REFRESH TOKEN
// ==================================================
function generateRefreshToken(payload) {
  try {
    // Refresh token con expiración más larga
    const token = jwt.sign(
      payload,
      JWT_SECRET,
      { 
        expiresIn: '7d', // 7 días
        algorithm: 'HS256'
      }
    );
    
    return token;
  } catch (error) {
    console.error('❌ Error generando refresh token:', error.message);
    throw new Error('No se pudo generar refresh token');
  }
}

// ==================================================
// DECODIFICAR TOKEN (sin verificar)
// ==================================================
function decodeToken(token) {
  try {
    // Decodificar sin verificar (útil para debugging)
    const decoded = jwt.decode(token);
    return decoded;
  } catch (error) {
    console.error('❌ Error decodificando token:', error.message);
    return null;
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  generateToken,
  verifyToken,
  generateRefreshToken,
  decodeToken
};
