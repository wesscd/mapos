FROM node:24-bookworm-slim
RUN apt-get update && apt-get install -y --no-install-recommends git ca-certificates && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev && npm cache clean --force
COPY src ./src
COPY test ./test
RUN mkdir /data && chown node:node /data
USER node
ENV NODE_ENV=production DATA_DIR=/data TOKEN_FILE=/run/secrets/whatsapp_token
EXPOSE 3000
CMD ["node", "src/server.mjs"]
