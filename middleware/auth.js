// ==================================================
// AUTH.JS - MIDDLEWARE DE AUTENTICACIÓN
// ==================================================
// Verifica JWT token en cada request protegido
// Extrae user_id y lo adjunta a req.user
// ==================================================

const { verifyToken } = require('../utils/jwt');

// ==================================================
// VERIFICAR TOKEN
// ==================================================
function authenticateToken(req, res, next) {
  try {
    // Obtener token del header Authorization
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1]; // "Bearer TOKEN"
    
    // Si no hay token, denegar acceso
    if (!token) {
      return res.status(401).json({
        success: false,
        message: 'Token de autenticación requerido'
      });
    }
    
    // Verificar y decodificar token
    const decoded = verifyToken(token);
    
    // Adjuntar información del usuario al request
    req.user = {
      user_id: decoded.user_id,
      email: decoded.email,
      user_type: decoded.user_type,
      lwc_id: decoded.lwc_id
    };
    
    // Continuar al siguiente middleware/ruta
    next();
    
  } catch (error) {
    // Token inválido o expirado
    return res.status(403).json({
      success: false,
      message: error.message || 'Token inválido o expirado'
    });
  }
}

// ==================================================
// VERIFICAR ROL DE USUARIO
// ==================================================
function requireRole(...allowedRoles) {
  return (req, res, next) => {
    // Este middleware debe usarse DESPUÉS de authenticateToken
    if (!req.user) {
      return res.status(401).json({
        success: false,
        message: 'Usuario no autenticado'
      });
    }
    
    // Verificar si el usuario tiene uno de los roles permitidos
    if (!allowedRoles.includes(req.user.user_type)) {
      return res.status(403).json({
        success: false,
        message: 'No tienes permisos para acceder a este recurso',
        required_role: allowedRoles,
        your_role: req.user.user_type
      });
    }
    
    next();
  };
}

// ==================================================
// VERIFICAR QUE EL USUARIO ACCEDE A SU PROPIA INFO
// ==================================================
function requireSelfOrAdmin(req, res, next) {
  // Este middleware debe usarse DESPUÉS de authenticateToken
  if (!req.user) {
    return res.status(401).json({
      success: false,
      message: 'Usuario no autenticado'
    });
  }
  
  // Obtener user_id del request (puede venir de params, body, o query)
  const targetUserId = req.params.user_id || req.body.user_id || req.query.user_id;
  
  // Permitir si es admin o si está accediendo a su propia información
  if (req.user.user_type === 'admin' || req.user.user_id == targetUserId) {
    next();
  } else {
    return res.status(403).json({
      success: false,
      message: 'Solo puedes acceder a tu propia información'
    });
  }
}

// ==================================================
// MIDDLEWARE OPCIONAL (no requiere token)
// ==================================================
function optionalAuth(req, res, next) {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (token) {
      // Si hay token, verificar e incluir info
      const decoded = verifyToken(token);
      req.user = {
        user_id: decoded.user_id,
        email: decoded.email,
        user_type: decoded.user_type,
        lwc_id: decoded.lwc_id
      };
    }
    
    // Continuar con o sin usuario autenticado
    next();
  } catch (error) {
    // Si el token es inválido, simplemente continuar sin usuario
    next();
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  authenticateToken,
  requireRole,
  requireSelfOrAdmin,
  optionalAuth
};
