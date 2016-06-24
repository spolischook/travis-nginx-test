#!/bin/sh
sudo sed -i 's/zend_extension=xdebug.so/;zend_extension=xdebug.so/g' /etc/php/7.0/mods-available/xdebug.ini

ENV="dev"
TEST_ENV="test"

if [ $1 ]
then
    ENV="$1"
fi

mysql -u vitalik -p -e "DROP DATABASE IF EXISTS b2b_enter_$ENV; CREATE DATABASE b2b_enter_$ENV;"

rm -rf app/cache/*

app/console oro:install --env=$ENV --application-url='http://b2b-dev.local/' --organization-name=OroB2B --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin --sample-data=n --timeout=900 --force --symlink

if [ "$ENV" == "$TEST_ENV" ]
then
    app/console doctrine:fixtures:load --append --no-debug --no-interaction --env=test --fixtures ./../../package/platform/src/Oro/Bundle/TestFrameworkBundle/Fixtures
    app/console doctrine:fixture:load --env=test --append --fixtures  ./../../package/commerce/src/Oro/Component/Testing/Fixtures
fi

# app/console clank:server > /dev/null 2>&1 &

sudo sed -i 's/;zend_extension=xdebug.so/zend_extension=xdebug.so/g' /etc/php/7.0/mods-available/xdebug.ini