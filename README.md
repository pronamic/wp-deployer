<h1 align="center">Pronamic WordPress Deployer</h3>

<p align="center">
	Console application to <strong>build</strong> and <strong>deploy</strong> WordPress solutions to the <strong>WordPress.org repository</strong> and <strong>AWS S3</strong>.
</p>

## Table of contents

- [Commands](#commands)
- [Examples](#examples)
- [Alternatives](#alternatives)
- [Links](#links)

## Commands

### `changelog`

Show the changelong entry of a specific version from `CHANGELOG.md`.

```sh
bin/wp-deployer changelog 1.0.0
```

### `svn-release`

Release a directory to a Subversion repository.

```sh
bin/wp-deployer svn-release build/plugin build/svn https://plugins.svn.wordpress.org/pronamic-pay-with-mollie-for-gravity-forms 1.0.0
```

### `wp-org-release`

Release a directory to the WordPress Plugin Directory.

```sh
bin/wp-deployer wp-org-release build/plugin build/svn pronamic-pay-with-mollie-for-gravity-forms
```

## Examples

```sh
bin/wp-deployer deploy easycruit https://gitlab.com/pronamic-plugins/easycruit.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-client https://github.com/pronamic/wp-pronamic-client.git --to-s3 --to-wp-org -vvv
bin/wp-deployer deploy pronamic-events https://github.com/pronamic/wp-pronamic-events.git --to-s3 --to-wp-org -vvv
bin/wp-deployer deploy pronamic-ideal https://github.com/pronamic/wp-pronamic-ideal.git --to-s3 --to-wp-org -vvv
bin/wp-deployer deploy pronamic-ideal https://github.com/pronamic/wp-pronamic-ideal.git --branch=develop --to-s3 -vvv
bin/wp-deployer deploy woocommerce-bulk-product-variations https://gitlab.com/pronamic-plugins/woocommerce-bulk-product-variations.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-adyen https://github.com/wp-pay-gateways/adyen.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-adyen https://github.com/wp-pay-gateways/adyen.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-contact-form-7 https://github.com/pronamic/wp-pronamic-pay-contact-form-7.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-contact-form-7 https://github.com/pronamic/wp-pronamic-pay-contact-form-7.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-digiwallet https://github.com/pronamic/wp-pronamic-pay-digiwallet.git --branch=main --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-fundraising https://github.com/pronamic/wp-pronamic-pay-fundraising.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-paypal https://github.com/pronamic/wp-pronamic-pay-paypal.git --to-s3 -vvv
bin/wp-deployer deploy pronamic-pay-payvision https://github.com/pronamic/wp-pronamic-pay-payvision.git --branch=main --to-s3 -vvv
```

## Alternatives

Here is a list of alternatives that we found. However, none of these satisfied our requirements.

*If you know other similar projects, feel free to edit this section!*

- [GitHub Actions for WordPress!](https://github.com/10up/actions-wordpress) by [10up](https://github.com/10up)
- [WordPress Plugin Directory Deployment Script](https://github.com/GaryJones/wordpress-plugin-svn-deploy) by [Gary Jones](https://github.com/GaryJones)
- [WordPress Plugin SVN Deploy](https://github.com/nk-o/action-wordpress-plugin-deploy) by [Nikita](https://github.com/nk-o)
- [sudar/wp-plugin-in-github](https://github.com/sudar/wp-plugin-in-github) by [Sudar Muthu](https://github.com/sudar)

## Subversion checkout

http://svnbook.red-bean.com/en/1.7/svn.ref.svn.c.checkout.html

```sh
svn co https://plugins.svn.wordpress.org/pronamic-ideal svn/pronamic-ideal --depth immediates

svn update --quiet svn/pronamic-ideal/trunk --set-depth infinity

svn update --quiet svn/pronamic-ideal/assets --set-depth infinity
```

## Git checkout

https://www.git-scm.com/docs/git-clone

```sh
git clone https://github.com/pronamic/wp-pronamic-ideal.git git/pronamic-ideal
```

## Update

```sh
cd svn/pronamic-ideal

svn update

cd ../../
```

```sh
cd git/pronamic-ideal

git pull

cd ../../
```

## Checkout

```sh
cd git/pronamic-ideal

git checkout tags/5.4.1

composer install --no-dev --prefer-dist

cd ../../
```

## Build

```sh
rm -r build/pronamic-ideal

mkdir build/pronamic-ideal

rsync --recursive --delete --exclude-from=exclude.txt ./git/pronamic-ideal/ ./build/pronamic-ideal/
```

## Create ZIP

```sh
mkdir zip/pronamic-ideal

zip -r ./zip/pronamic-ideal/pronamic-ideal.5.4.1.zip ./build/pronamic-ideal/*
```

## Create tar.gz

```sh
mkdir tar/pronamic-ideal

tar -zcvf ./tar/pronamic-ideal/pronamic-ideal.5.4.1.tar.gz ./build/pronamic-ideal/*
```

## To Subversion

```sh
rsync --recursive --delete ./build/pronamic-ideal/ ./svn/pronamic-ideal/trunk/

svn status ./svn/pronamic-ideal/trunk/ | grep '^!' | cut -c 9- | xargs -d '\n' -i svn delete {}@

svn status ./svn/pronamic-ideal/trunk/ | grep '^?' | cut -c 9- | xargs -d '\n' -i svn add {}@

svn commit ./svn/pronamic-ideal/trunk/ -m 'Update'
```

## Tag Subversion

```sh
svn copy ./svn/pronamic-ideal/trunk/ ./svn/pronamic-ideal/tags/4.5.1/

svn commit ./svn/pronamic-ideal/tags/4.5.1/ -m 'Tagging version 4.5.1'
```

## Requirements

```sh
# GNU tools on Mac.
# https://www.topbug.net/blog/2013/04/14/install-and-use-gnu-command-line-tools-in-mac-os-x/

# GNU `cat`
brew install coreutils

# GNU `xargs`:
# https://superuser.com/questions/467176/replacement-for-xargs-d-in-osx
brew install findutils --with-default-names

# GNU `grep`:
# https://apple.stackexchange.com/questions/193288/how-to-install-and-use-gnu-grep-in-osx
brew install grep --with-default-names

# AWS Command Line Interface
# https://aws.amazon.com/cli/
brew install awscli
```

## `svn status` and `xargs`


### `svn status`, `xargs` and `svn delete`

```sh
svn status | grep '^!' | cut -c 9- | xargs -d '\n' -i svn delete {}@
# ^^^^^^^^   ^^^^^^^^^   ^^^^^^^^^   ^^^^^^^^^^^^^^^^ ^^^^^^^^^^^^^^
#   |            |           |             |               |       |
#   |            |           |             |               |       \\ https://stackoverflow.com/questions/757435/how-to-escape-characters-in-subversion-managed-file-names
#   |            |           |             |               |       \\ http://svnbook.red-bean.com/en/1.7/svn.advanced.pegrevs.html
#   |            |           |             |               |
#   |            |           |             |               \\ Subversion delete.
#   |            |           |             |
#   |            |           |             \\ Xargs.
#   |            |           |
#   |            |           \\ Cut.
#   |            |           \\ https://github.com/apache/subversion/blob/1.10.2/subversion/svn/status.c#L447-L460
#   |            |
#   |            \\ Grep.
#   |
#   \\ Subversion status.
```

### `svn status`, `xargs` and `svn add`

```sh
svn status | grep '^?' | cut -c 9- | xargs -d '\n' -i svn add {}@
# ^^^^^^^^   ^^^^^^^^^   ^^^^^^^^^   ^^^^^^^^^^^^^^^^ ^^^^^^^^^^^
#   |            |           |             |               |    |
#   |            |           |             |               |    \\ https://stackoverflow.com/questions/757435/how-to-escape-characters-in-subversion-managed-file-names
#   |            |           |             |               |    \\ http://svnbook.red-bean.com/en/1.7/svn.advanced.pegrevs.html
#   |            |           |             |               |
#   |            |           |             |               \\ Subversion add.
#   |            |           |             |
#   |            |           |             \\ Xargs.
#   |            |           |
#   |            |           \\ Cut.
#   |            |           \\ https://github.com/apache/subversion/blob/1.10.2/subversion/svn/status.c#L447-L460
#   |            |
#   |            \\ Grep.
#   |
#   \\ Subversion status.
```

## WordPress.org assets

- https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
- https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- https://github.com/woocommerce/woocommerce/tree/3.4.5/.wordpress-org
- https://github.com/woocommerce/woocommerce-gateway-stripe/tree/4.1.10/wordpress_org_assets
- https://github.com/sudar/email-log/tree/2.2.5/assets-wp-repo

## Links

- https://www.topbug.net/blog/2013/04/14/install-and-use-gnu-command-line-tools-in-mac-os-x/
- https://superuser.com/questions/467176/replacement-for-xargs-d-in-osx
- https://apple.stackexchange.com/questions/193288/how-to-install-and-use-gnu-grep-in-osx
- https://github.com/stephenharris/grunt-wp-deploy
- https://github.com/GaryJones/wordpress-plugin-svn-deploy
- https://github.com/sudar/wp-plugin-in-github
- https://stackoverflow.com/questions/16991428/bash-how-to-put-each-line-within-quotation
- https://coderwall.com/p/tjekrq/subversion-shallow-checkout
- https://stackoverflow.com/questions/4709912/how-to-make-grep-only-match-if-the-entire-line-matches
- https://superuser.com/questions/294850/check-if-a-file-is-already-committed-to-svn
- https://askubuntu.com/questions/29370/how-to-check-if-a-command-succeeded
- https://linux.die.net/man/1/zip
- https://symfony.com/doc/current/components/process.html
- https://symfony.com/doc/current/components/console/helpers/processhelper.html
- https://symfonycasts.com/blog/fun-with-symfonys-console
- https://docs.aws.amazon.com/cli/latest/reference/s3/cp.html
