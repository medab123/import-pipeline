FROM public.ecr.aws/h7v5u9x4/a2d/laravel84-tpl:gd-pdftk

USER root

RUN sed -i '/xdebug.mode/d' /usr/local/etc/php/php.ini && echo "xdebug.mode = profile" >> /usr/local/etc/php/php.ini
RUN sed -i '/xdebug.output_dir/d' /usr/local/etc/php/php.ini && echo "xdebug.output_dir = /tmp/xdebug_profiles" >> /usr/local/etc/php/php.ini
RUN sed -i '/max_input_vars/d' /usr/local/etc/php/php.ini && echo "max_input_vars = 10000" >> /usr/local/etc/php/php.ini
RUN sed -i '/memory_limit/d' /usr/local/etc/php/php.ini && echo "memory_limit = 1G" >> /usr/local/etc/php/php.ini

USER www-data

COPY --chown=www-data:www-data ./ .
COPY --chown=www-data:www-data ./docker/supervisord/ /opt/supervisord/

RUN composer install
RUN npm install && npm run build

