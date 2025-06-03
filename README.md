# snmp-backend
Backend to get SNMP data from devices through a JSON API

## Build docker image

    $ docker build -t 'snmp-backend' .

## Run docker image (testing)

    $ docker run --rm -d -p 9020:80 \
    --volume=$PWD/src:/var/local/www/src \
    --volume=$PWD/public:/var/local/www/public \
    --volume=$PWD/logs:/var/local/www/logs \
    --volume=$PWD/logs:/var/log/apache2 \
    --name snmp-1 snmp-backend

## Run docker image (production)

    $ docker run --rm -d -p 9020:80 \
    --volume=$PWD/logs:/var/local/www/logs \
    --volume=$PWD/logs:/var/log/apache2 \
    --name snmp-1 snmp-backend

