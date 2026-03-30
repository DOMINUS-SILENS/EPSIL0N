#!/bin/bash

echo "=== ERP Frontend Verification ==="
echo ""

echo "1. Installing dependencies..."
pnpm install

echo ""
echo "2. Running type check..."
pnpm type-check

echo ""
echo "3. Running linting..."
pnpm lint

echo ""
echo "4. Building production bundle..."
pnpm build

echo ""
echo "5. Starting development server..."
pnpm dev &
DEV_PID=$!

sleep 5

echo ""
echo "6. Checking if server is running..."
if curl -s http://localhost:5173 > /dev/null; then
    echo "✓ Development server is running on http://localhost:5173"
else
    echo "✗ Development server failed to start"
fi

kill $DEV_PID

echo ""
echo "=== Verification Complete ==="
