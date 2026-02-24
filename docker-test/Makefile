.PHONY: up

up:
	docker compose build
	docker compose up -d
	dos2unix .env
	dos2unix bin/setup.sh && bin/setup.sh
	dos2unix bin/update.sh && bin/update.sh
