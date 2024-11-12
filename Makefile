PHP_RUN = docker compose run --rm php

.PHONY: create-products
create-products:
	$(PHP_RUN) php bin/console app:create-products --count 5

.PHONY: update-products
update-products:
	$(PHP_RUN) php bin/console app:update-products --count 5

