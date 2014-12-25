Deploy script
========
This library provides simple way to deploy php-based application to different environments.

## Installation ##
- Add to you composer.json file the line:
```json
"redcode/deploy": "1.0.*",
```
- Update composer with command:
```shell
composer update redcode/deploy
```

## Configuration ##
After installation process you have to spend some time with configuration.
Application allow configurations files in yml and json format.

Add config.yml into your project folder:
```yml
package:
    include: "./app ./src ./web" # files which will be included to the package
    exclude: ".git" # files which will be excluded from the package
version: "vcs" # "vcs" - for getting from GIT, or any different for put as it
version-strategy: "merged" # (this option make sense only if version set to "vcs") set to "tag" for getting version from the nearest tag, set to "branch" for getting from branch. 
environment: # at least one environment must be set
    dev:
        name: "dev" # 
        host: "hostname" # hostname of the server
        path: "/var/www/website" # the location of the project on the server
    prod:
        name: "prod"
        host: "hostname2"
        path: "/var/www/website"
command:
    local: # commands for executing on a local machine 
        before: # before creating the package
            - "any command line to execute"
        after: # after creating the package
            - "any command line to execute"
    server: # commands for executing on a server
        before: # before build extracting 
            - "any command line to execute"
        after: # after build extracting
            - "any command line to execute"
```

## Usage ##

```shell
cd project/path
bin/deploy
```