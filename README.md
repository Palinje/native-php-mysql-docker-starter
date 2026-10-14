# Native PHP + MySQL + phpMyAdmin + Nginx + Grafana + Prometheus (Windows 11)

A local development stack using plain/native PHP (no framework), PHP-FPM, Nginx, MySQL, phpMyAdmin, Prometheus, and Grafana. Docker Desktop runs the Linux containers; you do not need to install PHP, MySQL, Nginx, Prometheus, or Grafana directly on Windows.

> **Local development only.** This starter binds web dashboards to `127.0.0.1`, but the example passwords are not production secrets. Change them before use, never commit `.env`, and do not expose phpMyAdmin/Prometheus/Grafana directly to the public Internet.

## 1. Prerequisites

- Windows 11, virtualization enabled, and Docker Desktop using the WSL 2 backend.
- Docker Desktop opened and showing **Engine running**.
- Visual Studio Code (recommended).
- In PowerShell, verify Docker is available:

  ```powershell
  docker --version
  docker compose version
  docker version
  ```

If `docker version` cannot contact the server/engine, start Docker Desktop and retry. Docker Desktop's Windows installation guide: https://docs.docker.com/desktop/setup/install/windows-install/

## 2. Extract and open the project

1. Extract this folder to a simple path, for example `C:\projects\native-php-mysql-docker-stack`.
2. Open that folder in VS Code (**File → Open Folder**).
3. Open **Terminal → New Terminal**. PowerShell or the VS Code WSL terminal can run Docker Compose; run commands from this project folder where `docker-compose.yml` lives.

## 3. Create local environment settings

In a **PowerShell** terminal in the project folder, run:

```powershell
Copy-Item .env.example .env
```

Open `.env` and replace every example password with your own local value. Keep these demo passwords limited to letters, numbers, and underscores unless you first adapt `docker/mysql/init/01-create-exporter.sh` to safely handle other characters. `.env` is ignored by Git.

The file supplies:

- MySQL root password
- HRIS application database/user/password
- MySQL metrics exporter password
- Grafana administrator username/password

The application connects using the least-privileged application account, not the MySQL root account.

## 4. Validate and start the stack

Run these in the project directory:

```powershell
docker compose config
docker compose up -d --build
```

The first launch downloads several images and builds the PHP image; it can take a few minutes. Then check:

```powershell
docker compose ps
```

To follow logs during first startup:

```powershell
docker compose logs -f mysql php nginx mysql-exporter nginx-exporter php-fpm-exporter prometheus grafana
```

Press **Ctrl+C** to stop following the logs; this does not stop the containers.

## 5. Open the services

| Service | URL | Notes |
|---|---|---|
| Native PHP app served by Nginx | http://localhost:8080 | Sample page checks a PDO connection to MySQL. |
| phpMyAdmin | http://localhost:8081 | Sign in as `hris_app` using `MYSQL_USER`/`MYSQL_PASSWORD` from `.env`; use root only for administration. |
| Prometheus | http://localhost:9090 | Open **Status → Targets** and confirm the targets are `UP`. |
| Grafana | http://localhost:3001 | Sign in with `GRAFANA_ADMIN_USER`/`GRAFANA_ADMIN_PASSWORD` from `.env`. |

Grafana's Prometheus data source and a starter **Native PHP Stack Overview** dashboard are provisioned automatically. Open **Dashboards → Local Stack → Native PHP Stack Overview**. You can also use **Explore** and query `up` to inspect scrape-target health.

For MySQL command-line access inside the container:

```powershell
docker compose exec mysql mysql -u hris_app -p hris_db
```

Enter the `MYSQL_PASSWORD` from `.env` when prompted. MySQL port 3306 is intentionally **not published to Windows**; other services reach it through Docker's private network.

## 6. Where to put your PHP code

- `php/index.php` is the sample application entry point.
- `Dockerfile` installs `pdo_mysql`, `mysqli`, and OPcache in the PHP-FPM image.
- `docker/nginx/default.conf` routes `.php` files to `php:9000` and exposes an internal Nginx status endpoint for the metrics exporter.
- `docker/php/www.conf` sets the PHP-FPM pool and its `/status` endpoint for monitoring.
- `docker/prometheus/prometheus.yml` sets scrape targets.
- `docker/grafana/provisioning/` defines the Prometheus data source and dashboard provider.

Edit PHP files under `php/`; the source is mounted into the running container, so normal PHP code changes are picked up without rebuilding. Rebuild if you change the Dockerfile or installed extensions:

```powershell
docker compose up -d --build php
```

## 7. Stop, restart, and remove

Stop containers but preserve database, metrics, and Grafana data:

```powershell
docker compose down
```

Start again:

```powershell
docker compose up -d
```

**Destructive:** remove containers *and* all named volumes, including the MySQL database and Grafana/Prometheus data:

```powershell
docker compose down -v
```

Only run `down -v` when you intentionally want to erase that stored data.

## 8. Common troubleshooting

### `docker` or `docker compose` is not recognized / cannot connect
Open Docker Desktop and wait for **Engine running**. In VS Code, make sure you're running the command in the project folder. `wsl --version` is a Windows command (PowerShell/CMD), not a Linux Bash command.

### The app shows a database connection error
Check startup and logs:

```powershell
docker compose ps
docker compose logs php mysql
```

Confirm `.env` values match. The official MySQL image applies `MYSQL_DATABASE`, `MYSQL_USER`, and password variables only when initializing an empty database volume; changing `.env` later does not automatically change a user/password inside an existing database.

### MySQL exporter is unhealthy or Prometheus says the MySQL target is down
Check:

```powershell
docker compose logs mysql mysql-exporter
```

The initialization hook that creates the limited `exporter` account runs only when MySQL initializes an empty data volume. If the database volume predates this file, create the `exporter` user and grant the `PROCESS`, `REPLICATION CLIENT`, and `SELECT` privileges manually, using the `MYSQL_EXPORTER_PASSWORD` value.

### Nginx shows `502 Bad Gateway`

```powershell
docker compose logs nginx php
```

Wait until PHP is healthy, and check that `docker/php/www.conf` has `listen = 9000` and Nginx uses `fastcgi_pass php:9000`.

### A host port is already in use
Change only the **left side** of the mapping in `docker-compose.yml`. For example, change `127.0.0.1:8080:80` to `127.0.0.1:8088:80`, then open `http://localhost:8088`. The right side is the port inside the container and normally should stay as shown.

### Grafana login doesn't match the new `.env` password
If the Grafana volume already exists, changing the environment value may not reset its stored admin password. Use Grafana's password reset procedure or intentionally recreate the Grafana volume (this deletes its saved data). Do not delete the MySQL volume just to reset Grafana.

## 9. Before a real deployment

This configuration is a local development baseline—not a complete production deployment. Before deploying externally, use managed secrets, HTTPS/TLS, strong access controls, database backups and tested restore procedures, least-privilege accounts, log/metrics retention limits, pinned image digests or a controlled update policy, and network/firewall restrictions. Restrict or remove phpMyAdmin and monitoring endpoints from public access. The sample page is a smoke test, not a finished HRIS application.

## Official references

- Docker Desktop on Windows: https://docs.docker.com/desktop/setup/install/windows-install/
- Docker Compose services and health checks: https://docs.docker.com/reference/compose-file/services/
- Official PHP image / PHP-FPM: https://hub.docker.com/_/php
- Official MySQL image and initialization variables: https://hub.docker.com/_/mysql/
- Official phpMyAdmin image: https://hub.docker.com/_/phpmyadmin
- Prometheus configuration: https://prometheus.io/docs/introduction/first_steps/
- MySQL exporter and required grants: https://github.com/prometheus/mysqld_exporter
- NGINX Prometheus exporter: https://github.com/nginx/nginx-prometheus-exporter
- PHP-FPM exporter: https://github.com/hipages/php-fpm_exporter
- Grafana Docker installation: https://grafana.com/docs/grafana/latest/setup-grafana/installation/docker/
- Grafana provisioning: https://grafana.com/docs/grafana/latest/administration/provisioning/
