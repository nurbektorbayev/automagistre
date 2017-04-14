cs:
	docker-compose run --rm -e XDEBUG=false --entrypoint php-cs-fixer app fix --config .php_cs.dist
phpstan:
	docker-compose run --rm -e XDEBUG=false app phpstan analyse --level 5 --configuration phpstan.neon src tests
test:
	docker-compose run --rm -e APP_ENV=test -e APP_DEBUG=0 app