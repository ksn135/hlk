<?php

namespace App\Security;

use App\Entity\Contractor;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ContractorUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Contractor) {
            return;
        }

        if (null !== $user->getDeletedAt()) {
            throw new CustomUserMessageAccountStatusException('Учётная запись удалена.');
        }

        if (!$user->isAllowLkAccess()) {
            throw new CustomUserMessageAccountStatusException('Доступ к личному кабинету отключён.');
        }

        if (null === $user->getPassword() || '' === $user->getPassword()) {
            throw new CustomUserMessageAccountStatusException('Пароль для входа не задан. Обратитесь к менеджеру.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
