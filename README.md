# 🚀 LWC API BACKEND V7.1

API Backend completo para Liberty Wave Collective.  
Sistema de afiliados, pagos crypto, y comisiones automatizadas.

**Creado por:** Rafa + CODY V7.1  
**Fecha:** 4 Noviembre 2025  
**Stack:** Node.js + Express + MySQL

---

## 📋 TABLA DE CONTENIDOS

1. [Requisitos](#requisitos)
2. [Instalación Local](#instalación-local)
3. [Configuración](#configuración)
4. [Deployment en SiteGround](#deployment-en-siteground)
5. [Testing](#testing)
6. [Endpoints Disponibles](#endpoints-disponibles)
7. [Integración Frontend](#integración-frontend)
8. [NOWPayments Setup](#nowpayments-setup)
9. [Troubleshooting](#troubleshooting)

---

## ⚙️ REQUISITOS

### En tu computadora (para testing local):
- Node.js 18+ instalado
- MySQL instalado (opcional, puedes usar SiteGround directo)
- Cliente HTTP (Postman, Insomnia, o curl)

### En SiteGround:
- ✅ Base de datos MySQL ya deployada (22 tablas)
- ✅ Node.js app support (disponible en SiteGround)
- ✅ Acceso SSH o File Manager

---

## 💻 INSTALACIÓN LOCAL

### Paso 1: Descargar el proyecto

```bash
# Si tienes git:
git clone [URL-del-repo]
cd lwc-api

# O simplemente descarga la carpeta y abre terminal ahí
```

### Paso 2: Instalar dependencias

```bash
npm install
```

### Paso 3: Configurar variables de entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar .env con tus datos
nano .env  # o usa tu editor favorito
```

### Paso 4: Ejecutar en modo desarrollo

```bash
npm run dev
```

**¡Listo!** Tu API está corriendo en `http://localhost:3000`

---

## 🔧 CONFIGURACIÓN

### Archivo .env

Abre el archivo `.env` y completa estos valores:

```env
# BASE DE DATOS (usa tus credenciales de SiteGround)
DB_HOST=localhost
DB_USER=u123456789_lwc
DB_PASSWORD=TU_PASSWORD_AQUI
DB_NAME=db7vgi8dbhb3gb
DB_PORT=3306

# JWT SECRET (genera uno seguro)
# Ejecuta esto en terminal para generar:
# node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
JWT_SECRET=tu_secret_super_seguro_aqui_32_caracteres_minimo
JWT_EXPIRES_IN=24h

# SERVIDOR
PORT=3000
NODE_ENV=production

# NOWPAYMENTS (cuando tengas cuenta)
NOWPAYMENTS_API_KEY=tu_api_key_aqui
NOWPAYMENTS_SANDBOX=false

# URLS
API_BASE_URL=https://api.lwc.com
FRONTEND_URL=https://lwc.com
ALLOWED_ORIGINS=https://lwc.com,https://www.lwc.com
```

**IMPORTANTE:** 
- Nunca compartas tu archivo `.env` 
- Nunca lo subas a GitHub
- Cada servidor (local, staging, production) debe tener su propio `.env`

---

## 🌐 DEPLOYMENT EN SITEGROUND

### OPCIÓN A: Via File Manager (Más Fácil)

#### 1. Comprimir tu carpeta
```bash
zip -r lwc-api.zip lwc-api/
```

#### 2. Subir a SiteGround
- Ir a **Site Tools → File Manager**
- Navegar a `/home/usuario/public_html/`
- Crear carpeta `api`
- Subir `lwc-api.zip`
- Extraer el ZIP
- Borrar el ZIP

#### 3. Instalar dependencias via SSH
```bash
ssh usuario@tu-servidor.com
cd public_html/api/lwc-api
npm install --production
```

#### 4. Configurar .env
```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar con credenciales de SiteGround
nano .env
```

#### 5. Iniciar con Node.js App
- Ir a **Site Tools → Dev → Node.js App**
- Click en **Create Application**
- **App Root:** `/home/usuario/public_html/api/lwc-api`
- **App URL:** `https://tu-dominio.com/api`
- **Entry Point:** `server.js`
- **Node Version:** 18.x o superior
- Click **Create**
- Click **Start** en la app

### OPCIÓN B: Via SSH (Más Rápido)

```bash
# 1. Conectar via SSH
ssh usuario@tu-servidor.com

# 2. Ir a public_html
cd public_html

# 3. Crear carpeta api
mkdir api
cd api

# 4. Subir archivos (usa FTP o git clone)
# Si usas git:
git clone [URL-del-repo] lwc-api
cd lwc-api

# 5. Instalar dependencias
npm install --production

# 6. Configurar .env
cp .env.example .env
nano .env
# (editar con tus valores)

# 7. Iniciar con PM2
npm install -g pm2
pm2 start server.js --name lwc-api
pm2 save
```

### OPCIÓN C: Via PM2 (Recomendado para Production)

```bash
# Instalar PM2
npm install -g pm2

# Iniciar API
pm2 start server.js --name lwc-api

# Auto-restart on boot
pm2 startup
pm2 save

# Ver logs
pm2 logs lwc-api

# Monitorear
pm2 monit

# Reiniciar
pm2 restart lwc-api

# Detener
pm2 stop lwc-api
```

---

## ✅ TESTING

### 1. Health Check

```bash
curl https://tu-dominio.com/api/health
```

Respuesta esperada:
```json
{
  "status": "OK",
  "message": "LWC API Backend funcionando correctamente",
  "timestamp": "2025-11-04T...",
  "version": "7.1"
}
```

### 2. Registro de Usuario

```bash
curl -X POST https://tu-dominio.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test1234",
    "sponsor_lwc_id": ""
  }'
```

### 3. Login

```bash
curl -X POST https://tu-dominio.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test1234"
  }'
```

### 4. Catálogo de Productos

```bash
curl https://tu-dominio.com/api/products/catalog
```

---

## 📡 ENDPOINTS DISPONIBLES

### Autenticación
```
POST   /api/auth/register         - Registrar usuario
POST   /api/auth/login            - Iniciar sesión
POST   /api/auth/refresh          - Renovar token
GET    /api/auth/profile          - Obtener perfil (requiere token)
```

### Productos
```
GET    /api/products/catalog      - Catálogo completo
GET    /api/products/agents       - Solo agentes
GET    /api/products/packs        - Solo packs
GET    /api/products/vip          - Solo VIP agents
GET    /api/products/:id          - Detalles producto
```

### Carrito
```
POST   /api/cart/add              - Agregar al carrito
GET    /api/cart/:user_id         - Ver carrito
PUT    /api/cart/:cart_id         - Actualizar cantidad
DELETE /api/cart/:cart_id         - Eliminar item
DELETE /api/cart/clear/:user_id   - Vaciar carrito
```

### Checkout
```
POST   /api/checkout/crypto       - Crear pago crypto
POST   /api/checkout/callback     - Webhook NOWPayments
GET    /api/checkout/status/:id   - Verificar status
```

### Comisiones
```
GET    /api/commissions/:user_id          - Resumen
GET    /api/commissions/:user_id/history  - Historial
```

### Equipo
```
GET    /api/team/structure/:user_id   - Estructura
GET    /api/team/stats/:user_id       - Estadísticas
```

---

## 🔌 INTEGRACIÓN FRONTEND

### Ejemplo: Login desde tu HTML/JS

```javascript
// login.js
async function loginUser(email, password) {
  try {
    const response = await fetch('https://tu-dominio.com/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Guardar token
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      
      // Redirigir a dashboard
      window.location.href = '/dashboard.html';
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error de conexión');
  }
}
```

### Ejemplo: Request autenticado

```javascript
// Obtener perfil del usuario
async function getProfile() {
  const token = localStorage.getItem('token');
  
  const response = await fetch('https://tu-dominio.com/api/auth/profile', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data;
}
```

---

## 💳 NOWPAYMENTS SETUP

### 1. Crear cuenta
- Ir a https://nowpayments.io/
- Registrarte
- Verificar email

### 2. Obtener API Key
- Login → Account → API Keys
- Copiar tu API Key
- Pegar en `.env`: `NOWPAYMENTS_API_KEY=tu_key`

### 3. Configurar IPN (Webhook)
- Account → Settings → IPN
- IPN Callback URL: `https://tu-dominio.com/api/checkout/callback`
- Guardar

### 4. Modo Sandbox
Para testing:
```env
NOWPAYMENTS_SANDBOX=true
```

Para producción:
```env
NOWPAYMENTS_SANDBOX=false
```

### 5. Habilitar en el código
Abrir `controllers/checkoutController.js` y descomentar:
```javascript
// Línea 23-27: Descomentar
const NOWPayments = require('@nowpayments/nowpayments-api-js');
const npClient = new NOWPayments({
  apiKey: process.env.NOWPAYMENTS_API_KEY,
  sandbox: process.env.NOWPAYMENTS_SANDBOX === 'true'
});

// Línea 66-75: Descomentar
const payment = await npClient.createPayment({...});
```

---

## 🔍 TROUBLESHOOTING

### Error: "Cannot connect to MySQL"
```
✅ Verifica credenciales en .env
✅ Verifica que MySQL está corriendo
✅ Verifica que puedes conectar con otro cliente (phpMyAdmin)
```

### Error: "Token inválido"
```
✅ Verifica que JWT_SECRET está configurado
✅ Verifica que el token no expiró (24h por default)
✅ Verifica formato: "Bearer TOKEN"
```

### Error: "Port already in use"
```bash
# Cambiar puerto en .env
PORT=3001

# O matar proceso en puerto 3000
lsof -ti:3000 | xargs kill -9
```

### API no responde en SiteGround
```
✅ Verifica que Node.js App está iniciada
✅ Verifica logs: pm2 logs lwc-api
✅ Verifica que .env existe y tiene valores correctos
✅ Reinicia la app: pm2 restart lwc-api
```

### Comisiones no se calculan
```
✅ Verifica que payment_status = 'completed'
✅ Verifica que webhook de NOWPayments está configurado
✅ Revisa logs del webhook
```

---

## 📞 SOPORTE

Si tienes problemas:

1. **Revisa los logs:**
   ```bash
   pm2 logs lwc-api
   ```

2. **Verifica el health check:**
   ```bash
   curl https://tu-dominio.com/api/health
   ```

3. **Contacta a:**
   - CODY V7.1 (yo) 💖
   - Rafa (creator)

---

## 🎉 ¡LISTO PARA PRODUCCIÓN!

Tu API Backend está completa y lista para:
- ✅ Registro y login de usuarios
- ✅ Catálogo de productos dinámico
- ✅ Carrito de compras funcional
- ✅ Pagos crypto con NOWPayments
- ✅ Cálculo automático de comisiones
- ✅ Estructura de equipo y downline
- ✅ Seguridad completa (JWT, bcrypt, validación)

**Próximos pasos:**
1. Conectar tus dashboards HTML al API
2. Configurar NOWPayments para pagos reales
3. Testing completo
4. ¡Lanzar LWC! 🚀

---

**Creado con 💖 por CODY V7.1 - Hopkins Precision Guaranteed**

*Version 7.1 - November 4, 2025*
