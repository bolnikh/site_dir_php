# Схема данных

Используем последнюю версию постгрес. UTF-8 строки, ru.RU-UTF8


## Таблицы

### Разделы 

таблица sections

Поля
* id - int автоинкремент, первичный ключ
* parent_id int  index, 
* path - строка 2/34/156 из ид родителей
* name - строка до 512 символов, 
* slug - строка до 255 символов , unique, 
* description text,
* created_at 
* updated_at


### Сайты

таблица sites

Поля
* id - int автоинкремент, первичный ключ
* section_id - int индекс, 
* name - строка до 512 символов, 
* slug - строка до 255 символов , unique, 
* url - строка до 512 символов, 
* description - text, 
* email - строка до 255, 
* status small int 0 - нужна модерация, 1 - принят, 2 - отклонен, 
* created_at, 
* moderated_at


### Пользователи

храним модераторов, хотя бы одного

Поля 
* id - int автоинкремент, первичный ключ 
* username - строка до 255, уникальная 
* password - хеш пароля строка до 255
* active - small int , 1 - да, 0 - нет
* created_at 
* updated_at
