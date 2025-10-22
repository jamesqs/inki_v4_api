#!/bin/bash

##
# Generate API Documentation
#
# This script generates OpenAPI specification and Postman collection
# from your Laravel API routes.
#
# Usage:
#   ./scripts/generate-api-docs.sh
#

echo "🚀 Generating API Documentation..."
echo ""

# Step 1: Generate OpenAPI spec
echo "1️⃣ Generating OpenAPI specification..."
php artisan scramble:export

if [ ! -f "api.json" ]; then
    echo "❌ Error: OpenAPI spec not generated"
    exit 1
fi

echo "   ✅ OpenAPI spec generated: api.json"
echo ""

# Step 2: Convert to Postman collection
echo "2️⃣ Converting to Postman collection..."
openapi2postmanv2 -s api.json -o postman_collection.json -p > /dev/null 2>&1

if [ ! -f "postman_collection.json" ]; then
    echo "❌ Error: Postman collection not generated"
    echo "💡 Make sure openapi-to-postmanv2 is installed:"
    echo "   npm install -g openapi-to-postmanv2"
    exit 1
fi

echo "   ✅ Postman collection generated: postman_collection.json"
echo ""

# Summary
echo "════════════════════════════════════════════════════════════"
echo "✅ API Documentation Generated Successfully!"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "📄 Files created:"
echo "   - api.json ($(du -h api.json | cut -f1))"
echo "   - postman_collection.json ($(du -h postman_collection.json | cut -f1))"
echo ""
echo "📖 View interactive documentation:"
echo "   http://inki.api.test/docs/api"
echo ""
echo "📥 Import to Postman:"
echo "   1. Open Postman"
echo "   2. Click 'Import' button"
echo "   3. Select 'postman_collection.json'"
echo ""
