<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Settings changed in the administration; the environment only provides the defaults.
 */
class AppSettings
{
    private const OFFICE_EMAIL = 'office_email';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly EntityManagerInterface $em,
        #[Autowire('%office_email%')] private readonly string $defaultOfficeEmail,
    ) {
    }

    /** Where the technicians' answers go (accepted, declined, interested). */
    public function officeEmail(): string
    {
        $value = trim((string) $this->settings->find(self::OFFICE_EMAIL)?->getValue());

        return $value !== '' ? $value : $this->defaultOfficeEmail;
    }

    public function setOfficeEmail(string $email): void
    {
        $setting = $this->settings->find(self::OFFICE_EMAIL) ?? new Setting(self::OFFICE_EMAIL);
        $setting->setValue(trim($email));
        $this->em->persist($setting);
        $this->em->flush();
    }
}
