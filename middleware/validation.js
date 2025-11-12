// ==================================================
// VALIDATION.JS - VALIDACIÓN DE INPUTS
// ==================================================
// Validación y sanitización usando express-validator
// Previene SQL injection, XSS, y datos inválidos
// ==================================================

const { body, param, query, validationResult } = require('express-validator');

// ==================================================
// HELPER: MANEJAR ERRORES DE VALIDACIÓN
// ==================================================
function handleValidationErrors(req, res, next) {
  const errors = validationResult(req);
  
  if (!errors.isEmpty()) {
    return res.status(400).json({
      success: false,
      message: 'Errores de validación',
      errors: errors.array().map(err => ({
        field: err.path,
        message: err.msg
      }))
    });
  }
  
  next();
}

// ==================================================
// VALIDACIÓN: REGISTRO
// ==================================================
const validateRegister = [
  body('email')
    .isEmail().withMessage('Email inválido')
    .normalizeEmail()
    .trim(),
  
  body('password')
    .isLength({ min: 8 }).withMessage('Password debe tener mínimo 8 caracteres')
    .matches(/[A-Z]/).withMessage('Password debe tener al menos una mayúscula')
    .matches(/[a-z]/).withMessage('Password debe tener al menos una minúscula')
    .matches(/[0-9]/).withMessage('Password debe tener al menos un número'),
  
  body('sponsor_lwc_id')
    .optional()
    .isString().withMessage('LWC ID de sponsor debe ser texto')
    .trim(),
  
  handleValidationErrors
];

// ==================================================
// VALIDACIÓN: LOGIN
// ==================================================
const validateLogin = [
  body('email')
    .isEmail().withMessage('Email inválido')
    .normalizeEmail()
    .trim(),
  
  body('password')
    .notEmpty().withMessage('Password es requerido'),
  
  handleValidationErrors
];

// ==================================================
// VALIDACIÓN: AGREGAR AL CARRITO
// ==================================================
const validateAddToCart = [
  body('user_id')
    .isInt({ min: 1 }).withMessage('User ID debe ser un número válido'),
  
  body('product_id')
    .isInt({ min: 1 }).withMessage('Product ID debe ser un número válido'),
  
  body('product_type')
    .isIn(['agent', 'pack', 'vip']).withMessage('Tipo de producto inválido'),
  
  body('quantity')
    .optional()
    .isInt({ min: 1 }).withMessage('Cantidad debe ser al menos 1'),
  
  handleValidationErrors
];

// ==================================================
// VALIDACIÓN: CRYPTO PAYMENT
// ==================================================
const validateCryptoPayment = [
  body('user_id')
    .isInt({ min: 1 }).withMessage('User ID debe ser un número válido'),
  
  body('total_amount')
    .isFloat({ min: 0.01 }).withMessage('Monto debe ser mayor a 0'),
  
  body('crypto_type')
    .optional()
    .isIn(['USDT', 'BTC', 'ETH', 'BNB']).withMessage('Tipo de crypto inválido'),
  
  handleValidationErrors
];

// ==================================================
// VALIDACIÓN: USER ID EN PARAMS
// ==================================================
const validateUserId = [
  param('user_id')
    .isInt({ min: 1 }).withMessage('User ID debe ser un número válido'),
  
  handleValidationErrors
];

// ==================================================
// VALIDACIÓN: PRODUCT ID EN PARAMS
// ==================================================
const validateProductId = [
  param('product_id')
    .isInt({ min: 1 }).withMessage('Product ID debe ser un número válido'),
  
  handleValidationErrors
];

// ==================================================
// VALIDACIÓN: PAGINATION
// ==================================================
const validatePagination = [
  query('page')
    .optional()
    .isInt({ min: 1 }).withMessage('Página debe ser un número mayor a 0'),
  
  query('limit')
    .optional()
    .isInt({ min: 1, max: 100 }).withMessage('Límite debe estar entre 1 y 100'),
  
  handleValidationErrors
];

// ==================================================
// SANITIZACIÓN: PREVENIR XSS
// ==================================================
function sanitizeInput(req, res, next) {
  // Remover caracteres potencialmente peligrosos
  const sanitize = (obj) => {
    for (let key in obj) {
      if (typeof obj[key] === 'string') {
        // Remover scripts y tags HTML
        obj[key] = obj[key]
          .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
          .replace(/<[^>]+>/g, '')
          .trim();
      } else if (typeof obj[key] === 'object' && obj[key] !== null) {
        sanitize(obj[key]);
      }
    }
  };
  
  if (req.body) sanitize(req.body);
  if (req.query) sanitize(req.query);
  if (req.params) sanitize(req.params);
  
  next();
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  validateRegister,
  validateLogin,
  validateAddToCart,
  validateCryptoPayment,
  validateUserId,
  validateProductId,
  validatePagination,
  sanitizeInput,
  handleValidationErrors
};
