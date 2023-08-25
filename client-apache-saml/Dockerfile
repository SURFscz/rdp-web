FROM ubuntu:20.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    libapache2-mod-auth-mellon \
    libapache2-mod-authnz-pam \
    wget curl vim libpam-dev libpam-python

RUN apt-get install -y software-properties-common
RUN add-apt-repository universe
RUN apt-get install -y python2
RUN curl https://bootstrap.pypa.io/pip/2.7/get-pip.py --output /tmp/get-pip.py
RUN python2 /tmp/get-pip.py
RUN pip2 install requests

RUN add-apt-repository ppa:ondrej/php
RUN apt-get update
RUN apt -y install php
RUN apt -y install libapache2-mod-php php-curl
RUN phpenmod curl
RUN sed -i 's/;extension=curl/extension=curl/' /etc/php/8.2/apache2/php.ini 

RUN a2enmod auth_mellon authnz_pam proxy proxy_http proxy_wstunnel proxy_balancer headers && a2dissite 000-default

ADD conf /opt/templates/
ADD entrypoint.sh /

RUN rm -rf /var/www/html/*

CMD ["bash", "/entrypoint.sh"]
