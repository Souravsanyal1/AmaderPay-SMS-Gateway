# Use lightweight Node.js 20 Alpine base image
FROM node:20-alpine AS base

# Set working directory inside container
WORKDIR /app

# Copy package manifest from backend folder
COPY amaderpay_flutter/backend/package*.json ./

# Install production dependencies only
RUN npm ci --only=production || npm install --only=production

# Copy backend source code
COPY amaderpay_flutter/backend/ .

# Set default Node environment to production
ENV NODE_ENV=production

# Expose default port (Render will inject PORT at runtime)
EXPOSE 3000

# Start the server
CMD ["node", "server.js"]
