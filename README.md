# Oro Monolithic Development Repository

Oro, Inc. team works on multiple different initiatives, products, projects and applications. Often changes may have
global impact (affect all applications for example) so code organization should allow to do them in efficient way.

Monolithic repository is used by product development team and contains all supported applications code.

[![Build Status](https://travis-ci.com/laboro/dev.svg?token=xpj6qKNzq4qGqYEzx4Vm&branch=master)](https://travis-ci.com/laboro/dev)

## Repository Structure

Monolithic repository created based individual package and application repositories and divide code into two groups: 

- application - all application repositories with dependency on packages that are handled with 
[path](https://getcomposer.org/doc/05-repositories.md#path) repository type from composer
- documentation - all products documentation
- package - functional packages that are used to build certain applications
- tool - CLI and other tools necessary for repository and code maintenance 

## Installation and Initialization

* [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) globally 
* Clone repository to the local environment:
```bash
git clone git@github.com:laboro/dev.git
```
* Go to the root folder:
```bash
cd dev
```
* Install tools in `tool` folder:
```bash
cd tool && composer install && cd ..
```
* Install all dependencies for the application(s) you are going to work on (crm application used as example):
```bash
cd application/crm && composer install && cd ../..
```
* Install application via web or command line interface

## Development Experience

* Enable PHPStorm configuration for application you will be working on with:
```bash
php tool/console phpstorm:init-application {application_name}
```
* Create feature branch
* Do code changes
* Push branch to remote repository and create a pull request

### IDE

PHPStorm is the recommended IDE for Oro projects. Following plugins will help to improve developer experience:

* Symfony2 - allows to simplify code navigation within an application
* Markdown - helps with Markdown (*.md) files

## Maintenance

This repository created based on individual repositories with 
[git-subtree](https://github.com/git/git/blob/master/contrib/subtree/git-subtree.txt) capabilities. 
Maintenance cycle includes a few typical tasks:

### Adding new subtree

If you would like to add new code from existing upstream repository, you should add new record to `$repositories` in
[Oro\Cli\Command\Repository\Sync](./tool/src/Oro/Cli/Command/Repository/Sync.php) class and run following command:

```bash
php tool/console repository:sync REPO_NAME
```

### Syncronize subtree and upstream repository changes

In order to update subtree with code from original repository you will need to run following commands:

```bash
php tool/console repository:sync
```

In order to synchronize subtree and upstream repository you will need to run following command:

```bash
php tool/console repository:sync --two-way
```

*Note:* please pay attention to command output, if conflict will occur during subtree merge you'll need to resolve it
and run command again. If you notice **Working tree has modifications.  Cannot add.** in the output, it indicates that
you either have local changes that should be committed or conflict occurred during merge and it should be resolved.
