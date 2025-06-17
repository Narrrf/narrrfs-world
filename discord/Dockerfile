FROM node:18-slim

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy bot files
COPY . .

# Set environment variable to indicate we're on Render
ENV RENDER=true

# Create data directory for SQLite database
RUN mkdir -p /data

# Start the bot
CMD ["node", "index.js"] 