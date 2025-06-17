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

# Start the bot
CMD ["node", "index.js"] 