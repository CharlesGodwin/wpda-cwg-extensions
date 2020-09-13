# 
# This script will build an plugin install zip file
# 
pushd ../
rm -f wpda-cwg-extensions/wpda-cwg-extensions.zip
zip wpda-cwg-extensions/wpda-cwg-extensions.zip wpda-cwg-extensions/* -i @wpda-cwg-extensions/includefiles.txt
popd
