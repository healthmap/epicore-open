#!/usr/bin/env sh
set -e

aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
cat ssm_parameters.txt | jq -r '.[] |  map("\(.Name)=\(.Value|tostring)")'  |  sed 's/"//g' | sed 's/.$//' > .env    
rm -rf ssm_parameters.txt

