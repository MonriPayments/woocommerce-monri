#!/usr/bin/env bash
set -e

MESSAGE=$1
BASEDIR=$(dirname $(dirname "$0"))
VERSION=$(cat version.php)

if [ ! -d "release/monri-payments" ]; then
    echo "Missing release"
    exit 1
fi

if [ -z "$VERSION" ]; then
    echo "Missing version"
    exit 1
fi

if [ -z "$MESSAGE" ]; then
    echo "Missing release message"
    exit 1
fi

rm -rf release/woocommerce

svn co https://plugins.svn.wordpress.org/monri-payments/ release/woocommerce
if [ -d "release/svn/tags/$VERSION" ]; then
    echo "ERROR: Tag $VERSION already released."
    exit 1
fi

echo "Cloned a fresh copy of SVN repo"

rm -rf release/woocommerce/trunk
cp -Rf release/monri-payments release/woocommerce/trunk

echo "Copied release to trunk"

cd release/woocommerce
svn cp trunk tags/$VERSION

echo "Made tag $VERSION"

svn add trunk/* --force
svn add tags/$VERSION/* --force

TO_DELETE=$(svn status | grep '^!' | awk '{print $2}' | tr '\n' ' ')
echo $TO_DELETE
if [ "$TO_DELETE" != "" ]; then
    svn delete $TO_DELETE
fi

echo "STATUS:"
svn status --show-updates
read -p "Would you like to commit the changes above? (yes/No) " yn

case $yn in
    yes ) echo "Continuing with the release...";
        svn ci --username monripayments -m "version $VERSION ($MESSAGE)";;
    * ) echo "Stopping the release...";
        exit 1;;
esac
