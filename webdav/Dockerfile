ARG ALPINE_VERSION=2.4.43-alpine

FROM httpd:${ALPINE_VERSION}

RUN apk add --update git ca-certificates alpine-sdk apache2-dev linux-pam-dev redis autoconf automake hiredis-dev curl-dev make sudo

WORKDIR /usr/local/src

RUN git clone https://github.com/adelton/mod_authnz_pam.git && cd mod_authnz_pam && apxs -i -a -c mod_authnz_pam.c -lpam -Wall -pedantic
RUN git clone https://github.com/HarryKodden/SRAM-Token-Service.git && cd SRAM-Token-Service && make && make install

RUN apk --no-cache add shadow rsyslog

ENV WWW_DATA daemon

ENV HTTPD_PREFIX /usr/local/apache2
WORKDIR "$HTTPD_PREFIX"

RUN for i in dav dav_fs rewrite autoindex access_compat; do \
  sed -i -e "/^#LoadModule ${i}_module.*/s/^#//" "conf/httpd.conf"; \
  done;

RUN echo "LoadModule authnz_pam_module  modules/mod_authnz_pam.so" >> conf/httpd.conf

ADD default.conf conf/sites-enabled/
RUN echo "Include conf/sites-enabled/*.conf" >> "conf/httpd.conf";

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

EXPOSE 80/tcp

VOLUME /var/www/webdav

ENTRYPOINT [ "entrypoint.sh" ]

CMD [ "httpd-foreground" ]