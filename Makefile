PHP_RUN = docker compose run --rm php

.PHONY: run
run:
	$(PHP_RUN) php -v

