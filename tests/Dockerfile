FROM mcr.microsoft.com/playwright:latest

WORKDIR /app

# Копируем package.json и устанавливаем зависимости
COPY package.json ./
RUN npm install

# Всё остальное монтируется через volume в docker-compose.test.yml
