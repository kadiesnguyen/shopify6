#!/bin/bash
set -e

echo "=== Shopefy harness init ==="

if [ -f package.json ]; then
  echo "=== npm install ==="
  npm install

  if npm run | grep -q " lint"; then
    echo "=== npm run lint ==="
    npm run lint
  fi

  if npm run | grep -q " build"; then
    echo "=== npm run build ==="
    npm run build
  fi
else
  echo "No package.json yet — harness files only."
fi

echo "=== Verification Complete ==="
echo ""
echo "Next steps:"
echo "1. Read feature_list.json to see current feature state"
echo "2. Pick ONE unfinished feature to work on"
echo "3. Implement only that feature"
echo "4. Re-run verification before claiming done"
