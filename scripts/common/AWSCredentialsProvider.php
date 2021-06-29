
<?php

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\InstanceProfileProvider;
use Aws\Credentials\AssumeRoleCredentialProvider;
use Aws\Sts\StsClient;

require_once 'const.inc.php';

//singleton class
class AWSCredentialsProvider
{
    // Hold the class instance.
    private static $instance = null;
    private $provider;
    
    // The aws creds grabbed from role - established in the private constructor.
    private function __construct()
    {
        // $profile = new InstanceProfileProvider();
        $provider = CredentialProvider::instanceProfile([
          'timeout' => 5, // Timeout after waiting for 5 seconds instead of 1
          'retries' => 5, // Retry 5 times before returning an error instead of 3
        ]);

        $ARN = "arn:aws:iam::".AWS_EPICORE_ARN.":role/".AWS_EPICORE_IAM_ROLENAME;
        
        $sessionName = "aws-access-assume-role-epicore-nonprod";

        // echo 'region:'.AWS_REGION;
        // echo 'arn:'.AWS_EPICORE_ARN;;
        // echo 'role:'.AWS_EPICORE_IAM_ROLENAME;
        
        $assumeRoleCredentials = new AssumeRoleCredentialProvider([
            'client' => new StsClient([
                'region' => AWS_REGION,
                'version' => '2011-06-15',
                'credentials' => $profile,
                // 'debug' => true,
            ]),
            'assume_role_params' => [
                'RoleArn' => $ARN,
                'RoleSessionName' => $sessionName,
            ],
        ]);

          // To avoid unnecessarily fetching STS credentials on every API operation,
        // the memoize function handles automatically refreshing the credentials when they expire
        $this->provider = CredentialProvider::memoize($assumeRoleCredentials);
     

        
    }
    
    public static function getInstance()
    {
      if(!self::$instance)
      {
        self::$instance = new AWSCredentialsProvider();
      }
      return self::$instance;
    }
    
    public function fetchAWSCredentialsFromRole()
    {
      return $this->provider;
    }
    
}

?>