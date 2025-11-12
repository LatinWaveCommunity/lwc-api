// ==================================================
// ERRORHANDLER.JS - MANEJO CENTRALIZADO DE ERRORES
// ==================================================
// Captura todos los errores y responde apropiadamente
// Evita exponer detalles internos en producción
// ==================================================

// ==================================================
// ERROR HANDLER PRINCIPAL
// ==================================================
function errorHandler(err, req, res, next) {
  // Log del error para debugging
  console.error('❌ ERROR CAPTURADO:');
  console.error('Ruta:', req.method, req.path);
  console.error('Mensaje:', err.message);
  console.error('Stack:', err.stack);
  
  // Determinar código de status
  const statusCode = err.statusCode || 500;
  
  // Preparar respuesta de error
  const errorResponse = {
    success: false,
    message: err.message || 'Error interno del servidor',
    ...(process.env.NODE_ENV === 'development' && {
      stack: err.stack,
      details: err
    })
  };
  
  // Tipos específicos de error
  
  // Error de validación
  if (err.name === 'ValidationError') {
    errorResponse.message = 'Error de validación';
    errorResponse.errors = Object.values(err.errors).map(e => e.message);
    return res.status(400).json(errorResponse);
  }
  
  // Error de duplicado en BD (código MySQL 1062)
  if (err.code === 'ER_DUP_ENTRY') {
    errorResponse.message = 'Ya existe un registro con esa información';
    return res.status(409).json(errorResponse);
  }
  
  // Error de sintaxis SQL
  if (err.code && err.code.startsWith('ER_')) {
    errorResponse.message = 'Error en la base de datos';
    // No exponer detalles SQL en producción
    if (process.env.NODE_ENV !== 'development') {
      delete errorResponse.details;
    }
    return res.status(500).json(errorResponse);
  }
  
  // Error de JWT
  if (err.name === 'JsonWebTokenError' || err.name === 'TokenExpiredError') {
    errorResponse.message = 'Token inválido o expirado';
    return res.status(401).json(errorResponse);
  }
  
  // Error de conexión a BD
  if (err.code === 'ECONNREFUSED' || err.code === 'ETIMEDOUT') {
    errorResponse.message = 'Error de conexión a la base de datos';
    return res.status(503).json(errorResponse);
  }
  
  // Error genérico
  res.status(statusCode).json(errorResponse);
}

// ==================================================
// NOT FOUND HANDLER
// ==================================================
function notFoundHandler(req, res) {
  res.status(404).json({
    success: false,
    message: 'Ruta no encontrada',
    path: req.path,
    method: req.method
  });
}

// ==================================================
// ASYNC ERROR WRAPPER
// ==================================================
// Wrapper para capturar errores en funciones async
function asyncHandler(fn) {
  return (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = errorHandler;
module.exports.notFoundHandler = notFoundHandler;
module.exports.asyncHandler = asyncHandler;
