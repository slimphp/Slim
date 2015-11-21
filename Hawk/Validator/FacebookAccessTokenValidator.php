<?php
namespace Hawk\Validator;

use Facebook\Facebook;

class FacebookAccessTokenValidator implements ValidatorInterface
{
	/**
	 * [validate description]
	 *
	 * @throws Exception   An exception describing the error.
	 *
	 * @param  mixed $data [description]
	 *
	 * @return void        [description]
	 */
	public function validate($data)
	{
		$facebook = new Facebook([
            'app_id'     => '919001768122145',
            'app_secret' => '417edcba61e8b0188fb54879d703a9ad'
        ]);

        $facebook->setAccessToken($data);

        if (!$facebook->getUser())
            throw new InvalidParameterException('Facebook-Access-Token');
	}
}