PHP_RUN = docker compose run --rm php

.PHONY: run
run:
	$(PHP_RUN) php bin/console app:generate-products --count 5

