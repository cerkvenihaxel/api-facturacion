#!/bin/bash

# Script para probar el servidor de desarrollo de PHP
# Uso: ./test-server.sh [ip] [puerto]

IP=${1:-"localhost"}
PORT=${2:-"8000"}
BASE_URL="http://$IP:$PORT"

echo "🧪 Probando servidor de desarrollo PHP en $BASE_URL"
echo "=================================================="

# Función para hacer peticiones HTTP
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo ""
    echo "📡 Probando: $method $endpoint"
    echo "📝 Descripción: $description"
    echo "----------------------------------------"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\nHTTP_CODE:%{http_code}" "$BASE_URL$endpoint")
    else
        response=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X "$method" -H "Content-Type: application/json" -d "$data" "$BASE_URL$endpoint")
    fi
    
    # Separar respuesta y código HTTP
    http_code=$(echo "$response" | grep "HTTP_CODE:" | cut -d: -f2)
    body=$(echo "$response" | sed '/HTTP_CODE:/d')
    
    echo "📊 Código HTTP: $http_code"
    echo "📄 Respuesta:"
    echo "$body" | python3 -m json.tool 2>/dev/null || echo "$body"
    
    if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
        echo "✅ Éxito"
    else
        echo "❌ Error"
    fi
}

# Verificar si el servidor está ejecutándose
echo "🔍 Verificando si el servidor está ejecutándose..."
if curl -s --connect-timeout 5 "$BASE_URL" > /dev/null 2>&1; then
    echo "✅ Servidor está ejecutándose"
else
    echo "❌ Servidor no está ejecutándose en $BASE_URL"
    echo ""
    echo "🚀 Para iniciar el servidor, ejecuta:"
    echo "   cd public && php -S $IP:$PORT"
    echo ""
    echo "   O desde la raíz del proyecto:"
    echo "   php -S $IP:$PORT -t public"
    exit 1
fi

# Probar endpoints
test_endpoint "GET" "/health" "" "Health check del sistema"

test_endpoint "GET" "/nonexistent" "" "Endpoint inexistente (debe devolver 404)"

test_endpoint "POST" "/" '{
    "PtoVta": 1,
    "TipoComp": 1,
    "facCuit": "20123456789",
    "FechaComp": "15/12/2024",
    "facTotal": 1000.00,
    "facPeriodo_inicio": "01/12/2024",
    "facPeriodo_fin": "31/12/2024",
    "fechaUltimoDia": "15/01/2025"
}' "Crear factura (puede fallar si no hay certificados AFIP)"

test_endpoint "OPTIONS" "/health" "" "CORS preflight request"

echo ""
echo "🎯 Resumen de pruebas completado"
echo "=================================="
echo ""
echo "📋 Para usar con Postman:"
echo "   - URL base: $BASE_URL"
echo "   - Health check: $BASE_URL/health"
echo "   - Crear factura: $BASE_URL/"
echo ""
echo "🔧 Para iniciar el servidor manualmente:"
echo "   cd public && php -S $IP:$PORT"
echo ""
echo "📚 Documentación completa en POSTMAN-README.md" 