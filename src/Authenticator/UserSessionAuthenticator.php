<?php
declare(strict_types=1);

namespace App\Authenticator;

use ArrayAccess;
use Authentication\Authenticator\PrimaryKeySessionAuthenticator;
use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Keeps only the user id in session and reloads the user on each request.
 *
 * Sessions opened before this authenticator stored the whole User entity: they are converted
 * on the fly so that nobody is logged out. Can be removed once these sessions have expired.
 */
class UserSessionAuthenticator extends PrimaryKeySessionAuthenticator
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to authenticate with.
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        $sessionKey = $this->getConfig('sessionKey');
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');

        $stored = $session->read($sessionKey);
        if ($stored instanceof ArrayAccess || is_array($stored)) {
            $session->write($sessionKey, $stored[$this->getConfig('idField')]);
        }

        $result = parent::authenticate($request);
        if ($result->getStatus() === ResultInterface::FAILURE_IDENTITY_NOT_FOUND) {
            // persistIdentity() does not overwrite a stored id: a dead one would block the next login
            $session->delete($sessionKey);
        }

        return $result;
    }
}
