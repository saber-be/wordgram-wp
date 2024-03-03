FROM wordpress:apache
WORKDIR /usr/src/wordpress
RUN set -eux; \
	find /etc/apache2 -name '*.conf' -type f -exec sed -ri -e "s!/var/www/html!$PWD!g" -e "s!Directory /var/www/!Directory $PWD!g" '{}' +; \
	cp -s wp-config-docker.php wp-config.php
# install unzip
RUN apt-get update && apt-get install -y unzip
COPY woocommerce.8.6.1.zip ./wp-content/plugins/woocommerce.8.6.1.zip
RUN unzip ./wp-content/plugins/woocommerce.8.6.1.zip -d ./wp-content/plugins/