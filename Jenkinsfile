@Library('jenkins-shared-libs')
import org.healthmap.ProjectConfig;
def _APP_NAME= "epicore"
def _ENV_NAME = ""
if (params.AppEnv=="PROD") {
 _ENV_NAME = "prod"
}
else{
_ENV_NAME = "nonprod"
}

def _PROJECT_KEY= _APP_NAME+"-"+_ENV_NAME
def PROJECT_MAP = ProjectConfig.projectMap[_PROJECT_KEY]
def  _JENKINS_IAM_ROLE=PROJECT_MAP.JENKINS_IAM_ROLE
def  _AWS_ACCOUNT_ID=PROJECT_MAP.AWS_ACCOUNT_ID
def  _DOCKER_REGISTRY_URL= PROJECT_MAP.DOCKER_REGISTRY_URL
def  _DOCKER_REGISTRY_CRED_ID= PROJECT_MAP.DOCKER_REGISTRY_CRED_ID
def  _DOCKER_REPO = PROJECT_MAP.DOCKER_REPO
def  _DOCKER_IMAGE_NAME="epicore-app"
def  _K8S_NAMESPACE=PROJECT_MAP.EKS_APP_NAMESPACE
def  _EKS_CLUSTER_NAME= PROJECT_MAP.EKS_CLUSTER_NAME
def _AWS_REGION=PROJECT_MAP.AWS_REGION 
 


pipeline { 
    
    
      environment {
      
          
         JENKINS_IAM_ROLE = sh(returnStdout: true, script: "echo ${_JENKINS_IAM_ROLE}").trim()
         AWS_ACCOUNT_ID  = sh(returnStdout: true, script: "echo ${_AWS_ACCOUNT_ID}").trim()
         APP_NAME = sh(returnStdout: true, script: "echo ${_APP_NAME}").trim()
         ENV_NAME = sh(returnStdout: true, script: "echo ${_ENV_NAME}").trim()
         AWS_REGION = sh(returnStdout: true, script: "echo ${_AWS_REGION}").trim()  
         DOCKER_REGISTRY_URL =sh(returnStdout: true, script: "echo ${_DOCKER_REGISTRY_URL}").trim() 
         DOCKER_REGISTRY_CRED_ID =sh(returnStdout: true, script: "echo ${_DOCKER_REGISTRY_CRED_ID}").trim() 
         DOCKER_REPO =sh(returnStdout: true, script: "echo ${_DOCKER_REPO}").trim() 
         K8S_NAMESPACE = sh(returnStdout: true, script: "echo ${_K8S_NAMESPACE}").trim() 
         K8S_CLUSTER_NAME = sh(returnStdout: true, script: "echo ${_EKS_CLUSTER_NAME}").trim() 
         DOCKER_IMAGE_VERSION = ''  
         DOCKER_IMAGE_NAME = sh(returnStdout: true, script: "echo ${_DOCKER_IMAGE_NAME}").trim()  
         PATH= sh(returnStdout: true, script: "echo $PATH:/usr/local/bin:").trim() 
           
    }
    
    agent any 
    parameters {
     choice(name: 'AppEnv', choices: ['DEV','PROD'], description: 'Choose an Environment to deploy')
     }
    options {
        skipStagesAfterUnstable()
         timestamps()
    }
    stages {

                stage("Approve") {
               when {
                  environment name: 'ENV_NAME', value: 'prod'
               }
              options {
                  timeout(time: 30, unit: "MINUTES")
              }
               steps {
                     
                     script {
                   
                           userInput = input(
                              id: 'Proceed1', message: 'Do you want to deploy in PROD?', parameters: [
                              [$class: 'BooleanParameterDefinition', defaultValue: true, description: '', name: 'Please confirm']
                              ])
                  
                          if(userInput == false) {

                            skipRemainingStages = true
                                echo "do not proceed  skip ${skipRemainingStages}"
                                currentBuild.result = 'ABORTED'
                            }  
                            else {
                                skipRemainingStages = false
                                echo "proceed ${userInput} "
                            }
                       }        
              }
         }

      stage("Prepare") {
        steps {
          script {
       
            def gitCommitVersion=getGitCommitHash()
            def gitBranchName = getCurrentBranch()
            DOCKER_IMAGE_VERSION  = gitCommitVersion
            HELM_CHART_NAME = env.APP_NAME+"-"+gitBranchName
            EKS_CONTEXT=env.ENV_NAME+"-"+env.APP_NAME
              
          }
        }
      }

      stage('Build Image') { 
            steps { 

              script {
               
                      withAWS(region: env.AWS_REGION ,role: env.JENKINS_IAM_ROLE, roleAccount: env.AWS_ACCOUNT_ID) {

                         sh """
                         npm install 
                         npm run-script build
                         aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
                         cat ssm_parameters.txt | jq -r '.[] |  map(\"\(.Name)=\(.Value|tostring)\")'  |  sed 's/"//g' | sed 's/.$//' > .env   
                         rm -rf ssm_parameters.txt
                         """
                          docker.withRegistry( env.DOCKER_REGISTRY_URL, env.DOCKER_REGISTRY_CRED_ID) {
              
                                 
                                targetDockerImage = docker.build("${DOCKER_REPO}/${DOCKER_IMAGE_NAME}:${DOCKER_IMAGE_VERSION}")

                            }

                        }
              }
            }
         
          }

          stage('Tag-Push Image') { 
            steps { 

              script {
               
                      withAWS(region: env.AWS_REGION ,role: env.JENKINS_IAM_ROLE, roleAccount: env.AWS_ACCOUNT_ID) {
                              
                          docker.withRegistry( env.DOCKER_REGISTRY_URL, env.DOCKER_REGISTRY_CRED_ID) {
              
                                 targetDockerImage.push(DOCKER_IMAGE_VERSION)

                            }

                        }
              }
            }
         
          }


          stage('Deploy Image') { 
            steps { 

              script {
                   
                        withAWS(region: env.AWS_REGION ,role: env.JENKINS_IAM_ROLE, roleAccount: env.AWS_ACCOUNT_ID) {
                              
                         sh(script: "aws eks update-kubeconfig --region ${AWS_REGION} --name ${K8S_CLUSTER_NAME}  --alias ${EKS_CONTEXT}",returnStdout: true)
                         sh(script: "cp ./deploy/helm-chart/values-${ENV_NAME}.yaml ./deploy/helm-chart/values.yaml",returnStdout: false)
      
                             helm.upgrade(
                              bin:       '/usr/local/bin/helm', 
                              chart:     './deploy/helm-chart/', 
                              context:   EKS_CONTEXT, 
                              install:   true,
                              name:      HELM_CHART_NAME,
                              namespace: env.K8S_NAMESPACE,
                              values:    ['./deploy/helm-chart/values.yaml'],
                              set:       ['image.tag':DOCKER_IMAGE_VERSION]
                            )


              }
              
                        }
                      
    

           
         
            }
          }

 }
 
 }

 
 