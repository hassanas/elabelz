#!/bin/bash
# Deployment shell script for Elabelz by Adnan for dev branch

branch="$(git symbolic-ref HEAD 2>/dev/null)"
branch=${branch##refs/heads/}

if [ "$branch" == "dev" ] || [ "$branch" == "DEV" ]; then
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

    echo "Cleaning files that must be ignored."
    git clean -fx "app/etc/local.xml"
    git clean -fx ".gitignore"
    git clean -fx ".htaccess"
    git clean -fx "var/session/*"
    git clean -fx "var/cache/*"
    git clean -fx ".idea/*"
    git clean -fx "misc/"
    git clean -fx "var/*"
    git clean -fx "media/*"
    echo "-----LIST OF THE FILES TO BE PUSHED----"
    git status
    echo "Do you want to push these files? (y/n)"
    read pushYesNo

    if [ "$pushYesNo" == "y" ] || [ "$pushYesNo" == "Y" ]; then
        git add --all .
        echo "Please enter the comment for the task:"
        read comment
        md5=`echo -n "$comment" | md5sum | awk '{print $1}'`
        git commit -m "$comment > via push-dev with checksum: $md5"
        pull="$(git pull origin dev)"
        if [ [ "$origins" =~ "conflict" ] || [ "$origins" =~ "CONFLICT" ] ]; then
            echo "There are conflicts that needs to be resolved. Afer resolving run this script again."
            echo "Aborting..."
        else
            git push origin dev
        fi
    else
        echo "Its OK, aborting ..."
    fi
else
    echo "
_____________________________________________________________________________
      
    ERROR!
    You are on wrong branch <$branch>, please switch to dev branch and 
    try again, bye!
_____________________________________________________________________________
    "
fi

echo "< Restoring files ..."
cp ../els_dev_bk/local.xml app/etc/
cp ../els_dev_bk/.htaccess ./
cp ../els_dev_bk/.gitignore ./

if [ -d ".idea" -a -d "../els_dev_bk/.idea" ]; then
    if [ "$(ls -A ./.idea)" -a "$(ls -A ../els_dev_bk/.idea)" ]; then
        cp -rf ../els_dev_bk/.idea ./ 
    fi
fi