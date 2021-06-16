
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
        $profile = new InstanceProfileProvider();
        $ARN = "arn:aws:iam::503172036736:role/OrganizationAccountAccessRole";
        $sessionName = "aws-access-assume-role-epicore-nonprod";

        $assumeRoleCredentials = new AssumeRoleCredentialProvider([
            'client' => new StsClient([
                'region' => AWS_REGION,
                'version' => '2011-06-15',
                'credentials' => $profile
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
      // return $this->provider;
      return self::memoize(
        self::chain(
            self::env(),
            self::$provider
        )
      );
    }
    
}

?>