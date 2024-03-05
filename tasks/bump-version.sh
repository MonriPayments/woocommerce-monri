#!/usr/bin/env bash

BASEDIR=$(dirname $(dirname "$0"))
VERSION=$(cat version.php)

sed -i "s/Version: .*/Version: $VERSION/" "$BASEDIR/monri.php"
sed -i "s/define( 'MONRI_WC_VERSION', '.*' );/define( 'MONRI_WC_VERSION', '$VERSION' );/" "$BASEDIR/monri.php"
sed -i "s/Project-Id-Version: Monri .*\\n/Project-Id-Version: Monri $VERSION\\n/" $BASEDIR/languages/monri*.po{,t}
sed -i "s/Stable tag: .*/Stable tag: $VERSION/" "$BASEDIR/readme.txt"
if ! command -v npm &>/dev/null
then
  echo "NPM is not installed, please bump package.json and package-lock.json manually."
  exit 0
fi

npm version $VERSION --force --allow-same-version --no-git-tag-version