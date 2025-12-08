# CLAUDE.md - AI Assistant Guide for LWC API Backend

## Project Overview

**LWC API Backend** is a REST API for Liberty Wave Collective (LWC), an affiliate/MLM platform with cryptocurrency payment integration. The system manages user registration, product catalog, shopping cart, crypto checkout, commission calculations, and team/downline structures.

- **Stack**: Node.js 18+ / Express.js / PostgreSQL (Supabase)
- **Version**: 7.1
- **Primary Language**: JavaScript (ES6+)
- **Database**: PostgreSQL via Supabase (note: README mentions MySQL but actual implementation uses PostgreSQL)

## Repository Structure

```
lwc-api/
├── server.js              # Express app entry point
├── package.json           # Dependencies and scripts
├── .env.example           # Environment variables template
├── .gitignore             # Git ignore rules
├── README.md              # User documentation (Spanish)
├── test-api.sh            # API testing script
├── config/
│   └── database.js        # PostgreSQL connection pool (Supabase)
├── controllers/
│   ├── authController.js      # User registration, login, profile
│   ├── productController.js   # Product catalog operations
│   ├── cartController.js      # Shopping cart management
│   ├── checkoutController.js  # Crypto payment processing
│   ├── commissionController.js # Commission queries
│   └── teamController.js      # Team/downline structure
├── middleware/
│   ├── auth.js            # JWT authentication middleware
│   ├── validation.js      # Input validation (express-validator)
│   └── errorHandler.js    # Centralized error handling
├── routes/
│   ├── auth.js            # /api/auth/* endpoints
│   ├── products.js        # /api/products/* endpoints
│   ├── cart.js            # /api/cart/* endpoints
│   ├── checkout.js        # /api/checkout/* endpoints
│   ├── commissions.js     # /api/commissions/* endpoints
│   └── team.js            # /api/team/* endpoints
└── utils/
    ├── jwt.js             # JWT token generation/verification
    ├── bcrypt.js          # Password hashing utilities
    └── commission.js      # Commission calculation logic
```

## Development Commands

```bash
# Install dependencies
npm install

# Run in development mode (with nodemon auto-reload)
npm run dev

# Run in production mode
npm start

# Health check endpoint
curl http://localhost:3000/api/health
```

## Environment Configuration

Create a `.env` file from `.env.example`. Key variables:

| Variable | Description |
|----------|-------------|
| `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_PORT` | Database connection |
| `JWT_SECRET` | Secret for signing JWT tokens (min 32 chars) |
| `JWT_EXPIRES_IN` | Token expiration (default: 24h) |
| `PORT` | Server port (default: 3000) |
| `NODE_ENV` | Environment (development/production) |
| `NOWPAYMENTS_API_KEY` | NOWPayments crypto payment API key |
| `ALLOWED_ORIGINS` | CORS allowed origins (comma-separated) |

## Architecture Patterns

### Request Flow
```
Request → Rate Limiter → CORS → Helmet → Body Parser → Routes → Middleware → Controller → Database
                                                                    ↓
                                                              Error Handler
```

### Authentication Flow
1. User registers/logs in via `/api/auth/register` or `/api/auth/login`
2. Server returns JWT access token (24h) and refresh token (7d)
3. Protected routes use `authenticateToken` middleware
4. Token included as `Authorization: Bearer <token>` header

### Database Query Pattern
The codebase uses `executeQuery()` from `config/database.js` which provides:
- Connection pooling
- Automatic retry logic (3 attempts with backoff)
- Proper client release

```javascript
const { executeQuery } = require('../config/database');
const result = await executeQuery('SELECT * FROM users WHERE user_id = $1', [userId]);
```

## API Endpoints Reference

### Authentication (`/api/auth`)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | No | Register new user |
| POST | `/login` | No | User login |
| POST | `/refresh` | No | Refresh access token |
| GET | `/profile` | Yes | Get authenticated user profile |

### Products (`/api/products`)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/catalog` | No | Full product catalog |
| GET | `/agents` | No | Individual agents only |
| GET | `/packs` | No | Product packs only |
| GET | `/vip` | No | VIP agents only |
| GET | `/:id?type=` | No | Product details by type |

### Cart (`/api/cart`)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/add` | Yes | Add item to cart |
| GET | `/:user_id` | Yes | Get user's cart |
| PUT | `/:cart_id` | Yes | Update cart item |
| DELETE | `/:cart_id` | Yes | Remove cart item |
| DELETE | `/clear/:user_id` | Yes | Clear entire cart |

### Checkout (`/api/checkout`)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/crypto` | Yes | Create crypto payment |
| POST | `/callback` | No | NOWPayments IPN webhook |
| GET | `/status/:id` | Yes | Check payment status |

### Commissions (`/api/commissions`)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/:user_id` | Yes | Commission summary |
| GET | `/:user_id/history` | Yes | Commission history |

### Team (`/api/team`)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/structure/:user_id` | Yes | Team structure/downline |
| GET | `/stats/:user_id` | Yes | Team statistics |

## Commission System

The platform implements an MLM compensation plan in `utils/commission.js`:

1. **Direct Commission (50%)**: 50% of profit goes to sponsor on direct sales
2. **Override (25-50%)**: Affiliates earn percentage of their downline's direct commissions
3. **Matrix Bonus (2.5%)**: Monthly pool distributed to active users
4. **Constructor Bonus (5%)**: Bonus for users with "constructor" type

Commission distribution is triggered when payment status becomes `completed` via the NOWPayments webhook.

## Database Tables

Key tables (PostgreSQL/Supabase):
- `users` - User accounts with LWC IDs and sponsor relationships
- `lwc_products_agents` - Individual AI agent products
- `lwc_products_packs` - Product bundles
- `lwc_products_vip_agents` - Premium VIP agents
- `lwc_cart` - Shopping cart items
- `lwc_transactions` - Payment transactions
- `lwc_transaction_items` - Transaction line items
- `lwc_commissions` - Commission records

## Code Conventions

### Response Format
All API responses follow this structure:
```javascript
{
  success: true|false,
  message: "Human readable message",
  data: { /* response payload */ },
  // Optional in dev mode:
  error: "Error details"
}
```

### Error Handling
- Controllers wrap logic in try/catch
- Errors logged with emoji prefixes (e.g., `❌ Error en registro:`)
- Development mode exposes error details; production hides them
- Centralized error handler catches unhandled errors

### Validation
Input validation uses `express-validator` in `middleware/validation.js`:
- `validateRegister` - Email, password strength, optional sponsor
- `validateLogin` - Email and password
- `validateAddToCart` - User ID, product ID, type, quantity
- `validateCryptoPayment` - User ID, amount, crypto type

### Security Features
- **Helmet**: Sets secure HTTP headers
- **Rate Limiting**: 100 requests per 15 minutes per IP
- **CORS**: Configurable allowed origins
- **bcrypt**: Password hashing with 10 salt rounds
- **JWT**: HS256 algorithm with configurable expiration
- **Input Sanitization**: XSS prevention in validation middleware

## Important Notes for AI Assistants

### Database Considerations
- The actual database is **PostgreSQL (Supabase)**, not MySQL as mentioned in README
- Database credentials are currently hardcoded in `config/database.js` - should use environment variables
- Uses `$1, $2` parameterized query style (PostgreSQL), not `?` (MySQL)

### Payment Integration
- NOWPayments integration is currently **commented out/simulated**
- `checkoutController.js` returns mock payment data
- IPN webhook signature verification is not implemented (returns `true`)

### Known Inconsistencies
1. `config/database.js` has hardcoded credentials instead of using `.env`
2. Some controllers use `?` placeholders (MySQL style) instead of `$1` (PostgreSQL)
3. README references MySQL but code uses PostgreSQL

### When Making Changes
1. Follow the existing controller pattern with detailed section comments
2. Use `executeQuery()` for all database operations
3. Always validate inputs using express-validator patterns
4. Return consistent JSON response format
5. Log errors with emoji prefixes for visibility
6. Keep Spanish/English mixed comments consistent with existing code

### Testing
Run the health check after changes:
```bash
curl http://localhost:3000/api/health
```

Expected response:
```json
{
  "status": "OK",
  "message": "LWC API Backend funcionando correctamente",
  "timestamp": "...",
  "version": "7.1"
}
```

## File-Specific Notes

| File | Notes |
|------|-------|
| `config/database.js:1-41` | Contains hardcoded Supabase credentials - security concern |
| `controllers/checkoutController.js:64-82` | NOWPayments integration is simulated |
| `utils/commission.js:11-13` | References `executeTransaction` which may not be exported from database.js |
| `controllers/productController.js:237` | Uses `?` placeholder - should be `$1` for PostgreSQL |
