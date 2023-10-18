#!/user/bin/bash

version=$1
file=block-catalog-$version.zip

cd ../
zip -q -r block-catalog/releases/$file block-catalog -x "*/.git/*" "*/bin/*" "*/vendor/*" "*/node_modules/*" "*/releases/*"
echo $file "Built"
