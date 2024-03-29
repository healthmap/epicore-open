FROM 503172036736.dkr.ecr.us-east-1.amazonaws.com/epicore-base-php:7.2-fpm-10192021

# #Move composer.json file so we can run the install cmd
COPY composer.json /usr/share/php/composer.json

COPY nginx-site.conf /etc/nginx/sites-enabled/default

COPY entrypoint.sh /etc/entrypoint.sh

COPY --chown=www-data:www-data . /var/www/html

COPY . /var/www/html

#As part of the Jenkins build - npm run-script build is executed
#Copy webpack dist folder to workidr
COPY ./js/dist/* /var/wwww/html/

WORKDIR /var/www/html

ENTRYPOINT ["/etc/entrypoint.sh"]
