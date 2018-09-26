# Deployer

## Subversion checkout

http://svnbook.red-bean.com/en/1.7/svn.ref.svn.c.checkout.html

```sh
svn co https://plugins.svn.wordpress.org/pronamic-ideal svn/pronamic-ideal
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

## To Subversion

```sh
rsync --recursive --delete ./build/pronamic-ideal/ ./svn/pronamic-ideal/trunk/

svn status ./deploy/wp-svn/trunk/ | grep '^!' | cut -c 9- | xargs -d '\n' -i svn delete {}@

svn status ./deploy/wp-svn/trunk/ | grep '^?' | cut -c 9- | xargs -d '\n' -i svn add {}@

svn commit ./deploy/wp-svn/trunk/ -m 'Update'
```
