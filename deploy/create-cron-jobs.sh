
#!/bin/bash

AWS_ACCOUNT_ID="503172036736"
LATEST_IMG_TAG=$(aws ecr describe-images --repository-name epicore-app --query 'sort_by(imageDetails,& imagePushedAt)[-1].imageTags[0]' --output text | sort -r | head -n 1)

echo "Latest Image Tag is $LATEST_IMG_TAG"
while IFS=, read -r name script_name schedule; do
  # do something... Don't forget to skip the header line!
  

if [[ ${name::1} != "#" ]]; then
    echo "Generating job for $name"
    cat <<EOF > ./cron-jobs/$name.yml
    apiVersion: batch/v1beta1
    kind: CronJob
    metadata:
      name: ${name}
    spec:
      concurrencyPolicy: Allow
      jobTemplate:
        spec:
          template: 
            spec:
              containers:
              - command:
                - sh
                - -c
                - ./jobs.sh ${script_name}
                image: ${AWS_ACCOUNT_ID}.dkr.ecr.us-east-1.amazonaws.com/epicore-app:${LATEST_IMG_TAG}
                imagePullPolicy: IfNotPresent
                name:  ${name}
                resources:
                  limits:
                    cpu: 512m
                    memory: 1Gi
                terminationMessagePath: /dev/termination-log
                terminationMessagePolicy: File
                volumeMounts:
                  - mountPath: /var/www/html/data
                    name: persistent-storage
              restartPolicy: OnFailure
              schedulerName: default-scheduler
              securityContext: {}
              terminationGracePeriodSeconds: 
              volumes:
                - name: persistent-storage
                  persistentVolumeClaim:
                    claimName: efs-claim
      schedule: ${schedule}
    
EOF

    DEPLOY=$1

    if [[ ${name::1} != "#"  && "$DEPLOY" == "deploy" ]]; then
    
      echo "Checking if  Job $name already exists..."
    
      kubectl get cronjob  $name -n epicore

      retVal=$?
      if [ $retVal -ne 0 ]; then
          echo "Job does not exists. So deploy"
          kubectl apply -f ./cron-jobs/$name.yml -n epicore
        else
          image_name="${AWS_ACCOUNT_ID}.dkr.ecr.us-east-1.amazonaws.com/epicore-app:${LATEST_IMG_TAG}"
          echo "Job does exists. So deploy the job with latest image $image_name"
          kubectl delete cronjob $name -n epicore
          kubectl apply -f ./cron-jobs/$name.yml -n epicore

      fi
    

    fi
    
fi

done < jobs.txt