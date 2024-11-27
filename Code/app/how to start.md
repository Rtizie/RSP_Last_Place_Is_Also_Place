# Jak spustit
## Prekvizity
- composer install
- docker compose build
- docker compose up -d
- php bin/console doctrine:migrations:migrate

Pokud potřebuji něco měnit v databázi ve smyslu vytvářím novou entitu, tak jdu do .env a u DATABASE_URL změním mysql na 127.0.0.1
Pokud potřebuji aby fungovala aplikace změním v .env DATABASE_URL z 127.0.0.1

# Aktualni nastaveni role 

VE Visual studio code v terminalu vlozte tento prikaz docker exec -it symfony_mysql mysql -u root -p

Zadejte heslo: rootpassword

poté USE symfony; pro pouziti databaze ve ktere jsou data

UPDATE user SET roles = JSON_ARRAY('ROLE_ADMIN') WHERE username = 'vase uzivatelske jmeno';

a máte hotovson, stačí už dát jen exit;