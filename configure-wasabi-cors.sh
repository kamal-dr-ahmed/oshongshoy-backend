#!/bin/bash

# Wasabi CORS Configuration Script
# This script configures CORS for the Wasabi bucket to allow image loading from the frontend

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Configuring CORS for Wasabi Bucket...${NC}"

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo -e "${RED}Error: AWS CLI is not installed${NC}"
    echo "Install it with: brew install awscli"
    exit 1
fi

# Wasabi credentials from .env
WASABI_KEY="TR41Q8K5LJFNAPAPQLHZ"
WASABI_SECRET="PMeC4kUd96BfAs0BGAvheTw95nzOiB9fnIeYH6Ij"
WASABI_REGION="ap-southeast-1"
WASABI_BUCKET="oshongshoy"
WASABI_ENDPOINT="https://s3.ap-southeast-1.wasabisys.com"

# Configure AWS CLI for Wasabi
export AWS_ACCESS_KEY_ID=$WASABI_KEY
export AWS_SECRET_ACCESS_KEY=$WASABI_SECRET
export AWS_DEFAULT_REGION=$WASABI_REGION

echo -e "${YELLOW}Applying CORS configuration...${NC}"

# Apply CORS configuration
aws s3api put-bucket-cors \
  --bucket $WASABI_BUCKET \
  --cors-configuration file://wasabi-cors-config.json \
  --endpoint-url $WASABI_ENDPOINT

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ CORS configuration applied successfully!${NC}"
    
    # Verify CORS configuration
    echo -e "${YELLOW}Verifying CORS configuration...${NC}"
    aws s3api get-bucket-cors \
      --bucket $WASABI_BUCKET \
      --endpoint-url $WASABI_ENDPOINT
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ CORS configuration verified!${NC}"
    fi
else
    echo -e "${RED}✗ Failed to apply CORS configuration${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}CORS configuration completed!${NC}"
echo "Images should now load properly from Wasabi bucket."
