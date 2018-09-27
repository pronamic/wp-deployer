#!/bin/bash

# Pronamic Deployer
# https://github.com/pronamic/deployer

while [ $# -gt 0 ]
do
    case "$1" in
        -s)  SLUG="$2"; MAIN_FILE="$SLUG.php"; shift;;
        -g)  GIT_URL="$2"; shift;;
        -m)  MAIN_FILE="$2"; shift;;
        -*)
            echo >&2 \
            "usage: $0 [-p plugin-name] [-u svn-username] [-m main-plugin-file] [-a assets-dir-name] [-t tmp directory] [-i path/to/i18n] [-h history/changelog file]"
            exit 1;;
        *)  break;; # terminate while loop
    esac
    shift
done

# General
DEPLOYER_DIR=$(pwd)

# Subversion

SVN_URL="https://plugins.svn.wordpress.org/$SLUG"
SVN_PATH="svn/$SLUG"

# Git

GIT_PATH="git/$SLUG"

# Build
BUILD_PATH="build/$SLUG"

# Validate

if [ -z "$SLUG" ]; then
	echo "❌  Empty slug."

	exit 1;
fi

if [ -z "$GIT_URL" ]; then
	echo "❌  Empty Git URL."

	exit 1;
fi

if [ -z "$MAIN_FILE" ]; then
    echo "❌  Empty main file."

    exit 1;
fi

# Start

echo
echo '🚀 Pronamic Deployer v1.0.0'
echo 
echo "Deployer Directory: $DEPLOYER_DIR"
echo
echo "Subversion URL: $SVN_URL"
echo "Subversion path: $SVN_PATH"
echo
echo "Git URL: $GIT_URL"
echo "Git path: $GIT_PATH"
echo 
echo "Main File: $MAIN_FILE"
echo
echo "Build path: $BUILD_PATH"
echo

# Subversion checkout

if [ ! -d "$SVN_PATH" ]; then
	svn checkout $SVN_URL $SVN_PATH --depth immediates
fi

# Subversion update

echo
echo 'ℹ️  Subversion update'
echo

svn update $SVN_PATH/trunk --set-depth infinity

svn update $SVN_PATH/assets --set-depth infinity

# Git clone

if [ ! -d "$GIT_PATH" ]; then
	git clone $GIT_URL $GIT_PATH
fi

# Git pull

echo
echo "ℹ️  Git pull"
echo

cd $GIT_PATH

git pull

cd $DEPLOYER_DIR

echo

# Git checkout

echo
echo "ℹ️  Git checkout master"
echo

cd $GIT_PATH

git checkout master

git pull

cd $DEPLOYER_DIR

echo

# Version check

if [ ! -f "$GIT_PATH/$MAIN_FILE" ]; then
    echo "❌  Main file `$MAIN_FILE` not found."

    exit 1;
fi

MAIN_FILE_VERSION=$(grep -i "Version:" $GIT_PATH/$MAIN_FILE | awk -F ' ' '{print $NF}' | tr -d '\r')
README_TXT_VERSION=$(grep -i "Stable tag:" $GIT_PATH/readme.txt | awk -F' ' '{print $NF}' | tr -d '\r')
VERSION=$MAIN_FILE_VERSION

echo
echo "ℹ️  Version"
echo
echo "Main file version: $MAIN_FILE_VERSION"
echo "Readme.txt version: $README_TXT_VERSION"
echo

if [ "$MAIN_FILE_VERSION" != "$README_TXT_VERSION" ]; then
    echo "Version in readme.txt & $MAIN_FILE don't match. Exiting…"

    exit 1;
fi

# Composer

echo
echo "ℹ️  Composer"
echo

cd $GIT_PATH

composer install --no-dev --prefer-dist --optimize-autoloader

cd $DEPLOYER_DIR

# Build

echo
echo "ℹ️  Build"
echo

rm -r ./$BUILD_PATH

mkdir ./$BUILD_PATH

rsync --recursive --delete --exclude-from=exclude.txt --verbose ./$GIT_PATH/ ./$BUILD_PATH/

# Subversion

TAG_INFO=$(svn info $SVN_URL/tags/$VERSION 2> /dev/null)

if [ "$TAG_INFO" ]; then
    echo "❌  Tag $VERSION already exists on $SVN_URL/tags."
    echo

    svn info $SVN_URL/tags/$VERSION

    exit 1;
fi

# Update Subversion trunk
#
# https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#trunk
#
# "Even if you do your development work elsewhere (like a git repository), we recommend you keep the trunk folder up to date with your code for easy SVN compares."

echo
echo "ℹ️  Build » Subversion `trunk`"
echo

rsync --recursive --delete --verbose ./$BUILD_PATH/ ./$SVN_PATH/trunk/

echo
echo "ℹ️  Subversion delete"
echo

svn status ./$SVN_PATH/trunk/ | grep '^!' | cut -c 9- | xargs -d '\n' -i svn delete {}@

echo
echo "ℹ️  Subversion add"
echo

svn status ./$SVN_PATH/trunk/ | grep '^?' | cut -c 9- | xargs -d '\n' -i svn add {}@

echo
echo "ℹ️  Subversion commit"
echo

svn commit ./$SVN_PATH/trunk/ -m 'Update'

echo
echo "ℹ️  Subversion tag check"
echo

TAG_INFO=$(svn info $SVN_URL/tags/$VERSION)

echo $TAG_INFO

if [ TAG_INFO -eq 0 ]; then
    echo "❌  Tag $VERSION already exists on $SVN_URL/tags."
    echo
    echo $TAG_INFO

    exit 1;
fi

exit 1;

# Alternately, you can use http URLs to copy, and save yourself bandwidth:
#
# ```sh
# my-local-dir/$ svn cp https://plugins.svn.wordpress.org/your-plugin-name/trunk https://plugins.svn.wordpress.org/your-plugin-name/tags/2.0
# ```
#
# Doing that will perform the copy remotely instead of copying everything locally and uploading. This can be beneficial if your plugin is larger.

echo
echo "ℹ️  Subversion tag"
echo

svn cp $SVN_URL/trunk/ $SVN_URL/tags/$VERSION/ -m "Tagging version $VERSION for release."
