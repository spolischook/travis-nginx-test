# Oro Monolithic Development Repository

Oro, Inc. team works on multiple different initiatives, products, projects and applications. Often changes may have
global impact (affect all applications for example) so code organization should allow to do them in efficient way.

Monolithic repository is used by product development team and contains all supported applications code.


## Repository Structure

Monolithic repository created based individual package and application repositories and divide code into two groups: 

- application - all application repositories with dependency on packages that are handled with 
[path](https://getcomposer.org/doc/05-repositories.md#path) repository type from composer
- package - functional packages that are used to build certain applications

## Development Experience

Development flow is not different from any composer based application and consists of the following steps:

* Clone repository
* Install tools
  * go to the `tool` folder
  * run `composer install`
* Install application(s)
  * go to the application folder, for example `cd application/crm-enterprise`
  * run `composer install`
  * go back to the root folder, for example `cd ../..`

  NOTE: symlinks for path repository not supported on Windows environment. Please remove packages copy from vendor
folder and use `mklink` command instead. For example, to enable platform package in platform application after 
`composer install` run following commands in the administrator mode:
```
rd /s /q "application/platform/vendor/oro/platform"
mklink /J "./application/platform/vendor/oro/platform" "./package/platform"
```
* Enable PHPStorm configuration for application you will be working on with: 
 ```
 php tool/console phpstorm:init-application {application_name}
 ```
* Create feature branch
* Do code changes
* Push code to remote repository and create a pull request

### IDE

PHPStorm is the recommended IDE for Oro projects. Following plugins will help to improve developer experience:

* Symfony2 - allows to simplify code navigation within an application
* Markdown - helps with Markdown (*.md) files

## Maintenance

This repository created based on individual repositories with 
[git-subtree](https://github.com/git/git/blob/master/contrib/subtree/git-subtree.txt) capabilities. 
Maintenance cycle includes a few typical tasks:

### Adding new subtree

If you would like to add new code from existing repository, you should run following command:

```
git subtree add --prefix={{code/folder}} {{code repository}} {{branch}}
```

- {{code/folder}} - destination folder where code will be added. In you adding an application, you'll typically use
 `application/{application name}` and `package/{package name}` for packages.
- {{code repository}} - URL of the original code repository
- {{branch}} - code branch or revision, typically you will use `master`

### Merge changes from the original repository

In order to update subtree with code from original repository you will need to run following commands:

```
git remote add -f {{origin-name}} {{code repository}}
git subtree add --prefix={{code/folder}} {{origin-name}} {{branch}} 
git fetch {{origin-name}} {{branch}}
git subtree pull --prefix={{code/folder}} {{origin-name}} {{branch}}
```

### Merge changes to the original repository

```
git remote add -f {{origin-name}} {{code repository}}
git subtree push --prefix={{code/folder}} {{origin-name}} {{branch}}
```
