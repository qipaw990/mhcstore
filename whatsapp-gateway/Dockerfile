# ============================================================
# CicalengkaGO WhatsApp Gateway - Dockerfile
# Untuk deploy di CasaOS via Docker
# ============================================================
FROM node:20-slim

# Install Chromium dependencies untuk puppeteer (headless browser)
RUN apt-get update && apt-get install -y \
    chromium \
    fonts-freefont-ttf \
    fonts-ipafont-gothic \
    fonts-wqy-zenhei \
    fonts-thai-tlwg \
    fonts-kacst \
    fonts-symbola \
    fonts-noto \
    libatk-bridge2.0-0 \
    libdrm2 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libxkbcommon0 \
    libasound2 \
    libpangocairo-1.0-0 \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Set Chromium path untuk puppeteer
ENV PUPPETEER_SKIP_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Working directory
WORKDIR /app

# Copy package files & install dependencies
COPY package*.json ./
RUN npm ci --only=production

# Copy source code
COPY . .

# Create auth session directory
RUN mkdir -p .wwebjs_auth

# Expose port
EXPOSE 3001

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD node -e "require('http').get('http://localhost:3001/status', r => r.statusCode === 200 ? process.exit(0) : process.exit(1)).on('error', () => process.exit(1))"

# Start server
CMD ["node", "server.js"]
