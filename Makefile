DOCKER_COMPOSE_RUN = docker compose run --rm
PHP_RUN = $(DOCKER_COMPOSE_RUN) php php

vendor:
	$(DOCKER_COMPOSE_RUN) php composer install

.PHONY: create-products
create-products:
	$(PHP_RUN) bin/console app:create-products ${O}

.PHONY: update-products
update-products:
	$(PHP_RUN) bin/console app:update-products ${O}
