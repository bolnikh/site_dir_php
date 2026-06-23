# Каталог сайтов

Сделано на claude code + deepseek
  
# Для запуска

Все команды докера выполняются из корневой директории проекта (где этот файл лежит).

sudo docker compose up -d --build
sudo docker compose exec php composer dump-autoload

# Тестовые данные

sudo docker compose exec -T postgres psql -U catalog_user -d catalog < migrations/001_initial.sql
sudo docker compose exec -T postgres psql -U catalog_user -d catalog < migrations/002_seed_data.sql

# Старт / стоп

sudo docker compose up -d
sudo docker compose down

# Доступ для пользователей

http://localhost

# Доступ для модератора

http://localhost/moderator

admin / password

