<?php

namespace toubilib\infra\repositories;

use Exception;
use PDO;
use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;
use toubilib\core\domain\entities\User;

class PDOAuthRepository implements AuthRepositoryInterface
{

    public function __construct(
        private PDO $pdo,
    )
    {

    }

    public function byEmail(string $email): User
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password, role FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $user = new User($row['email'], $row['password'], (int)$row['role']);
            $user->setId($row['id']);
            return $user;

        }
        throw new Exception('User not found');
    }

    public function byId(string $id): User
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password, role FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $user = new User($row['email'], $row['password'], (int)$row['role']);
            $user->setId($row['id']);
            return $user;
        }
        throw new Exception('User not found');

    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (id, email, password, role) VALUES (:id, :email, :password, :role)');
        $stmt->execute([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'password' => $user->getHashedPassword(),
            'role' => $user->getRole()
        ]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function update(User $user): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET email = :email, password = :password, role = :role WHERE id = :id');
        $stmt->execute([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => $user->getRole()
        ]);
    }
}
