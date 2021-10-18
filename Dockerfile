# FROM 503172036736.dkr.ecr.us-east-1.amazonaws.com/epicore-base-php:7.2-fpm

FROM php:7.2-fpm


RUN apt-get update -y  \
  && apt-get -y install sudo \
  && apt-get -y install vim \
  && apt-get install -y git \
  && apt-get install -y zip \
  && apt-get install -y unzip \
  && apt-get install -y jq \
  && apt-get install -y python3.7 \
  && apt-get install -y python3-pip \
  && apt-get install -y libcairo2-dev
# && apt-get install php-zip

RUN sudo apt-get install -y nginx  

RUN docker-php-ext-install pdo_mysql \
  && docker-php-ext-install opcache \
  && apt-get install libicu-dev -y \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl \
  && docker-php-ext-install mysqli \
  && apt-get remove libicu-dev icu-devtools -y
RUN { \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.interned_strings_buffer=8'; \
  echo 'opcache.max_accelerated_files=4000'; \
  echo 'opcache.revalidate_freq=2'; \
  echo 'opcache.fast_shutdown=1'; \
  echo 'opcache.enable_cli=1'; \
  } > /usr/local/etc/php/conf.d/php-opocache-cfg.ini

# Install Composer
RUN sudo curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install AWS and configure
#install and set-up aws-cli
RUN sudo curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" \
  && unzip awscliv2.zip \
  && aws/install








# #Move composer.json file so we can run the install cmd
COPY composer.json /usr/share/php/composer.json

COPY nginx-site.conf /etc/nginx/sites-enabled/default

COPY entrypoint.sh /etc/entrypoint.sh


COPY --chown=www-data:www-data . /var/www/html

COPY . /var/www/html

#As part of the Jenkins build - npm run-script build is executed
#Copy webpack dist folder to workidr
COPY ./js/dist/* /var/wwww/html/

RUN  pip3 install -r requirements.txt

WORKDIR /var/www/html


ENTRYPOINT ["/etc/entrypoint.sh"]
