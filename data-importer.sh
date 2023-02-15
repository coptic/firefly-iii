docker run \
--rm \
-e FIREFLY_III_ACCESS_TOKEN=821c0c6373400977277a61f78b8ea8c3 \
-e FIREFLY_III_URL=localhost:56082 \
-p 8081:8080 \
fireflyiii/data-importer:latest