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
                           commerce_enterprise=$(echo -e "$files" | grep -e "^commerce-enterprise$");
                           if [[ $platform ]]; then echo "Platform is detected. Run all";
                           elif [[ $crm ]] && [[ $APPLICATION == */crm* ]]; then echo "CRM is detected. Run CRM and CRM Enterprise";
                           elif [[ crm_enterprise ]] && [[ $APPLICATION == */crm-enterprise ]]; then echo "CRM Enterprise is detected. Run CRM Enterprise";
                           elif [[ commerce ]] && [[ $APPLICATION == */commerce* ]]; then echo "Commerce is detected. Run Commerce and Commerce Enterprise";
                           elif [[ commerce_enterprise ]] && [[ $APPLICATION == */commerce-enterprise ]]; then echo "Commerce Enterprise is detected. Run Commerce Enterprise";
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
                           echo -e "Pass files to PHPCS and PHPMD";
                           export TRAVIS_CS_FILES=$files;
                           commerce=$(echo -e "$files" | grep -e "^commerce$");
                           if [[ $commerce ]]; then export TRAVIS_CS_COMMERCE='YES'; fi
                         elif [ ! -e "$JS" ]; then
                           echo -e "Source code changes were detected:\n$files";
                           echo -e "Pass files to JavaScript";
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
             composer install --optimize-autoloader --no-interaction --working-dir=$TRAVIS_BUILD_DIR/tool;
          fi
          if [[ "$APPLICATION" == "documentation" ]]; then
             cd ${APPLICATION};
             pip install -q -r requirements.txt --use-mirrors;
             pip install git+https://github.com/fabpot/sphinx-php.git;
          fi
          if [ ! -z "$JS" ]; then
             cd $TRAVIS_BUILD_DIR/tool;
             npm install;
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
          if [ ! -z "$UPDATE_FROM" ]; then
              git clone https://${GITHUB_OAUTH}@github.com/laboro/Builds.git builds
              echo  "Restore DB ${UPDATE_FROM}...";
              case $DB in
                 mysql)
                        mysql -u root -D ${dbname} < builds/DBDumps/${UPDATE_FROM}.mysql.sql;
                 ;;
                 postgresql)
                        psql -U postgres ${dbname} < builds/DBDumps/${UPDATE_FROM}.pgsql.sql > /dev/null
                 ;;
              esac
              rm -rf builds
              INSTALLED_DATE=`date --rfc-3339=seconds`
              sed -i "s/installed"\:".*/installed"\:" '${INSTALLED_DATE}'/g" app/config/parameters_test.yml;
          fi
    ;;
    script)
          echo  "Script...";

          cd ${APPLICATION};
          if [[ "$APPLICATION" == "documentation" ]]; then
             sphinx-build -nW -b html -d _build/doctrees . _build/html; 
          fi
          if [ ! -z "$TESTSUITE" ]; then
             TEST_RUNNER_OPTIONS=''
             if [ ! -z "$SOAP" ]; then
                 TEST_RUNNER_OPTIONS='--stderr --group=soap'
             fi
             composer install --optimize-autoloader --no-interaction;
             if [ ! -z "$DB" ]; then
                SKIP_ASSETS='--skip-assets'
                if [ ! -z "$WITH_ASSETS" ]; then
                    SKIP_ASSETS=''
                fi
                if [ ! -z "$UPDATE_FROM" ]; then
                    php app/console oro:platform:update --env test --force --no-interaction ${SKIP_ASSETS} --timeout 600;
                else
                    php app/console oro:install --env test --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin --sample-data=n --organization-name=OroCRM --no-interaction ${SKIP_ASSETS} --timeout 600;
                fi
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
                    { $TRAVIS_BUILD_DIR/tool/vendor/bin/phpunit --verbose ${TEST_RUNNER_OPTIONS} --testsuite=$TESTSUITE-$i-of-$PARALLEL_PROCESSES > ../../result.$i 2>&1 ; echo "$?" > "../../code.$i" ; } &
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
                 php $TRAVIS_BUILD_DIR/tool/vendor/bin/phpunit --testsuite ${TESTSUITE} ${TEST_RUNNER_OPTIONS};
             fi
          fi
          if [ ! -z "$CS" ]; then
             APPLICATION_PWD=$PWD
             cd ..;
             TEST_FILES=$(if [ ! -z "$TRAVIS_CS_FILES" ]; then echo $TRAVIS_CS_FILES; else echo "$APPLICATION_PWD/."; fi);
             $TRAVIS_BUILD_DIR/tool/vendor/bin/phpcs $TEST_FILES -p --encoding=utf-8 --extensions=php --standard=psr2;
             if [ ! -z "$TRAVIS_CS_FILES" ]; then
                TEST_FILES=${TRAVIS_CS_FILES//$'\n'/,};
                $TRAVIS_BUILD_DIR/tool/vendor/bin/phpmd $TEST_FILES text $TRAVIS_BUILD_DIR/tool/codestandards/rulesetMD.xml --suffixes php;
             fi
             if [ ! -z "$TRAVIS_CS_FILES" ] && [ ! -z "$TRAVIS_CS_COMMERCE" ]; then
                $TRAVIS_BUILD_DIR/tool/vendor/bin/phpcpd --min-lines 25 \
                                --exclude=AccountBundle/Migrations/Schema \
                                --exclude=PaymentBundle/Migrations/Schema \
                                --exclude=PricingBundle/Migrations/Schema \
                                --exclude=ProductBundle/Migrations/Schema \
                                --exclude=RFPBundle/Migrations/Schema \
                                --exclude=SaleBundle/Migrations/Schema \
                                --exclude=OrderBundle/Migrations/Schema \
                                --exclude=InvoiceBundle/Migrations/Schema \
                                --exclude=ShoppingListBundle/Migrations/Schema \
                                --exclude=WebsiteBundle/Migrations/Schema \
                                --exclude=CatalogBundle/Migrations/Schema \
                                --exclude=CMSBundle/Migrations/Schema \
                                --exclude=WarehouseBundle/Migrations/Schema \
                                --exclude=TaxBundle/Migrations/Schema \
                                --exclude=MenuBundle/Migrations/Schema \
                                --exclude=CheckoutBundle/Migrations/Schema \
                                --exclude=AlternativeCheckoutBundle/Migrations/Schema \
                                --exclude=ShippingBundle/Migrations/Schema \
                                --exclude=SaleBundle/Entity \
                                --exclude=RFPBundle/Entity \
                                --exclude=AlternativeCheckoutBundle/Entity \
                                --verbose $APPLICATION_PWD"/commerce";
             fi
          fi
          if [ ! -z "$JS" ]; then
             cd $TRAVIS_BUILD_DIR;
             set +e; 
             tool/node_modules/.bin/jscs package/*/src/*/Bundle/*Bundle/Resources/public/js/** package/*/src/*/Bundle/*Bundle/Tests/JS/** package/*/Resources/public/js/** --config=tool/.jscsrc; 
             tool/node_modules/.bin/jshint package/*/src/*/Bundle/*Bundle/Resources/public/js/** package/*/src/*/Bundle/*Bundle/Tests/JS/** package/*/Resources/public/js/** --config=tool/.jshintrc --exclude-path=tool/.jshintignore;
          fi
    ;;
esac
