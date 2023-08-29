FROM golang:1.21.0

FROM ubuntu:20.04

RUN apt update -y
RUN apt upgrade -y

ENV DEBIAN_FRONTEND="noninteractive"

RUN apt install -y --no-install-recommends php7.4-fpm nginx supervisor git ca-certificates gettext-base php-curl vim
RUN phpenmod curl
RUN sed -i "s/;clear_env = no/clear_env = no/" /etc/php/*/fpm/pool.d/www.conf

COPY --from=0 /usr/local/go /usr/local/

ENV PATH="/usr/local/go/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

RUN git clone https://github.com/vouch/vouch-proxy.git && cd vouch-proxy && ./do.sh goget && ./do.sh build && ./do.sh install

COPY server.conf /etc/nginx/sites-enabled/default

ADD vouch.conf /etc/nginx/snippets/
ADD vouch.yml.template /etc/
ADD  supervisord.conf /etc/supervisor/conf.d/

RUN groupmod -g 1000 www-data
RUN usermod -u 1000 www-data

RUN rm -rf /var/www/html/*

RUN mkdir /files
RUN chown www-data:www-data /files

VOLUME /files

CMD ["/usr/bin/supervisord", "-n"]
