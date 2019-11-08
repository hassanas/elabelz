#!/bin/bash
# Deployment shell script for Elabelz by Adnan for dev branch

cat << "EOF"

            ,--,                                                   
          ,--.'|                                                   
       ,--,  | :                                 ,---,             
    ,---.'|  : '    ,---.            .---.     ,---.'|             
    |   | : _' |   '   ,'\          /. ./|     |   | :             
    :   : |.'  |  /   /   |      .-'-. ' |     |   | |       .--,  
    |   ' '  ; : .   ; ,. :     /___/ \: |   ,--.__| |     /_ ./|  
    '   |  .'. | '   | |: :  .-'.. '   ' .  /   ,'   |  , ' , ' :  
    |   | :  | ' '   | .; : /___/ \:     ' .   '  /  | /___/ \: |  
    '   : |  : ; |   :    | .   \  ' .\    '   ; |:  |  .  \  ' |  
    |   | '  ,/   \   \  /   \   \   ' \ | |   | '/  '   \  ;   :  
    ;   : ;--'     `----'     \   \  |--"  |   :    :|    \  \  ;  
    |   ,/                     \   \ |      \   \  /       :  \  \ 
    '---'                       '---"        `----'         \  ' ; 
                                                             `--`  
EOF
    echo "
_____________________________________________________________________________

    Make sure that you know what you are doing, in case of any confusion 
    please contact your team lead. Thank you!

    R.E.A.D  C.A.R.E.F.U.L.L.Y

    1 - Taking pull on dev
    2 - Taking pull on master
    3 - Push and merge your changes to dev
    4 - Push and merge your changes to master
    5 - Creating new task
    6 - Merge your task
_____________________________________________________________________________
    "
read -r -p " Type action number and press enter > " response

if [ "$response" == "1" ]; then
    sh pull-dev.sh
elif [ "$response" == "2" ]; then
    sh pull-master.sh
elif [ "$response" == "3" ]; then
    sh push-dev.sh
elif [ "$response" == "4" ]; then
    sh push-master.sh
elif [ "$response" == "5" ]; then
    sh task-new.sh
elif [ "$response" == "6" ]; then
    sh task-merge.sh
else
    echo "Invalid action number, aborting ..."
fi
