# syntax=docker/dockerfile:1
FROM node:20-alpine
WORKDIR /app
ENV NODE_ENV=production
COPY package.json package-lock.json ./
RUN npm ci --omit=dev
COPY src ./src
COPY public ./public
COPY sql ./sql
COPY scripts ./scripts
RUN mkdir -p uploads/sell
EXPOSE 3000
CMD ["node", "src/server.js"]
