#!/bin/bash
# Deployment shell script for Elabelz by Imran

origins="$(git remote show)"

echo $origins

#check if the origin elastera is created
if [[ "$origins" =~ "elastera-live" ]]
then
    echo "Origin already created, below is the list of origins"
    git remote show
else
    echo "Creating origin elastera-live..."
    git remote add elastera-live elabelz-live@manager.elabelz.elastera.net:/git
fi
#delete cleanup branch
function delCleanupBranch {
    delMsg="$(git branch -D cleanup)"
    if [[ "$delMsg" =~ "error" ]]
    then
        echo "ERROR: You are currently on cleanup branch please switch back to master and run this script."
        echo "Forcefully changing your branch to master."
        git checkout -f master
	echo "Branch switched to master, now deleting cleanup."
        git branch -D cleanup
	echo "Cleanup deleted."
    fi
}

#delete the cleanup branch
delCleanupBranch
#Now deploy the elastera code
git checkout -b cleanup
git rm -r media var/backups var/export var/import var/report var/log var/tmp ae.elabelz.com deploy.sh deploy-live.sh app/etc/mojomigrate.sql.gz
git commit -m "Deploying code via automated script, the script was run by $USER"
git push --force elastera-live cleanup:master

echo "Script completed all tasks. Now chaning branch back to master."
git checkout -f master
git branch -D cleanup
