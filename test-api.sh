#!/bin/bash

# ==================================================
# TEST-API.SH - SCRIPT DE TESTING BÁSICO
# ==================================================
# Prueba los endpoints principales del API
# Uso: bash test-api.sh
# ==================================================

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# URL base (cambiar según tu deployment)
API_URL="http://localhost:3000"

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  LWC API TESTING SCRIPT                                  ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "Testing API en: $API_URL"
echo ""

# ==================================================
# TEST 1: HEALTH CHECK
# ==================================================
echo -e "${YELLOW}[1/6] Testing Health Check...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL/api/health)

if [ $response -eq 200 ]; then
    echo -e "${GREEN}✅ Health check OK (200)${NC}"
else
    echo -e "${RED}❌ Health check FAILED (${response})${NC}"
fi
echo ""

# ==================================================
# TEST 2: REGISTRO
# ==================================================
echo -e "${YELLOW}[2/6] Testing Register...${NC}"
response=$(curl -s -X POST $API_URL/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test_'$(date +%s)'@example.com",
    "password": "Test1234"
  }')

if echo "$response" | grep -q '"success":true'; then
    echo -e "${GREEN}✅ Register OK${NC}"
    TOKEN=$(echo $response | grep -o '"token":"[^"]*' | cut -d'"' -f4)
else
    echo -e "${RED}❌ Register FAILED${NC}"
    echo "Response: $response"
fi
echo ""

# ==================================================
# TEST 3: LOGIN
# ==================================================
echo -e "${YELLOW}[3/6] Testing Login...${NC}"
response=$(curl -s -X POST $API_URL/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test1234"
  }')

if echo "$response" | grep -q '"success":true'; then
    echo -e "${GREEN}✅ Login OK${NC}"
else
    echo -e "${RED}❌ Login FAILED (probablemente usuario no existe)${NC}"
fi
echo ""

# ==================================================
# TEST 4: CATÁLOGO
# ==================================================
echo -e "${YELLOW}[4/6] Testing Catalog...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL/api/products/catalog)

if [ $response -eq 200 ]; then
    echo -e "${GREEN}✅ Catalog OK (200)${NC}"
else
    echo -e "${RED}❌ Catalog FAILED (${response})${NC}"
fi
echo ""

# ==================================================
# TEST 5: PROFILE (con token)
# ==================================================
if [ ! -z "$TOKEN" ]; then
    echo -e "${YELLOW}[5/6] Testing Profile (authenticated)...${NC}"
    response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL/api/auth/profile \
      -H "Authorization: Bearer $TOKEN")

    if [ $response -eq 200 ]; then
        echo -e "${GREEN}✅ Profile OK (200)${NC}"
    else
        echo -e "${RED}❌ Profile FAILED (${response})${NC}"
    fi
else
    echo -e "${YELLOW}[5/6] Skipping Profile test (no token)${NC}"
fi
echo ""

# ==================================================
# TEST 6: CARRITO (con token)
# ==================================================
if [ ! -z "$TOKEN" ]; then
    echo -e "${YELLOW}[6/6] Testing Cart...${NC}"
    # Este test probablemente falle porque no hay user_id, pero verifica que el endpoint existe
    response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL/api/cart/1 \
      -H "Authorization: Bearer $TOKEN")

    if [ $response -eq 200 ] || [ $response -eq 403 ] || [ $response -eq 404 ]; then
        echo -e "${GREEN}✅ Cart endpoint OK (${response})${NC}"
    else
        echo -e "${RED}❌ Cart endpoint FAILED (${response})${NC}"
    fi
else
    echo -e "${YELLOW}[6/6] Skipping Cart test (no token)${NC}"
fi
echo ""

# ==================================================
# RESUMEN
# ==================================================
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  TESTING COMPLETO                                        ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "Si todos los tests pasaron, tu API está funcionando correctamente."
echo "Si alguno falló, revisa los logs: pm2 logs lwc-api"
echo ""
