---
description: Docker development environment setup and usage
applyTo: "**/*"
---

# Docker Dev Environment

- Run `.docker/env.sh` to generate `.docker/.env` with auto-assigned ports
- Ports are deterministic per worktree directory name (hashed)
- Default admin credentials: `admin`/`admin`
- Use `make up`/`make down` to manage services (see Commands)
