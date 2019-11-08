#!/bin/bash
# Deployment shell script for Elabelz by Imran
echo "Backing up local.xml, .htaccess and .gitignore in ../elsbk/"

mkdir ../elsbk
mv app/etc/local.xml ../elsbk/
mv .htaccess ../elsbk/
mv .gitignore ../elsbk/

git pull

echo "Restoring local.xml, .htaccess and .gitignore"

cp ../elsbk/local.xml app/etc/
cp ../elsbk/.htaccess ./
cp ../elsbk/.gitignore ./
