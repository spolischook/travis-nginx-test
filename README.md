# Oro Monolithic Development Repository

Oro, Inc. team works on multiple different initiatives, products, projects and applications. Some changes may have
global impact (for example, a change may affect all products), so the source code organization should allow to perform them in an efficient way.

This monolithic repository is used by the product development team as the main source code repository ("upstream repository") for all development activities. It contains the source code of all products and applications. The code changes and the new code are frequently distributed to the invididual application repositories ("downstream repositories"). Occasionally, code changes are accepted back from the downstream repositories (e.g. pull requests from the community may first get into individual downstream repositories, and then they are accepted to this upstream monolithic repository).

[![Build Status](https://travis-ci.com/laboro/dev.svg?token=xpj6qKNzq4qGqYEzx4Vm&branch=master)](https://travis-ci.com/laboro/dev)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/72e37cec-75b7-4b2b-bc8a-72544beaa446/mini.png)](https://insight.sensiolabs.com/projects/72e37cec-75b7-4b2b-bc8a-72544beaa446)

## Repository Structure

The monolithic repository contains code of individual packages, applications, documentation and additional tools: 

- application - an application is a Symfony application that contains referenceres to all required package dependencies.
In order to avoid duplication of dependencies, they are handled with 
[path](https://getcomposer.org/doc/05-repositories.md#path) repository type in composer.json files.
- documentation - documentation for all products.
- package - a package is a group of related functional modules, that are used primarily together in a certain application.
- tool - various tools necessary for repository and code maintenance, and IDE integration.

## Installation and Initialization

* [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) globally 
* Clone repository to the local environment:
```bash
git clone git@github.com:laboro/dev.git
```
* Go to the cloned repository folder:
```bash
cd dev
```
* Install tools in `tool` folder:
```bash
cd tool && composer install && cd ..
```
* Install all dependencies for the application you are going to work on, for example:
```bash
cd application/crm && composer install && cd ../..
```
* Install application via web or command line interface
* Repeat the previous two steps (install application dependencies and run application installer) for as many applications as necessary.

## Development Experience

* Enable PHPStorm configuration for the application you are going to work on:
```bash
php tool/console phpstorm:init-application {application_name}
```
* Create a feature branch
* Perform code changes and testing
* Push your branch to the remote repository and create a pull request

*Note:* to see all existing applications run `phpstorm:init-application` without parameters:
```bash
php tool/console phpstorm:init-application
```

### IDE

PHPStorm is the recommended IDE for Oro projects. The following plugins may help to improve developer experience:

* Symfony2 - allows to simplify code navigation within an application
* Markdown - helps with Markdown (*.md) files editing and preview

## Maintenance

This repository is using [git-subtree](https://github.com/git/git/blob/master/contrib/subtree/git-subtree.txt)
to synchronize the source code with individual downstream repsoitories.

The maintenance cycle includes a few typical tasks:

### Add a new subtree

If you would like to add new downstream repository, you should add a new record to the `$repositories` in
[Oro\Cli\Command\Repository\Sync](./tool/src/Oro/Cli/Command/Repository/Sync.php) class and run the following command:

```bash
php tool/console repository:sync REPO_NAME
```

### Accept changes from an individual downstream repository into the monolithic

In order to update a subtree in the monolithic repository with the new code from an individual downstream repository,
run the following command:

```bash
php tool/console repository:sync
```

### Send changes from the monolithic into an individual downstream repository

In order to send new code from the monolithic repository into an individual downstream repository,
run the following command:

```bash
php tool/console repository:sync --two-way
```

*Note:* please pay attention to the output, produced by the repository:sync command output:
* If a conflict occurs during the subtree merge you must resolve it and run the command again.
* If you see **There are untracked files in the working tree, to continue please clean the working tree.
Use "git status" command to see details.** in the output, it indicates that you have local 
changes that should be committed before executing the command.

### Accept changes from a specific branch of the downstream repository

In order to update a subtree in the monolithic repository with the new code from a specific branch 
of an invidual downstream repository, run the following command:

```bash
php tool/console repository:branch-sync some-branch
```

*Note:* The specified branch will be created in an individual downstream repository if it doesn't exist there yet

### Send changes into a branch of an individual downstream repository

In order to send the new code from the monolithic repository into a specific branch of an individual downstream
repository, run the following command:

```bash
php tool/console repository:branch-sync --two-way
```

*Note:* The specified branch will be created in an individual downstream repository if it doesn't exist there yet

### Check branches accross multiple repositories

In order to get a list of the repositories where the specified branch exists, run the following command:

```bash
php tool/console repository:branch-sync --dry-run
```

*Note:* please pay attention to the output, produced by the repository:sync command output:
* If a conflict occurs during the subtree merge you must resolve it and run the command again.
* If you see **There are untracked files in the working tree, to continue please clean the working tree.
Use "git status" command to see details.** in the output, it indicates that you have local 
changes that should be committed before executing the command.
