#!/bin/bash

# Script para probar la API de AFIP
# Uso: ./test-api.sh [puerto]

PORT=${1:-8080}
BASE_URL="http://localhost:$PORT"

echo "🧪 Probando API de AFIP en $BASE_URL"
echo "======================================"

# Verificar si el servicio está corriendo
echo "📡 Verificando conectividad..."
if curl -s -f "$BASE_URL" > /dev/null; then
    echo "✅ Servicio disponible"
else
    echo "❌ Servicio no disponible en $BASE_URL"
    echo "💡 Asegúrate de que Docker esté corriendo:"
    echo "   docker-compose up -d"
    exit 1
fi

# Datos de prueba
JSON_DATA='{
    "PtoVta": 1,
    "TipoComp": 1,
    "facCuit": "20123456789",
    "FechaComp": "15/12/2024",
    "facTotal": 1000.00,
    "facPeriodo_inicio": "01/12/2024",
    "facPeriodo_fin": "31/12/2024",
    "fechaUltimoDia": "15/01/2025"
}'

echo ""
echo "📝 Enviando solicitud de factura..."
echo "📊 Datos: $JSON_DATA"
echo ""

# Enviar solicitud
RESPONSE=$(curl -s -X POST "$BASE_URL/" \
    -H "Content-Type: application/json" \
    -d "$JSON_DATA")

# Verificar respuesta
if [ $? -eq 0 ]; then
    echo "✅ Respuesta recibida:"
    echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
    
    # Extraer información útil
    SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false' 2>/dev/null)
    if [ "$SUCCESS" = "true" ]; then
        echo ""
        echo "🎉 ¡Factura creada exitosamente!"
        echo "📄 Número: $(echo "$RESPONSE" | jq -r '.nro // "N/A"')"
        echo "🔢 CAE: $(echo "$RESPONSE" | jq -r '.CAE // "N/A"')"
        echo "📅 Vencimiento: $(echo "$RESPONSE" | jq -r '.Vencimiento // "N/A"')"
        echo "📁 PDF: $(echo "$RESPONSE" | jq -r '.pdfFilename // "N/A"')"
        
        # Intentar descargar el PDF
        PDF_URL=$(echo "$RESPONSE" | jq -r '.downloadLink // ""' 2>/dev/null)
        if [ -n "$PDF_URL" ] && [ "$PDF_URL" != "null" ]; then
            echo ""
            echo "📥 Descargando PDF..."
            curl -s -o "factura-test.pdf" "$PDF_URL"
            if [ $? -eq 0 ]; then
                echo "✅ PDF descargado como 'factura-test.pdf'"
            else
                echo "⚠️  No se pudo descargar el PDF"
            fi
        fi
    else
        echo ""
        echo "❌ Error en la creación de la factura:"
        echo "$(echo "$RESPONSE" | jq -r '.error // "Error desconocido"')"
    fi
else
    echo "❌ Error al conectar con la API"
fi

echo ""
echo "🔍 Para ver logs en tiempo real:"
echo "   docker-compose logs -f afip-api"
echo ""
echo "🔍 Para ver logs específicos:"
echo "   docker-compose exec afip-api tail -f logs/wsfe_error.log" 