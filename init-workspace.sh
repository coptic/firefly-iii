gcloud auth login
gcloud config set project builder-budget-app
gcloud auth activate-service-account deployer@builder-budget-app.iam.gserviceaccount.com --key-file=deployer-key-file
gcloud auth configure-docker

sudo apt-get update
sudo apt-get install kubectl
sudo apt-get install google-cloud-sdk-gke-gcloud-auth-plugin

gke-gcloud-auth-plugin --version


gcloud container clusters get-credentials budget-app-cluster \
    --region=us-central1-c

kubectl expose deployment firefly-iii --name the-captain -n finance-manager \
    --type LoadBalancer --port 80 --target-port 8080

kubectl expose deployment firefly-iii-importer --name external-firefly-importer-service -n finance-manager \
    --type LoadBalancer --port 80 --target-port 8080