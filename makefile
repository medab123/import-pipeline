docker-clean:
	docker compose -f docker/docker-compose.yml down --rmi local && docker system prune -f --volumes
docker-build :
	docker compose -f docker/docker-compose.yml build
docker-up : docker-clean docker-build
	docker compose -f docker/docker-compose.yml up -d && docker compose -f docker/docker-compose.yml exec -T app sleep 10 && docker ps
docker-test :
	docker compose -f docker/docker-compose.yml exec  -T app bash -c " cd /var/www/html/ && php artisan test --coverage-clover=phpunit-coverage.xml --log-junit=phpunit-report.xml"
docker-copy :
	docker compose -f docker/docker-compose.yml cp app:/var/www/html/phpunit-coverage.xml ./ || true && docker compose -f docker/docker-compose.yml cp app:/var/www/html/phpunit-report.xml ./ || true
up:
	docker compose -f docker/docker-compose.yml up -d
down:
	docker compose -f docker/docker-compose.yml down
clean:
	docker compose -f docker/docker-compose.yml stop && docker compose -f docker/docker-compose.yml rm -f -v && docker system prune -f --volumes
build:
	docker compose -f docker/docker-compose.yml build
bash:
	docker compose -f docker/docker-compose.yml exec app bash
restart: down up

