#!/bin/bash
# Deployment shell script for Elabelz by Adnan for master branch

branch="$(git symbolic-ref HEAD 2>/dev/null)"
branch=${branch##refs/heads/}

if [ "$branch" == "master" ] || [ "$branch" == "master" ]; then
    echo "> Good! You are on $branch, performing actions."
    echo "..."
    echo "..."
    echo "> Backing up files in ../els_dev_bk/ ..."

    dir="els_dev_bk"
    if [ ! -d "../$dir" ]; then
        mkdir ../els_dev_bk
    fi

    if [ -d ".idea" ]; then
        if [ "$(ls -A ./.idea)" ]; then
            if [ ! -d "../els_dev_bk/.idea" ]; then
                mkdir ../els_dev_bk/.idea
            fi
            cp -rf ./.idea/ ../els_dev_bk/
        fi
    fi

    cp app/etc/local.xml ../els_dev_bk/
    cp .htaccess ../els_dev_bk/
    cp .gitignore ../els_dev_bk/

    echo "Do you want to pull $branch branch? (y/n)"
    read pushYesNo

    if [ "$pushYesNo" == "y" ] || [ "$pushYesNo" == "Y" ]; then
        git pull origin master

        echo "< Restoring files ..."
        cp ../els_dev_bk/local.xml app/etc/
        cp ../els_dev_bk/.htaccess ./
        cp ../els_dev_bk/.gitignore ./

        if [ -d ".idea" -a -d "../els_dev_bk/.idea" ]; then
            if [ "$(ls -A ./.idea)" -a "$(ls -A ../els_dev_bk/.idea)" ]; then
                cp -rf ../els_dev_bk/.idea ./ 
            fi
        fi
    else
        echo "< Restoring files ..."
        cp ../els_dev_bk/local.xml app/etc/
        cp ../els_dev_bk/.htaccess ./
        cp ../els_dev_bk/.gitignore ./
        echo "Aborting ..."
    fi
else
    echo "
_____________________________________________________________________________
      
    ERROR!
    You are on wrong branch <$branch>, please switch to master branch and 
    try again, bye!
_____________________________________________________________________________
    "
fi
