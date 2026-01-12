<?php

namespace toubilib\core\application\usecases;

use Exception;
use PDOException;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\application\ports\api\dtos\inputs\CredentialsDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;
use toubilib\core\application\ports\api\servicesInterfaces\AuthnServiceInterface;
use toubilib\core\domain\entities\User;
use toubilib\core\domain\exceptions\AuthenticationFailedException;
use toubilib\core\domain\exceptions\DuplicateEmailException;
use toubilib\core\domain\exceptions\InvalidPasswordException;
use toubilib\core\domain\exceptions\RepositoryEntityNotFoundException;
use toubilib\core\domain\validators\PasswordValidator;

final class AuthnService implements AuthnServiceInterface
{

    public function __construct(
        private AuthRepositoryInterface $authRepository
    )
    {
    }

    public function authenticate(CredentialsDTO $credentials): ProfileDTO
    {
        try {
            $user = $this->authRepository->byEmail($credentials->email);
        } catch (RepositoryEntityNotFoundException $e) {
            throw new AuthenticationFailedException('Invalid credentials');
        }

        if (password_verify($credentials->password, $user->getPassword())) {
            return new ProfileDTO($user->getID(), $user->getEmail(), $user->getRole());
        }
        throw new AuthenticationFailedException('Invalid credentials');

    }

    public function register(CredentialsDTO $credentials, int $role): profileDTO
    {
        try {
            PasswordValidator::validate($credentials->password);
            $user = new User($credentials->email, $credentials->password, $role);
            $this->authRepository->save($user);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                throw new DuplicateEmailException();
            }
            throw $e;
        }
        return new ProfileDTO($user->getID(), $user->getEmail(), $user->getRole());
    }
}