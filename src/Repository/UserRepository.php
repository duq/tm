<?php

namespace App\Repository;

use App\Entity\User;
use App\Services\UserService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private $userService;

    public function __construct(ManagerRegistry $registry, UserService $userService)
    {
        parent::__construct($registry, User::class);
        $this->userService = $userService;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @return array
     */
    public function getAllUsers(): array
    {
        $users = $this->findAll();
        $usersArray = [];
        foreach ($users as $user)
        {
            $usersArray[] = $this->userService->transformUser($user);
        }
        return $usersArray;
    }

    public function getUserById(int $id): ?User
    {
        $user = $this->find($id);
        if ($user) {
            return $this->find($user);
        } else {
            return null;
        }
    }
}
