cd /workspaces/javascript-node/BuilderBudgetBackend
head=`git rev-parse HEAD`

version=${head:0:6}
echo $version

docker build . -t ggcr.io/builder-budget-app/finance-manager:$version
docker push gcr.io/builder-budget-app/finance-manager:$version

kubectl delete deployment/firefly-iii
envsubst < GKE/deployment.yaml | kubectl apply -f -