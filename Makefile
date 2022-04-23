shell:
	#docker-compose exec -u ${UID}:${UID} php sh
	docker-compose exec -u 1000:1000 php sh

up:
	docker-compose up --build -d --remove-orphans

down:
	docker-compose down --remove-orphans
