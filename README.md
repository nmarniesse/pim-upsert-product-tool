# PIM product upsert tool

Use the PIM API to create/update products


## Install

Pull the code, then:

```bash
docker compose build
make vendor
```


## Configure

- Create a new connection in the PIM with the admin permissions
- Create a `.env` file:

```bash
cp .env.dist .env
```

- Edit the file with the information of the connection.

### Make the config works for local PIM (docker)

In your local PIM, add the network in the `docker-compose.override.yml` file:

```yaml
networks:
  pim:
    driver: bridge
```

Restart your pim with `make up`

In the `.env` file of this project, the host should be:

```bash
HOST=http://httpd
```


## Run

**Create products:**

```bash
make create-products                # Infinite 
make create-products O="--count=2"  # 2 creations
```

**Update products:**

```bash
make update-products                # Infinite 
make update-products O="--count=2"  # 2 updates
```
