#!/bin/bash
# Deployment shell script for Elabelz by Imran
rm -rf en-sa en-qa en-bh en-om en-lb en-kr en-kw ar-sa ar-qa ar-bh ar-om ar-lb ar-kr ar-kw ar-ae
mkdir en-sa en-qa en-bh en-om en-lb en-kr en-kw
mkdir ar-sa ar-qa ar-bh ar-om ar-lb ar-kr ar-kw ar-ae
#cp -r ./en-ae/index.php ./en-sa/ ./en-qa/ ./en-bh/ ./en-om/ ./en-lb/ ./en-kr/ ./en-kw/ ./ar-sa/ ./ar-qa/ ./ar-bh/ ./ar-om/ ./ar-lb/ ./ar-kr/ ./ar-kw/
for i in ./en-sa ./en-qa ./en-bh ./en-om ./en-lb ./en-kr ./en-kw ./ar-sa ./ar-qa ./ar-bh ./ar-om ./ar-lb ./ar-kr ./ar-kw ./ar-ae; do cp ./en-ae/index.php $i; done
sed -i -e 's/en_ae/en_sa/g' en-sa/index.php
sed -i -e 's/en_ae/en_qa/g' en-qa/index.php
sed -i -e 's/en_ae/en_bh/g' en-bh/index.php
sed -i -e 's/en_ae/en_om/g' en-om/index.php
sed -i -e 's/en_ae/en_lb/g' en-lb/index.php
sed -i -e 's/en_ae/en_kr/g' en-kr/index.php
sed -i -e 's/en_ae/en_kw/g' en-kw/index.php

sed -i -e 's/ar_ae/ar_sa/g' ar-sa/index.php
sed -i -e 's/ar_ae/ar_qa/g' ar-qa/index.php
sed -i -e 's/ar_ae/ar_bh/g' ar-bh/index.php
sed -i -e 's/ar_ae/ar_om/g' ar-om/index.php
sed -i -e 's/ar_ae/ar_lb/g' ar-lb/index.php
sed -i -e 's/ar_ae/ar_kr/g' ar-kr/index.php
sed -i -e 's/ar_ae/ar_kw/g' ar-kw/index.php
sed -i -e 's/ar_ae/ar_ae/g' ar-ae/index.php

chmod 755 en-*
chmod 755 ar-*

chmod 644 en-*/index.php       
chmod 644 ar-*/index.php
echo "Done with everything :)"
