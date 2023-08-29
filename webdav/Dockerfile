FROM httpd:2.4.43-alpine

RUN apk --no-cache add shadow

ENV WWW_DATA daemon

ENV HTTPD_PREFIX /usr/local/apache2
WORKDIR "$HTTPD_PREFIX"

RUN for i in dav dav_fs; do \
  sed -i -e "/^#LoadModule ${i}_module.*/s/^#//" "conf/httpd.conf"; \
  done;

ADD default.conf conf/sites-enabled/
RUN printf '%s\n' "Include conf/sites-enabled/*.conf" \
  >> "conf/httpd.conf";

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

EXPOSE 80/tcp

VOLUME /var/www/webdav

ENTRYPOINT [ "entrypoint.sh" ]

CMD [ "httpd-foreground" ]