redcode-deploy is the easiest way to deploy your code to server
==============

Steps to start:
- just create a symlink of deploy.sh to folder with your project. Like: ln -s ../vendor/redcode/deploy/deploy.sh ./deploy.sh
- create deploy.json file in the same folder. Like in example:
{
    "version" : "vcs",
    "version-strategy" : "merged",
    "pack-type": "tar",
    "path": {
        "local": "~/local-path/",
        "server": "/var/www/some-server-path/"
    },
    "file": {
        "deploy": "",
        "ignore": ""
    },
    "environment" : {
        "dev" : {
            "host" : "",
            "path" : ""
        },
        "prod" : {
            "host" : ""
        }
    },
    "commands": {
        "before" : [
        ],
        "after" : [
        ]
    }
}
