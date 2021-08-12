FROM yiisoftware/yii2-php:7.3-apache

COPY ./config/apache.conf /etc/apache2/sites-available/000-default.conf

ADD . /app
WORKDIR /app

# Делаем entrypoint исполняемым
COPY /provision/entrypoint.sh /usr/local/bin/
RUN sed -i 's/\r//' /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["entrypoint.sh"]

# Раздаем права на папки для apache
RUN chown -R www-data:www-data /app/server/web

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]

