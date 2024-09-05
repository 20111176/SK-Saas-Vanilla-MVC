update:
	composer install && composer update;

npm:
	npm install && npm update;

tailwind-watch:
	npx tailwindcss -i src/input.css -o public/assets/css/site.css --watch

tailwind-build:
	npx tailwindcss -i src/input.css -o public/assets/css/site.css --build

run:
	docker compose up -d;

down:
	docker compose down;