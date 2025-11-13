#!/bin/bash

# Test Media Upload API
# Usage: ./test-media-upload.sh

API_URL="http://localhost:8088/api"
TOKEN="YOUR_AUTH_TOKEN_HERE"

echo "=== Testing Media Upload API ==="
echo ""

# Test 1: Upload Image
echo "Test 1: Uploading test image..."
curl -X POST "${API_URL}/media/upload/image" \
  -H "Authorization: Bearer ${TOKEN}" \
  -F "file=@/path/to/test/image.jpg" \
  -F "folder=articles"

echo -e "\n\n"

# Test 2: Upload Video
echo "Test 2: Uploading test video..."
# curl -X POST "${API_URL}/media/upload/video" \
#   -H "Authorization: Bearer ${TOKEN}" \
#   -F "file=@/path/to/test/video.mp4" \
#   -F "folder=videos"

echo -e "\n\n"

# Test 3: Upload File
echo "Test 3: Uploading test file..."
# curl -X POST "${API_URL}/media/upload/file" \
#   -H "Authorization: Bearer ${TOKEN}" \
#   -F "file=@/path/to/test/document.pdf" \
#   -F "folder=documents"

echo -e "\n\n"

echo "=== Tests Complete ==="
