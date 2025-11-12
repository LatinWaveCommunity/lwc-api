// ==================================================
// BCRYPT.JS - HASHING DE PASSWORDS
// ==================================================
// Nunca almacenar passwords en texto plano
// Bcrypt genera hashes seguros con salt
// ==================================================

const bcrypt = require('bcryptjs');

// Número de rondas para generar salt
// Más rondas = más seguro pero más lento
// 10 es un buen balance
const SALT_ROUNDS = 10;

// ==================================================
// HASHEAR PASSWORD
// ==================================================
async function hashPassword(plainPassword) {
  try {
    // Generar salt y hashear password
    const salt = await bcrypt.genSalt(SALT_ROUNDS);
    const hashedPassword = await bcrypt.hash(plainPassword, salt);
    
    return hashedPassword;
  } catch (error) {
    console.error('❌ Error hasheando password:', error.message);
    throw new Error('No se pudo procesar el password');
  }
}

// ==================================================
// COMPARAR PASSWORD
// ==================================================
async function comparePassword(plainPassword, hashedPassword) {
  try {
    // Comparar password ingresado con hash almacenado
    const isMatch = await bcrypt.compare(plainPassword, hashedPassword);
    return isMatch;
  } catch (error) {
    console.error('❌ Error comparando password:', error.message);
    throw new Error('No se pudo verificar el password');
  }
}

// ==================================================
// VALIDAR FORTALEZA DE PASSWORD
// ==================================================
function validatePasswordStrength(password) {
  // Mínimo 8 caracteres
  if (password.length < 8) {
    return {
      valid: false,
      message: 'El password debe tener al menos 8 caracteres'
    };
  }
  
  // Al menos una letra mayúscula
  if (!/[A-Z]/.test(password)) {
    return {
      valid: false,
      message: 'El password debe tener al menos una letra mayúscula'
    };
  }
  
  // Al menos una letra minúscula
  if (!/[a-z]/.test(password)) {
    return {
      valid: false,
      message: 'El password debe tener al menos una letra minúscula'
    };
  }
  
  // Al menos un número
  if (!/[0-9]/.test(password)) {
    return {
      valid: false,
      message: 'El password debe tener al menos un número'
    };
  }
  
  // Password válido
  return {
    valid: true,
    message: 'Password válido'
  };
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  hashPassword,
  comparePassword,
  validatePasswordStrength
};
