Medusa is a command line tool that works together with Satis to create a local
git mirror for your composer projects.

**What the hell???**

# What is Medusa, what is it good for?

If you have a very slow connection, fetching your project's dependencies through
[composer](http://getcomposer.org) can be a pain. My projects were taking more
than half a day to update or install on my local machines because of slow networks.

Medusa will create a mirror of all these things on your local machine and let you
fetch everything from there rather than fetching the whole source from Github. Each
dependency is entirely mirrored, meaning you'll have all versions, tags, and branches
on your local machine.

# Limitations

It will only work with github hosted projects for now.

It has very poor documentation

It is a very early release, there might be bugs, and the API to use it is
definitely confusing.

# How to use

For now, you can do the following:

* Download the .phar archive from the downloads section
* Download the .phar archive for SATIS
* Put them both in a folder on your machine
* Inside of that folder, create a `web/` and a `repositories/` folder
* Create a `medusa.json` file that looks like this:

    {
        "require": [
            "vendor/package",
            "othervendor/otherpackage",
            //... List all the packages you want here, there dependencies can be
            // auto downloaded as well
        ],
        "repodir":"repositories",
        "satisconfig":"satis.json"
    }

* Create a satis config file skeleton like this:

    {
        "name": "My Repository",
        "homepage": "http://packages.example.org",
        "repositories": [
        ],
        "require-all": true // if you want to also mirror the dependencies from each package
    }

* run `./medusa.phar medusa.json`
* wait a long time

During this time, medusa will first find all the dependencies you need. Then run
`git clone --mirror` for each of them to create a mirror inside of the specified
repodir. And finally update your satis.json file with your new config.

* Run the satis build command: `./satis.phar build satis.json web/`
* Once a day run:

    ./medusa.phar update repos
    ./satis.phar build satis.json web/

To update all repos and rebuild the satis config.

# Other available commands:

`add [--config-file] [--with-deps] package [repos-dir]`

* `--config-file` is the satis config file (so it can be updated)
* `--with-deps` set to true or false to decide if you want to also mirror the new
package's dependencies
* `package` is the package name you want to mirror (eg: symfony/symfony)
* `repos-dir` in our case would be `repositories`

# Make composer use it

Point a webserver to the `web/` directory.

In your composer global config file add:

    {
        "repositories": [
            { "type": "composer", "url": "http://localsatis.url"}
        ]
    }
