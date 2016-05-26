#!/bin/bash
set -e
step=$1
dbname=${DB_NAME:-oro_crm_test}

case $step in
     before_install)
           set +e; 
           echo "Before installing...";
           if [ "$TRAVIS_PULL_REQUEST" == "false" ]; then
                return 0
           fi
           diff=$(git diff --name-only $TRAVIS_COMMIT_RANGE);
           filteredDiff=$(git diff --name-only --diff-filter=ACMR $TRAVIS_COMMIT_RANGE);
           case $APPLICATION in
                documentation)
                       echo "Defining strategy for Documentation Tests...";
                       files=$(echo -e "$diff" | grep -e "^documentation/");
                       if [[ $files ]]; then
                          echo -e "Documentation changes were detected:\n$files";
                       else
                          echo -e "Documentation build not required!";
                          export TRAVIS_SKIP="true";
                       fi;;
                 application/*)
                        echo "Defining strategy for Tests...";
                        files=$(echo -e "$diff" | grep -e "^application/" -e "^package/" | awk -F"/" '{print $2}');
                        if [ ! -e "$TESTSUITE" ] && [[ $files ]]; then
                           echo -e "Source code changes were detected:\n$files";
                           echo -e "Building rules...";
                           echo -e "Detecting scope changes"
                           platform=$(echo -e "$files" | grep -e "^platform$");
                           crm=$(echo -e "$files" | grep -e "^crm$");
                           crm_enterprise=$(echo -e "$files" | grep -e "^crm-enterprise$");
                           commerce=$(echo -e "$files" | grep -e "^commerce$");
                           if [[ $platform ]]; then echo "Platform is detected. Run all";
                           elif [[ $crm ]] && [[ $APPLICATION == */crm* ]]; then echo "CRM is detected. Run CRM and Enterprise";
                           elif [[ crm_enterprise ]] && [[ $APPLICATION == */crm-enterprise ]]; then echo "Enterprise is detected. Run Enterprise";
                           elif [[ commerce ]] && [[ $APPLICATION == */commerce ]]; then echo "Commerce is detected. Run Commerce";
                           # TODO: add other cases for example Extensions tests
                           else
                               echo "Tests build not required!";
                               export TRAVIS_SKIP="true";
                           fi
                        else
                           echo "Source code changes were not detected";
                           echo "Tests build not required!";
                           export TRAVIS_SKIP="true";
                        fi;;
                  package/* | package)
                        echo "Defining strategy for CodeStyle...";
                        files=$(echo -e "$filteredDiff" | grep -e "^package/.*\.php$");
                        if [ ! -e "$CS" ] && [[ $files ]]; then
                           echo -e "Source code changes were detected:\n$files";
                           echo -e "Pass files to PHPCS";
                           export TRAVIS_CS_FILES=$files;
                        else
                           echo "Code Style build not required!";
                           export TRAVIS_SKIP="true";
                        fi;;
     esac
     ;;
     install)
          echo "Installing...";
          if [ ! -z "$TRAVIS_PHP_VERSION" ]; then
             phpenv config-rm xdebug.ini;
             phpenv config-add travis.php.ini;
             composer self-update;
             composer config -g github-oauth.github.com ${GITHUB_OAUTH};
          fi
          if [ ! -z "$CS" ]; then
             composer global require "squizlabs/php_codesniffer=2.3.3";
          fi
          if [[ "$APPLICATION" == "documentation" ]]; then
             cd ${APPLICATION};
             pip install -q -r requirements.txt --use-mirrors;
             pip install git+https://github.com/fabpot/sphinx-php.git;
          fi
     ;;
     before_script)
          echo  "Before script...";
          cd ${APPLICATION};
          if [ ! -z "$DB" ]; then 
             cp app/config/parameters_test.yml.dist app/config/parameters_test.yml;
          fi 
          case $DB in
               mysql)
                      mysql -u root -e "create database IF NOT EXISTS ${dbname}";
                      sed -i "s/database_driver"\:".*/database_driver"\:" pdo_mysql/g; s/database_name"\:".*/database_name"\:" ${dbname}/g; s/database_user"\:".*/database_user"\:" root/g; s/database_password"\:".*/database_password"\:" ~/g" app/config/parameters_test.yml;
               ;;
               postgresql)
                      psql -U postgres -c "CREATE DATABASE ${dbname} WITH lc_collate = 'C' template = template0;";
                      psql -U postgres -c 'CREATE EXTENSION IF NOT EXISTS "uuid-ossp";' -d ${dbname};
                      sed -i "s/database_driver"\:".*/database_driver"\:" pdo_pgsql/g; s/database_name"\:".*/database_name"\:" ${dbname}/g; s/database_user"\:".*/database_user"\:" postgres/g; s/database_password"\:".*/database_password"\:" ~/g" app/config/parameters_test.yml;
               ;; 
          esac
    ;;
    script)
          echo  "Script...";
          composer install --optimize-autoloader --no-interaction --working-dir=$TRAVIS_BUILD_DIR/tool;
          cd ${APPLICATION};
          if [[ "$APPLICATION" == "documentation" ]]; then
             sphinx-build -nW -b html -d _build/doctrees . _build/html; 
          fi
          if [ ! -z "$TESTSUITE" ]; then 
             composer install --optimize-autoloader --no-interaction;
             if [ ! -z "$DB" ]; then 
                php app/console oro:install --env test --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin --sample-data=n --organization-name=OroCRM --no-interaction --skip-assets --timeout 600;
                php app/console doctrine:fixture:load --no-debug --append --no-interaction --env=test --fixtures vendor/oro/platform/src/Oro/Bundle/TestFrameworkBundle/Fixtures; 
                if [[ "$APPLICATION" == "application/commerce" ]]; then
                    php app/console doctrine:fixture:load --no-debug --append --no-interaction --env=test --fixtures vendor/oro/commerce/src/Oro/Component/Testing/Fixtures;
                fi;
             fi;
             if [ ! -z "$PARALLEL_PROCESSES" ]; then
                cd ../..;

                echo "Cloning environment...";

                if [[ "$DB" == "mysql" ]]; then
                    mysqldump -u root ${dbname} > db.sql
                fi
                for i in `seq 2 $PARALLEL_PROCESSES`; do
                    cp -r ${APPLICATION} ${APPLICATION}_$i;
                    case $DB in
                        mysql)
                            mysql -u root -e "create database IF NOT EXISTS ${dbname}_$i";
                            mysql -u root -D ${dbname}_$i < db.sql
                        ;;
                        postgresql)
                            psql -U postgres -c "CREATE DATABASE ${dbname}_$i WITH TEMPLATE ${dbname};";
                        ;;
                    esac
                    sed -i "s/database_name"\:".*/database_name"\:" ${dbname}_$i/g" ${APPLICATION}_$i/app/config/parameters_test.yml;
                    sed -i "s/${dbname}/${dbname}_$i/g" ${APPLICATION}_$i/app/cache/test/appTestProjectContainer.php;
                done

                echo -n "Tests execution";

                SECONDS=0

                # run background processes and save PIDs
                for i in `seq 1 $PARALLEL_PROCESSES`; do
                    if [ $i -eq 1 ]; then
                        DIRECTORY="${APPLICATION}"
                    else
                        DIRECTORY="${APPLICATION}_$i"
                    fi
                    cd $DIRECTORY
                    { php $TRAVIS_BUILD_DIR/tool/vendor/bin/phpunit --stderr --testsuite=$TESTSUITE-$i-of-$PARALLEL_PROCESSES > ../../result.$i 2>&1 ; echo "$?" > "../../code.$i" ; } &
                    PIDS[$i]=$!
                    cd ../..
                done

                # wait until processes finish
                PROCESSES_WORK=1
                while [ "$PROCESSES_WORK" -eq 1 ]; do
                    sleep 1
                    echo -n "."
                    PROCESSES_WORK=0
                    for i in `seq 1 $PARALLEL_PROCESSES`; do
                        if ps -p ${PIDS[$i]} > /dev/null; then
                           PROCESSES_WORK=1
                           break
                        fi
                    done
                done

                # print result
                for i in `seq 1 $PARALLEL_PROCESSES`; do
                    printf "\n>>> Testsuite \"$TESTSUITE-$i-of-$PARALLEL_PROCESSES\":\n"
                    cat result.$i
                done

                printf "\n>>> Execution time - $(($SECONDS / 60)) minutes $(($SECONDS % 60)) seconds.\n"

                # return first error code
                for i in `seq 1 $PARALLEL_PROCESSES`; do
                    if [ ! -f code.$i ]; then
                        exit 1
                    fi
                    CODE=`cat code.$i`
                    if [ $CODE -ne 0 ]; then
                        exit $CODE
                    fi
                done
             else
                 php $TRAVIS_BUILD_DIR/tool/vendor/bin/phpunit --stderr --testsuite ${TESTSUITE};
             fi
          fi
          if [ ! -z "$CS" ]; then
             APPLICATION_PWD=$PWD
             cd ..;
             TEST_FILES=$(if [ ! -z "$TRAVIS_CS_FILES" ]; then echo $TRAVIS_CS_FILES; else echo "$APPLICATION_PWD/."; fi);
             $HOME/.composer/vendor/bin/phpcs $TEST_FILES -p --encoding=utf-8 --extensions=php --standard=psr2;
          fi
    ;;
esac
