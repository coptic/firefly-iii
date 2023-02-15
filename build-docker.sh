head=`git rev-parse HEAD`

version=${head:0:6}
echo $version

docker build . -t gcr.io/builder-budget-app/finance-manager:$version
docker push gcr.io/builder-budget-app/finance-manager:$version

# kubectl delete deployment/deployment/finance-manager
# envsubst < .//deployment.yaml | kubectl apply -f -