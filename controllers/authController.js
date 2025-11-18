// ==================================================
// AUTHCONTROLLER.JS - AUTENTICACIÓN
// ==================================================
// Maneja registro, login, y refresh de tokens
// ==================================================

const { executeQuery } = require('../config/database');
const { hashPassword, comparePassword, validatePasswordStrength } = require('../utils/bcrypt');
const { generateToken, generateRefreshToken, verifyToken } = require('../utils/jwt');

// ==================================================
// REGISTRO DE USUARIO
// ==================================================
async function register(req, res) {
  try {
    const { email, password, sponsor_lwc_id } = req.body;
    
    // Validar fortaleza del password
    const passwordCheck = validatePasswordStrength(password);
    if (!passwordCheck.valid) {
      return res.status(400).json({
        success: false,
        message: passwordCheck.message
      });
    }
    
    // Verificar que el email no exista
    const existingUser = await executeQuery(
      'SELECT user_id FROM lwc_users WHERE email = $1',
      [email]
    );
    
    if (existingUser.length > 0) {
      return res.status(409).json({
        success: false,
        message: 'El email ya está registrado'
      });
    }
    
    // Verificar sponsor (si se proporciona)
    let sponsor_id = null;
    if (sponsor_lwc_id) {
      const sponsor = await executeQuery(
        'SELECT user_id FROM lwc_users WHERE lwc_id = $1',
        [sponsor_lwc_id]
      );
      
      if (sponsor.length === 0) {
        return res.status(404).json({
          success: false,
          message: 'LWC ID de sponsor no encontrado'
        });
      }
      
      sponsor_id = sponsor[0].user_id;
    }
    
    // Hashear password
    const hashedPassword = await hashPassword(password);
    
    // Generar LWC ID único (LWC + timestamp + random)
    const lwc_id = `LWC${Date.now()}${Math.floor(Math.random() * 1000)}`;
    
    // Insertar usuario
    const result = await executeQuery(`
      INSERT INTO lwc_users 
      (email, password_hash, lwc_id, sponsor_id, user_type, registration_date)
      VALUES ($1, $2, $3, $4, 'cliente', NOW())
      RETURNING user_id
    `, [email, hashedPassword, lwc_id, sponsor_id]);
    
    const user_id = result[0].user_id;
    
    // Generar tokens
    const token = generateToken({
      user_id,
      email,
      lwc_id,
      user_type: 'cliente'
    });
    
    const refreshToken = generateRefreshToken({
      user_id,
      email
    });
    
    // Responder con éxito
    res.status(201).json({
      success: true,
      message: 'Usuario registrado exitosamente',
      data: {
        user_id,
        email,
        lwc_id,
        user_type: 'cliente',
        token,
        refreshToken
      }
    });
    
  } catch (error) {
    console.error('❌ Error en registro:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al registrar usuario',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
}

// ==================================================
// LOGIN
// ==================================================
async function login(req, res) {
  try {
    const { email, password } = req.body;
    
    // Buscar usuario por email
    const users = await executeQuery(
      'SELECT * FROM lwc_users WHERE email = $1',
      [email]
    );
    
    if (users.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Email o password incorrectos'
      });
    }
    
    const user = users[0];
    
    // Verificar password
    const isMatch = await comparePassword(password, user.password_hash);
    
    if (!isMatch) {
      return res.status(401).json({
        success: false,
        message: 'Email o password incorrectos'
      });
    }
    
    // Actualizar último login
    await executeQuery(
      'UPDATE lwc_users SET last_login = NOW() WHERE user_id = $1',
      [user.user_id]
    );
    
    // Generar tokens
    const token = generateToken({
      user_id: user.user_id,
      email: user.email,
      lwc_id: user.lwc_id,
      user_type: user.user_type
    });
    
    const refreshToken = generateRefreshToken({
      user_id: user.user_id,
      email: user.email
    });
    
    // Responder con éxito
    res.status(200).json({
      success: true,
      message: 'Login exitoso',
      data: {
        user: {
          user_id: user.user_id,
          email: user.email,
          lwc_id: user.lwc_id,
          user_type: user.user_type,
          full_name: user.full_name
        },
        token,
        refreshToken
      }
    });
    
  } catch (error) {
    console.error('❌ Error en login:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al iniciar sesión',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
}

// ==================================================
// REFRESH TOKEN
// ==================================================
async function refreshAccessToken(req, res) {
  try {
    const { refreshToken } = req.body;
    
    if (!refreshToken) {
      return res.status(400).json({
        success: false,
        message: 'Refresh token requerido'
      });
    }
    
    // Verificar refresh token
    const decoded = verifyToken(refreshToken);
    
    // Buscar usuario
    const users = await executeQuery(
      'SELECT * FROM lwc_users WHERE user_id = $1',
      [decoded.user_id]
    );
    
    if (users.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Usuario no encontrado'
      });
    }
    
    const user = users[0];
    
    // Generar nuevo access token
    const newToken = generateToken({
      user_id: user.user_id,
      email: user.email,
      lwc_id: user.lwc_id,
      user_type: user.user_type
    });
    
    res.status(200).json({
      success: true,
      message: 'Token renovado',
      data: {
        token: newToken
      }
    });
    
  } catch (error) {
    console.error('❌ Error renovando token:', error.message);
    res.status(401).json({
      success: false,
      message: 'Refresh token inválido o expirado'
    });
  }
}

// ==================================================
// OBTENER PERFIL
// ==================================================
async function getProfile(req, res) {
  try {
    const user_id = req.user.user_id; // Del middleware auth
    
    const users = await executeQuery(
      'SELECT user_id, email, lwc_id, user_type, full_name, wallet_address, registration_date FROM lwc_users WHERE user_id = $1',
      [user_id]
    );
    
    if (users.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Usuario no encontrado'
      });
    }
    
    res.status(200).json({
      success: true,
      data: users[0]
    });
    
  } catch (error) {
    console.error('❌ Error obteniendo perfil:', error.message);
    res.status(500).json({
      success: false,
      message: 'Error al obtener perfil'
    });
  }
}

// ==================================================
// EXPORTAR
// ==================================================
module.exports = {
  register,
  login,
  refreshAccessToken,
  getProfile
};
