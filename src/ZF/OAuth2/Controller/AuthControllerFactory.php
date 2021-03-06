<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\OAuth2\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\OAuth2\Adapter\PdoAdapter as OAuth2Storage;
use OAuth2\Server as OAuth2Server;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;


class AuthControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator()->get('ServiceManager');
        $config   = $services->get('Configuration');

        if (!isset($config['zf-oauth2']['db']) || empty($config['zf-oauth2']['db'])) {
            throw new Exception\RuntimeException(
                'The database configuration [\'zf-oauth2\'][\'db\'] for OAuth2 is missing'
            );
        }

        $username = isset($config['zf-oauth2']['db']['username']) ? $config['zf-oauth2']['db']['username'] : null;
        $password = isset($config['zf-oauth2']['db']['password']) ? $config['zf-oauth2']['db']['password'] : null;

        $storage = new OAuth2Storage(array(
            'dsn'      => $config['zf-oauth2']['db']['dsn'],
            'username' => $username,
            'password' => $password,
        ));

        $enforceState  = isset($config['zf-oauth2']['enforce_state'])  ? $config['zf-oauth2']['enforce_state']  : true;
        $allowImplicit = isset($config['zf-oauth2']['allow_implicit']) ? $config['zf-oauth2']['allow_implicit'] : false;

        // Pass a storage object or array of storage objects to the OAuth2 server class
        $server = new OAuth2Server($storage, array('enforce_state' => $enforceState, 'allow_implicit' => $allowImplicit));

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $server->addGrantType(new ClientCredentials($storage));

        // Add the "Authorization Code" grant type (this is where the oauth magic happens)
        $server->addGrantType(new AuthorizationCode($storage));

        // Add the "User Credentials" grant type
        $server->addGrantType(new UserCredentials($storage));

        // Add the "Refresh Token" grant type
        $server->addGrantType(new RefreshToken($storage));

        return new AuthController($server);
    }
}
