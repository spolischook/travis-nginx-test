<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\AccountBundle\Mailer\Processor;

class AccountUserManager extends BaseUserManager implements ContainerAwareInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Processor
     */
    protected $emailProcessor;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param AccountUser $user
     */
    public function register(AccountUser $user)
    {
        if ($this->isConfirmationRequired()) {
            $this->sendConfirmationEmail($user);
        } else {
            $this->confirmRegistration($user);
        }
    }

    /**
     * @param AccountUser $user
     */
    public function confirmRegistration(AccountUser $user)
    {
        $user->setConfirmed(true)
            ->setConfirmationToken(null);
        $this->sendWelcomeEmail($user);
    }

    /**
     * @param AccountUser $user
     */
    public function sendWelcomeEmail(AccountUser $user)
    {
        $this->getEmailProcessor()->sendWelcomeNotification(
            $user,
            $this->isSendPasswordInWelcomeEmail() ? $user->getPlainPassword() : null
        );
    }

    /**
     * @param AccountUser $user
     */
    public function sendConfirmationEmail(AccountUser $user)
    {
        $user->setConfirmed(false)
            ->setConfirmationToken($user->generateToken());
        $this->getEmailProcessor()->sendConfirmationEmail($user);
    }

    /**
     * @param AccountUser $user
     */
    public function sendResetPasswordEmail(AccountUser $user)
    {
        $this->getEmailProcessor()->sendResetPasswordEmail($user);
    }

    /**
     * @param int $maxLength
     * @return string
     */
    public function generatePassword($maxLength)
    {
        return substr($this->generateToken(), 0, $maxLength);
    }

    /**
     * @param string $name
     * @return array|string
     */
    protected function getConfigValue($name)
    {
        if (!$this->configManager) {
            $this->configManager = $this->container->get('oro_config.manager');
        }

        return $this->configManager->get($name);
    }

    /**
     * @return Processor
     */
    protected function getEmailProcessor()
    {
        if (!$this->emailProcessor) {
            $this->emailProcessor = $this->container->get('orob2b_account.mailer.processor');
        }

        return $this->emailProcessor;
    }

    /**
     * @return string
     */
    protected function generateToken()
    {
        return rtrim(strtr(base64_encode(hash('sha256', uniqid(mt_rand(), true), true)), '+/', '-_'), '=');
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return bool
     */
    public function isConfirmationRequired()
    {
        return (bool)$this->getConfigValue('oro_b2b_account.confirmation_required');
    }

    /**
     * @return bool
     */
    protected function isSendPasswordInWelcomeEmail()
    {
        return (bool)$this->getConfigValue('oro_b2b_account.send_password_in_welcome_email');
    }
}
